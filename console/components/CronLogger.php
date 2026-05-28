<?php

namespace console\components;

use Yii;

/**
 * Appends a START / END line (with timestamp and duration) to a plain-text
 * log every time a console command runs, so there is a simple record of when
 * each cron started and finished.
 *
 * Wired in console/config/main.php via the application's beforeAction /
 * afterAction events — no changes needed in the individual controllers.
 *
 * Only the scheduled cron commands are logged; ad-hoc commands (migrate, help,
 * cache/flush, …) are ignored to keep the log focused.
 *
 * Log file: console/runtime/logs/cron.log
 */
final class CronLogger
{
    /** Controller IDs that run on a schedule. */
    private const CRON_CONTROLLERS = [
        'steam',
        'steam-spy',
        'sale',
        'genre',
        'tag',
        'category',
        'sitemap',
    ];

    /** @var array<string, float> route => start microtime */
    private static array $started = [];

    public static function start(string $route): void
    {
        if (!self::isCron($route)) {
            return;
        }

        self::$started[$route] = microtime(true);
        self::write('START  ' . $route);
    }

    public static function finish(string $route): void
    {
        if (!self::isCron($route)) {
            return;
        }

        $suffix = '';
        if (isset(self::$started[$route])) {
            $suffix = sprintf(' (%.1fs)', microtime(true) - self::$started[$route]);
        }

        self::write('END    ' . $route . $suffix);
    }

    private static function isCron(string $route): bool
    {
        $controllerId = explode('/', $route)[0] ?? '';

        return in_array($controllerId, self::CRON_CONTROLLERS, true);
    }

    private static function write(string $message): void
    {
        $file = Yii::getAlias('@app/runtime/logs/cron.log');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents(
            $file,
            sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message),
            FILE_APPEND | LOCK_EX
        );
    }
}
