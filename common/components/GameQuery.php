<?php

namespace common\components;

use common\models\Game;
use common\models\GameOffer;
use common\models\GameStoreScan;
use common\components\AdultContent;
use yii\db\ActiveQuery;

/**
 * Custom ActiveQuery class for Game model.
 *
 */
class GameQuery extends ActiveQuery
{

    /**
     * Min length of phrase part.
     *
     * @var int
     */
    public $minLength = 3;

    /**
     * @var string[]
     */
    public $titleDelimiters = [
        '{',
        '}',
        '\\',
        '/',
        '–',
        '_',
        ':',
        '\'',
        '.',
        ',',
        '!',
        '?',
        '[',
        ']',
        '(',
        ')',
        '&',
        '#',
        '-',
        '+',
    ];

    /**
     * Scope - only active (published) games. Apply explicitly wherever a
     * user-facing list should hide unsynced / hidden games.
     *
     * Pass $alias when the query is aliased (e.g. ->alias('game')) so the
     * condition targets the right table.
     */
    public function active(string $alias = 'game'): GameQuery
    {
        return $this->andWhere([$alias . '.status' => Game::STATUS_ACTIVE]);
    }

    /**
     * Scope — hides adult (is_adult = 1) games from a catalogue list unless the
     * current viewer has opted in. Delegates the per-viewer decision to
     * {@see AdultContent}. Pass $alias when the query is aliased.
     */
    public function hideAdultCatalog(string $alias = 'game'): GameQuery
    {
        AdultContent::filterCatalog($this, $alias);

        return $this;
    }

    /**
     * Scope — games eligible for a keyshop match attempt on $storeId.
     *
     * Narrows the 200k-row catalogue to titles a keyshop could plausibly carry
     * and that we haven't settled yet: active, real games (not DLC/tools/demos),
     * non-free, actually priced on Steam, without an offer on this store, and —
     * unless $ignoreCooldown — past their miss-backoff window
     * (see {@see GameStoreScan::record()}). The priced filter is the big cut:
     * free/unpriced Steam apps are never sold on keyshops, so re-searching them
     * every cooldown is wasted traffic. Newest games first.
     */
    public function keyshopMatchCandidates(int $storeId, bool $ignoreCooldown = false): GameQuery
    {
        $alreadyOffered = GameOffer::find()
            ->select('game_id')
            ->where(['store_id' => $storeId]);

        $this->andWhere(['status' => Game::STATUS_ACTIVE, 'type' => Game::TYPE_GAME])
            ->andWhere(['or', ['is_free' => 0], ['is_free' => null]])
            ->andWhere(['>', 'steam_price_final', 0])
            ->andWhere(['not in', 'id', $alreadyOffered]);

        if (!$ignoreCooldown) {
            $coolingDown = GameStoreScan::find()
                ->select('game_id')
                ->where(['store_id' => $storeId])
                ->andWhere(['>', 'next_check_at', date('Y-m-d H:i:s')]);
            $this->andWhere(['not in', 'id', $coolingDown]);
        }

        return $this->orderBy(['id' => SORT_DESC]);
    }

    /**
     * Scope - filter results by given game title.
     *
     * @param string $title
     * @return $this
     */
    public function onlyWithTitle($title)
    {
        if (strcmp($title, '') === 0) {
            // empty phrase
            return $this->andWhere(['title' => '']);
        }

        // handle more specific searches
        if (substr($title, 0, 1) === '=') {
            return $this->andWhere(['game.title' => substr($title, 1)]);
        } else if (substr($title, 0, 1) === '"') {
            return $this->andFilterWhere(['like', 'game.title', trim($title, '"')]);
        }

        $phrase = str_replace($this->titleDelimiters, ' ', $title);
        $words = explode(' ', $phrase);
        $where = ['and'];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= $this->minLength || is_numeric($word)) {
                $where[] = ['like', 'game.title', $word];
            }
        }

        return $this->andFilterWhere([
            'or',
            ['like', 'game.title', $title],
            $where,
        ]);
    }

}
