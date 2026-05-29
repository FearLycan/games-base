<?php

namespace console\controllers;

use console\controllers\base\RecountController;

/**
 * Refreshes the precomputed games_count column on every genre row.
 * Counts active games of type=game linked to each genre via the
 * game_genre pivot table. Designed to run daily via cron.
 */
class GenreController extends RecountController
{
    protected function entity(): string
    {
        return 'genre';
    }
}
