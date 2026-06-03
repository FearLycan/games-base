<?php

namespace common\components;

use Throwable;
use Yii;
use yii\web\HttpException;

/**
 * Records context for 404 (and other client-error) responses so broken inbound
 * links can be traced. The key signal is the HTTP Referer — it reveals where the
 * bad URL was published (an external site, a stale internal link, a mistyped
 * sitemap entry) — alongside the requested URL, client identity and a bot flag.
 *
 * Entries go to the dedicated `notfound` log category (see the log target in the
 * app config) so they land in their own file instead of polluting error logs.
 */
class NotFoundLogger
{
    /**
     * Base log category for not-found tracing. Human traffic is logged under this
     * exact category; bot traffic under the `.bot` sub-category (see CATEGORY_BOT).
     * The file target captures both via the `notfound*` wildcard, while an email
     * target can subscribe to the exact `notfound` category to alert only on real
     * users — keeping crawler noise out of the inbox.
     */
    public const string CATEGORY = 'notfound';

    /** Sub-category for crawler/bot 404s — wildcard-matched by the file target. */
    public const string CATEGORY_BOT = 'notfound.bot';

    /**
     * Logs the request context for a not-found / client-error exception. No-op
     * for non-HTTP exceptions and for 5xx server errors (those belong in the
     * normal error log, not here). Never throws — logging must not break the
     * already-failing error page.
     */
    public static function log(?Throwable $exception): void
    {
        if (!$exception instanceof HttpException) {
            return;
        }

        // Only client errors (4xx). 404 is the common case; 403/410 are useful too.
        if ($exception->statusCode < 400 || $exception->statusCode >= 500) {
            return;
        }

        try {
            $request = Yii::$app->request;
            $userAgent = $request->userAgent;
            $isBot = BotDetector::isBot($userAgent);

            $context = [
                'status'    => $exception->statusCode,
                'url'       => $request->absoluteUrl,
                'referrer'  => $request->referrer ?: '(none)',
                'method'    => $request->method,
                'ip'        => $request->userIP,
                'bot'       => $isBot ? 'yes' : 'no',
                'userAgent' => $userAgent ?: '(none)',
                'userId'    => Yii::$app->has('user') && !Yii::$app->user->isGuest
                    ? Yii::$app->user->id
                    : null,
            ];

            // Pipe-delimited single line keeps grep/tail readable in the log file.
            $line = implode(' | ', array_map(
                static fn($key, $value): string => "$key=$value",
                array_keys($context),
                array_values($context),
            ));

            // Route bots to a sub-category so email alerts can target only humans.
            Yii::info($line, $isBot ? self::CATEGORY_BOT : self::CATEGORY);
        } catch (Throwable $e) {
            // Swallow: the error page must render even if logging context fails.
        }
    }
}
