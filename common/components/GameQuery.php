<?php

namespace common\components;

use common\models\Game;
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
