<?php

namespace common\components;

use common\models\IpAddress;
use Throwable;
use Yii;
use yii\base\BootstrapInterface;
use yii\web\Application;

/**
 * Denies blocked IPs on the public site.
 *
 * Registered in the frontend `bootstrap` list, it runs before routing: if the
 * requesting IP is flagged {@see IpAddress::BLOCKED}, it returns a bare 403 and
 * ends the request — the visitor never reaches a controller. The block list is
 * cached (and invalidated by {@see IpAddress::afterSave()} / `afterDelete()`)
 * so the common case costs a single cache read, not a query per request.
 *
 * Every step is wrapped defensively: a DB/cache hiccup must never take the site
 * down, so on any failure the request is simply allowed through.
 */
class IpBlocker implements BootstrapInterface
{
    public const string CACHE_KEY = 'ip:blocked-set';

    /** How long the block list may be served from cache (seconds). */
    private const int CACHE_TTL = 300;

    public function bootstrap($app): void
    {
        if (!$app instanceof Application) {
            return;
        }

        try {
            $ip = $app->request->userIP;
            if (!$ip || !in_array($ip, self::blockedSet(), true)) {
                return;
            }

            $response = $app->response;
            $response->statusCode = 403;
            $response->content = 'Your IP address has been blocked.';
            $response->send();
            $app->end();
        } catch (Throwable $e) {
            // Fail open: never let blocking logic break a normal request.
        }
    }

    /**
     * Blocked IPs as a flat list, cached. Local DummyCache turns this into a
     * per-request query (fine); Redis serves it from memory in production.
     *
     * @return string[]
     */
    public static function blockedSet(): array
    {
        return Yii::$app->cache->getOrSet(
            self::CACHE_KEY,
            static fn(): array => IpAddress::find()
                ->select('ip')
                ->where(['is_blocked' => IpAddress::BLOCKED])
                ->column(),
            self::CACHE_TTL,
        );
    }

    /** Drops the cached block list so the next request rebuilds it. */
    public static function flush(): void
    {
        try {
            Yii::$app->cache->delete(self::CACHE_KEY);
        } catch (Throwable $e) {
            // No cache component / already gone — nothing to invalidate.
        }
    }
}
