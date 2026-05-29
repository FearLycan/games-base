<?php

namespace console\controllers;

use console\controllers\base\RecountController;

/**
 * Refreshes the precomputed games_count column on every publisher row.
 * Counts active games of type=game linked to each publisher via the
 * game_publisher pivot table. Designed to run daily via cron, after steam/sync.
 */
class PublisherController extends RecountController
{
    protected function entity(): string
    {
        return 'publisher';
    }
}
