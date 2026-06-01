<?php

namespace common\components;

/**
 * Lightweight User-Agent based crawler detection. Keeps automated traffic from
 * triggering side effects meant for real visitors — specifically, flagging
 * games for re-sync on detail-page views. A crawler sweeping the whole
 * catalogue would otherwise mark thousands of games as force_sync and starve
 * first-time syncs of new games.
 *
 * This is a throttle, not a security control: it only inspects the UA string.
 */
class BotDetector
{
    /**
     * Case-insensitive substrings that mark a request as automated. Generic
     * tokens (bot/crawl/spider/slurp) catch the long tail of search engines and
     * SEO tools; the rest are notable agents that don't contain those tokens.
     */
    private const array SIGNATURES = [
        'bot', 'crawl', 'spider', 'slurp',
        'mediapartners', 'facebookexternalhit', 'ia_archiver', 'archive.org',
        'semrush', 'ahrefs', 'yandex', 'bytespider',
        'curl', 'wget', 'python-requests', 'go-http-client', 'scrapy', 'headlesschrome',
    ];

    public static function isBot(?string $userAgent): bool
    {
        // A missing UA is treated as a bot: real browsers always send one.
        if ($userAgent === null || $userAgent === '') {
            return true;
        }

        $ua = strtolower($userAgent);
        foreach (self::SIGNATURES as $signature) {
            if (str_contains($ua, $signature)) {
                return true;
            }
        }

        return false;
    }
}
