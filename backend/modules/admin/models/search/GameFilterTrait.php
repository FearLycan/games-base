<?php

namespace backend\modules\admin\models\search;

use yii\db\ActiveQuery;

/**
 * Shared "find by game" filter for search models that join the game table.
 *
 * The single `gameTitle` box matches the game's title; an all-digits term is
 * treated as a Steam App ID (exact match) instead, so the same field works for
 * both lookups. Requires the query to have joined `game` (e.g. ->joinWith('game')).
 */
trait GameFilterTrait
{
    /** Virtual filter: matches the related game's title or Steam App ID. */
    public ?string $gameTitle = null;

    protected function applyGameFilter(ActiveQuery $query): void
    {
        $term = trim((string)$this->gameTitle);

        if ($term === '') {
            return;
        }

        if (ctype_digit($term)) {
            $query->andWhere(['game.steam_appid' => (int)$term]);
        } else {
            $query->andWhere(['like', 'game.title', $term]);
        }
    }
}
