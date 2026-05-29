<?php

namespace console\controllers;

use console\controllers\base\RecountController;

/**
 * Refreshes the precomputed games_count column on every developer row.
 * Counts active games of type=game linked to each developer via the
 * game_developer pivot table. Designed to run daily via cron, after steam/sync.
 */
class DeveloperController extends RecountController
{
    protected function entity(): string
    {
        return 'developer';
    }
}
