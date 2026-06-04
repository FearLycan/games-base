<?php

namespace common\components;

use common\models\Game;
use common\models\GameOffer;
use common\models\GameStoreScan;
use yii\db\ActiveQuery;
use yii\db\Expression;

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
     * Scope - filter results by given game title, ranked by relevance.
     *
     * Tokenises the phrase on {@see $titleDelimiters} and keeps words of at
     * least {@see $minLength} chars (numbers are always kept). Instead of the
     * old "every word must match" AND — which dropped a whole result the moment
     * one typed word wasn't in the title — it counts how many words match and
     * requires a majority: short queries (<=2 words) stay strict, longer ones
     * tolerate a missing word. This is what makes longer, multi-word titles
     * still surface when the user adds or mistypes a word.
     *
     * It also injects a relevance ORDER BY (exact title > starts-with phrase >
     * contains phrase > number of matched words). Callers that want their own
     * tie-breakers (e.g. review count) MUST use addOrderBy() so relevance stays
     * primary; a plain orderBy() would wipe it.
     *
     * Two power operators bypass ranking:
     *   =Exact Title   -> exact equality
     *   "substring"    -> single literal LIKE
     *
     * @param string $title
     * @return $this
     */
    public function onlyWithTitle(string $title): static
    {
        if ($title === '') {
            return $this->andWhere(['title' => '']);
        }

        if (str_starts_with($title, '=')) {
            return $this->andWhere(['game.title' => substr($title, 1)]);
        }

        if (str_starts_with($title, '"')) {
            $inner = trim($title, '"');
            if ($inner === '') {
                return $this->andWhere(['title' => '']);
            }
            return $this->andWhere(['like', 'game.title', $inner]);
        }

        $phrase = str_replace($this->titleDelimiters, ' ', $title);
        $words = [];
        foreach (explode(' ', $phrase) as $word) {
            $word = trim($word);
            if ($word !== '' && (strlen($word) >= $this->minLength || is_numeric($word))) {
                $words[] = $word;
            }
        }

        // Nothing tokenisable (e.g. all words below minLength) -> plain contains.
        if ($words === []) {
            return $this->andWhere(['like', 'game.title', $title]);
        }

        // Each test yields 1/0 in MySQL, so their sum is the number of matched
        // words — reused as both the filter threshold and the rank score.
        $hitTests = [];
        $wordParams = [];
        foreach (array_values($words) as $i => $word) {
            $hitTests[] = "(game.title LIKE :tw{$i})";
            $wordParams[":tw{$i}"] = '%' . $this->escapeLike($word) . '%';
        }
        $matchCount = implode(' + ', $hitTests);

        // Require a majority of words; longer queries may miss one, short ones
        // must match all so 2-word searches stay precise.
        $wordCount = count($words);
        $minHits = $wordCount <= 2 ? $wordCount : (int)ceil($wordCount * 0.6);

        $this->andWhere(new Expression("({$matchCount}) >= {$minHits}", $wordParams));

        // Relevance: exact title wins, then a leading phrase match, then a
        // contained phrase, then how many individual words were hit.
        // $wordParams are already registered via the WHERE Expression above —
        // Yii2 merges all Expression params globally, so no need to repeat them.
        $scoreParams = [
            ':exTitle'    => $title,
            ':exPrefix'   => $this->escapeLike($title) . '%',
            ':exContains' => '%' . $this->escapeLike($title) . '%',
        ];
        $score = '(CASE WHEN game.title = :exTitle THEN 1000 ELSE 0 END)'
            . ' + (CASE WHEN game.title LIKE :exPrefix THEN 200 ELSE 0 END)'
            . ' + (CASE WHEN game.title LIKE :exContains THEN 100 ELSE 0 END)'
            . " + ({$matchCount}) * 10";

        return $this->addOrderBy(new Expression("({$score}) DESC", $scoreParams));
    }

    /**
     * Escapes LIKE wildcards (\ % _) so user input is matched literally, the
     * same way Yii's built-in LIKE condition builder does (backslash escape).
     */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

}
