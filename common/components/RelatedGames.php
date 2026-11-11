<?php

namespace common\components;

use Yii;
use yii\caching\CacheInterface;
use yii\db\Connection;
use yii\db\Query;

/**
 * "More games like this": games sharing the most genres with a given game,
 * ties broken by review volume.
 *
 * The previous single query joined every game in the shared genres (e.g. the
 * whole "Action"/"Indie" catalogue), grouped and sorted it on every page-cache
 * miss: 1.1-1.5 s per game page, full scan of `game` + temporary table +
 * filesort. Crawlers hit the long tail of ~130k games, so almost every request
 * was a miss and each held a php-fpm worker for seconds.
 *
 * Now:
 *  1. per genre, the top N games by review count are computed once and cached
 *     (a few dozen genres → a few dozen queries per TTL, shared by all games);
 *  2. for one game, the candidate lists of its genres are merged and the
 *     shared-genre count is read from `game_genre` by primary key
 *     (game_id, genre_id) for those few hundred candidates only.
 *
 * Ranking is the same (shared genres DESC, reviews DESC); candidates are
 * limited to each genre's most-reviewed titles, which is what the block is
 * for anyway.
 */
final class RelatedGames
{
    public const CANDIDATES_PER_GENRE = 150;
    public const CACHE_TTL = 21600; // 6 h

    /**
     * @param int[] $genreIds genres of the source game
     * @return int[] related game ids, best first
     */
    public static function findIds(
        int $gameId,
        array $genreIds,
        bool $includeAdult,
        int $limit = 6,
        ?Connection $db = null,
        ?CacheInterface $cache = null,
    ): array {
        $genreIds = array_values(array_unique(array_map('intval', $genreIds)));
        if ($genreIds === []) {
            return [];
        }
        $db ??= Yii::$app->db;
        $cache ??= Yii::$app->cache;

        // 1. Candidate pool: most-reviewed games of each genre (cached per genre).
        $reviewsById = [];
        foreach ($genreIds as $genreId) {
            foreach (self::topIdsForGenre($genreId, $includeAdult, $db, $cache) as $id => $reviews) {
                if ($id === $gameId) {
                    continue;
                }
                $reviewsById[$id] = max($reviewsById[$id] ?? 0, $reviews);
            }
        }
        if ($reviewsById === []) {
            return [];
        }

        // 2. Shared-genre count for the candidates only (PK lookup).
        $shared = [];
        $rows = (new Query())
            ->select(['game_id', 'genre_id'])
            ->from('{{%game_genre}}')
            ->where(['game_id' => array_keys($reviewsById), 'genre_id' => $genreIds])
            ->all($db);
        foreach ($rows as $row) {
            $id = (int)$row['game_id'];
            $shared[$id] = ($shared[$id] ?? 0) + 1;
        }

        // 3. Rank: shared genres DESC, reviews DESC, id ASC (stable).
        $ids = array_keys($reviewsById);
        usort($ids, static function (int $a, int $b) use ($shared, $reviewsById): int {
            return [($shared[$b] ?? 0), $reviewsById[$b], $a] <=> [($shared[$a] ?? 0), $reviewsById[$a], $b];
        });

        return array_slice($ids, 0, $limit);
    }

    /**
     * @return array<int,int> game id => review count, most reviewed first
     */
    public static function topIdsForGenre(int $genreId, bool $includeAdult, Connection $db, CacheInterface $cache): array
    {
        $key = ['related-games.top-by-genre', $genreId, $includeAdult ? 1 : 0, self::CANDIDATES_PER_GENRE];

        return $cache->getOrSet($key, static function () use ($genreId, $includeAdult, $db): array {
            $query = (new Query())
                ->select(['g.id', 'reviews' => 'COALESCE(r.total_reviews, 0)'])
                ->from(['g' => '{{%game}}'])
                ->innerJoin(['gg' => '{{%game_genre}}'], 'gg.game_id = g.id AND gg.genre_id = :genre', [':genre' => $genreId])
                ->leftJoin(['r' => '{{%review}}'], 'r.game_id = g.id')
                ->where(['g.status' => 1, 'g.type' => 'game'])
                ->orderBy(['reviews' => SORT_DESC, 'g.id' => SORT_ASC])
                // Over-fetch a little in case `review` has more than one row per game.
                ->limit(self::CANDIDATES_PER_GENRE * 2);
            if (!$includeAdult) {
                $query->andWhere(['g.is_adult' => 0]);
            }

            $result = [];
            foreach ($query->all($db) as $row) {
                $id = (int)$row['id'];
                if (!isset($result[$id])) {
                    $result[$id] = (int)$row['reviews'];
                }
                if (count($result) >= self::CANDIDATES_PER_GENRE) {
                    break;
                }
            }

            return $result;
        }, self::CACHE_TTL);
    }
}
