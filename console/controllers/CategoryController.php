<?php

namespace console\controllers;

use console\controllers\base\RecountController;

/**
 * Refreshes the precomputed games_count column on every category row.
 * Counts active games of type=game linked to each category via the
 * game_category pivot table. Designed to run daily via cron, after steam/sync.
 */
class CategoryController extends RecountController
{
    protected function entity(): string
    {
        return 'category';
    }
}
