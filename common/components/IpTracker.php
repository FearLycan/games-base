<?php

namespace common\components;

use common\models\IpAddress;
use common\models\IpError;
use GeoIp2\Database\Reader;
use Throwable;
use Yii;
use yii\web\HttpException;

/**
 * Attributes error responses to the requesting IP.
 *
 * Called from the app's error action (see frontend SiteController::actionError),
 * it upserts an {@see IpAddress} row — bumping the aggregate `error_count`,
 * refreshing the last-error snapshot and the country — and appends an
 * {@see IpError} event so the admin can see exactly which errors a source
 * produced. Records every error that reaches the error page (4xx and 5xx).
 *
 * Mirrors {@see NotFoundLogger}'s defensive contract: it never throws — tracking
 * must not break an already-failing error page.
 */
class IpTracker
{
    /** Inserted/updated string columns are truncated to their DB limits. */
    private const int PATH_LIMIT = 1024;
    private const int UA_LIMIT   = 512;

    public static function recordError(?Throwable $exception): void
    {
        try {
            $request = Yii::$app->request;
            $ip = $request->userIP;
            if (!$ip) {
                return;
            }

            // HttpExceptions carry their own status; anything else reaching the
            // error action is an unhandled server error (500).
            $status = $exception instanceof HttpException ? $exception->statusCode : 500;

            $userAgent = $request->userAgent;
            $isBot = BotDetector::isBot($userAgent);
            $path = self::clip($request->url, self::PATH_LIMIT);
            $now = date('Y-m-d H:i:s');

            $model = IpAddress::findOne(['ip' => $ip]);
            if ($model === null) {
                $model = new IpAddress([
                    'ip'            => $ip,
                    'country'       => self::lookupCountry($ip),
                    'first_seen_at' => $now,
                ]);
            }

            $model->error_count     = (int)$model->error_count + 1;
            $model->last_status     = $status;
            $model->last_path       = $path;
            $model->last_user_agent = self::clip($userAgent, self::UA_LIMIT);
            $model->is_bot          = $isBot ? 1 : 0;
            $model->last_seen_at    = $now;

            if (!$model->save(false)) {
                return;
            }

            $event = new IpError([
                'ip_address_id' => $model->id,
                'status'        => $status,
                'method'        => self::clip($request->method, 10),
                'path'          => $path,
                'referrer'      => self::clip($request->referrer, self::PATH_LIMIT),
                'user_agent'    => self::clip($userAgent, self::UA_LIMIT),
                'is_bot'        => $isBot ? 1 : 0,
            ]);
            $event->save(false);
        } catch (Throwable $e) {
            // Swallow: the error page must render even if tracking fails.
        }
    }

    private static function clip(?string $value, int $max): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    /**
     * ISO country code for an IP via the MaxMind GeoLite2 database (the same one
     * {@see CurrencyResolver} uses), or null when it can't be resolved.
     */
    private static function lookupCountry(string $ip): ?string
    {
        $dbPath = Yii::getAlias((string)(Yii::$app->params['geoip_db'] ?? ''), false);
        if (!$dbPath || !is_file($dbPath)) {
            return null;
        }

        try {
            return (new Reader($dbPath))->country($ip)->country->isoCode;
        } catch (Throwable $e) {
            return null;
        }
    }
}
