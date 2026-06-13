<?php

namespace common\components;

use Throwable;

/**
 * Web error handler that attributes every uncaught exception to its source IP
 * before handling it as usual.
 *
 * Hooking {@see logException()} (rather than the error *view* action) means the
 * recorder fires for all errors — 4xx and 5xx alike, in both dev and prod.
 * In dev, `YII_DEBUG` makes Yii render the debug page directly for non-HTTP
 * exceptions and skip `errorAction`, so attaching to the error action would miss
 * server errors; this hook runs regardless. Recording is best-effort and never
 * interferes with the normal error flow.
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    public function logException($exception): void
    {
        IpTracker::recordError($exception);
        parent::logException($exception);
    }
}
