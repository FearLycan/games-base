<?php

namespace common\components;

use common\models\Category;
use common\models\Developer;
use common\models\Game;
use common\models\Genre;
use common\models\Publisher;
use common\models\Tag;
use Yii;
use yii\db\ActiveRecord;

/**
 * Single source of truth for refreshing the precomputed `games_count` columns
 * that drive the directory pages (genres, tags, categories, developers,
 * publishers) and their popularity sorting.
 *
 * Each entity stores a denormalized count of active type=game rows linked
 * through its pivot table; this service rebuilds those counts with one UPDATE
 * per entity and invalidates the matching count cache. Both the console
 * recount commands and steam/get-coming-soon delegate here so the SQL lives in
 * exactly one place.
 */
class GamesCountRecounter
{
    /**
     * Per-entity spec. All values are trusted constants (never user input), so
     * they are safe to interpolate into the UPDATE statement.
     *
     * @var array<string,array{table:string,pivot:string,fk:string,model:class-string<ActiveRecord>,cacheKey:?string}>
     */
    public const SPECS = [
        'genre'     => ['table' => 'genre',     'pivot' => 'game_genre',     'fk' => 'genre_id',     'model' => Genre::class,     'cacheKey' => 'genre.count'],
        'tag'       => ['table' => 'tag',       'pivot' => 'game_tag',       'fk' => 'tag_id',       'model' => Tag::class,       'cacheKey' => 'tag.count'],
        'category'  => ['table' => 'category',  'pivot' => 'game_category',  'fk' => 'category_id',  'model' => Category::class,  'cacheKey' => 'category.count'],
        'developer' => ['table' => 'developer', 'pivot' => 'game_developer', 'fk' => 'developer_id', 'model' => Developer::class, 'cacheKey' => null],
        'publisher' => ['table' => 'publisher', 'pivot' => 'game_publisher', 'fk' => 'publisher_id', 'model' => Publisher::class, 'cacheKey' => null],
    ];

    /**
     * Rebuilds games_count for a single entity and clears its count cache.
     *
     * @param string $entity one of the keys in {@see SPECS}
     * @return int number of rows whose count changed
     */
    public function recount(string $entity): int
    {
        $spec = self::SPECS[$entity] ?? throw new \InvalidArgumentException("Unknown recount entity: {$entity}");

        $sql = "
            UPDATE {{%{$spec['table']}}} e
            SET e.games_count = (
                SELECT COUNT(DISTINCT p.game_id)
                FROM {{%{$spec['pivot']}}} p
                INNER JOIN {{%game}} ga ON ga.id = p.game_id
                WHERE p.{$spec['fk']} = e.id
                  AND ga.status = :status
                  AND ga.type   = :type
            )
        ";

        $affected = Yii::$app->db->createCommand($sql, [
            ':status' => Game::STATUS_ACTIVE,
            ':type'   => Game::TYPE_GAME,
        ])->execute();

        if ($spec['cacheKey'] !== null) {
            Yii::$app->cache->delete($spec['cacheKey']);
        }

        return $affected;
    }

    /**
     * Rebuilds games_count for every known entity.
     *
     * @param string[]|null $entities subset of {@see SPECS} keys; null = all
     * @return array<string,int> entity => rows changed
     */
    public function recountAll(?array $entities = null): array
    {
        $result = [];
        foreach ($entities ?? array_keys(self::SPECS) as $entity) {
            $result[$entity] = $this->recount($entity);
        }

        return $result;
    }

    /**
     * Highest-count rows for an entity, for reporting after a recount.
     *
     * @return ActiveRecord[]
     */
    public function topRows(string $entity, int $limit = 5): array
    {
        $spec = self::SPECS[$entity] ?? throw new \InvalidArgumentException("Unknown recount entity: {$entity}");

        return $spec['model']::find()
            ->orderBy(['games_count' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}
