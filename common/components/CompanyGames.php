<?php

namespace common\components;

use yii\db\Connection;
use yii\db\Query;

/**
 * Game ids owned by a developer and/or publisher, resolved up front through the
 * pivot tables' developer_id / publisher_id indexes.
 *
 * Replaces `game.id IN (subquery) OR game.id IN (subquery)` + LEFT JOIN review,
 * which MySQL could not drive from the pivot indexes: every company page did a
 * scan of the whole `game` table (measured 0.6-0.7 s COUNT + 1.4-1.6 s SELECT
 * per request; crawlers walk every company x role x sort variant).
 */
final class CompanyGames
{
    /**
     * @param string $role 'developed' | 'published' | anything else = both
     * @return int[] distinct game ids (unfiltered by status/type — callers add that)
     */
    public static function ids(?int $developerId, ?int $publisherId, string $role = 'all', ?Connection $db = null): array
    {
        $useDeveloper = $developerId !== null && $role !== 'published';
        $usePublisher = $publisherId !== null && $role !== 'developed';

        // Role tab for a role the company doesn't have: fall back to both.
        if (!$useDeveloper && !$usePublisher) {
            $useDeveloper = $developerId !== null;
            $usePublisher = $publisherId !== null;
        }

        $ids = [];
        if ($useDeveloper) {
            $ids = (new Query())->select('game_id')->from('{{%game_developer}}')
                ->where(['developer_id' => $developerId])->column($db);
        }
        if ($usePublisher) {
            $ids = array_merge($ids, (new Query())->select('game_id')->from('{{%game_publisher}}')
                ->where(['publisher_id' => $publisherId])->column($db));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Condition for `game.id`, safe for an empty list (matches nothing).
     */
    public static function condition(array $ids, string $alias = 'game'): array
    {
        return $ids === [] ? ['0=1'] : [$alias . '.id' => $ids];
    }
}
