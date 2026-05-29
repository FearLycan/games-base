<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Collapses duplicate {{%game}} rows that share the same steam_appid, then adds
 * a UNIQUE index so they can never reappear.
 *
 * Duplicates were created by importers (coming-soon / sales / steamspy) whose
 * "does this appid already exist?" check ran through GameQuery::where(), which
 * used to force-inject `status = STATUS_ACTIVE`. Unsynced placeholders
 * (status = 0) were therefore invisible to the check and got re-inserted.
 *
 * Keep rule, per appid (highest priority wins):
 *   1. active rows (status = STATUS_ACTIVE) over everything else,
 *   2. then rows that carry data (relations / slug),
 *   3. then the oldest row (lowest id) as a deterministic tiebreak.
 *
 * Child rows (genres, tags, images, reviews, ...) of the discarded ids are
 * removed first so no orphans are left behind. In practice the discarded rows
 * are empty placeholders, but we sweep defensively.
 */
class m260529_120000_dedupe_games_by_steam_appid extends Migration
{
    private const string INDEX_NAME = 'idx_game_steam_appid_unique';

    /** Tables that reference game.id via a game_id column. */
    private const array CHILD_TABLES = [
        '{{%game_category}}',
        '{{%game_developer}}',
        '{{%game_genre}}',
        '{{%game_image}}',
        '{{%game_publisher}}',
        '{{%game_sale}}',
        '{{%game_search_stat}}',
        '{{%game_tag}}',
        '{{%metacritic}}',
        '{{%platform}}',
        '{{%review}}',
    ];

    public function safeUp()
    {
        $rows = (new Query())
            ->select([
                'id'         => 'g.id',
                'steam_appid' => 'g.steam_appid',
                'status'     => 'g.status',
                'has_slug'   => "(g.slug IS NOT NULL AND g.slug <> '')",
                'has_genre'  => 'EXISTS(SELECT 1 FROM {{%game_genre}} x WHERE x.game_id = g.id)',
                'has_dev'    => 'EXISTS(SELECT 1 FROM {{%game_developer}} x WHERE x.game_id = g.id)',
                'has_pub'    => 'EXISTS(SELECT 1 FROM {{%game_publisher}} x WHERE x.game_id = g.id)',
                'has_cat'    => 'EXISTS(SELECT 1 FROM {{%game_category}} x WHERE x.game_id = g.id)',
                'has_tag'    => 'EXISTS(SELECT 1 FROM {{%game_tag}} x WHERE x.game_id = g.id)',
                'has_review' => 'EXISTS(SELECT 1 FROM {{%review}} x WHERE x.game_id = g.id)',
                'has_meta'   => 'EXISTS(SELECT 1 FROM {{%metacritic}} x WHERE x.game_id = g.id)',
                'has_img'    => 'EXISTS(SELECT 1 FROM {{%game_image}} x WHERE x.game_id = g.id)',
            ])
            ->from('{{%game}} g')
            ->innerJoin(
                ['d' => (new Query())
                    ->select('steam_appid')
                    ->from('{{%game}}')
                    ->where(['not', ['steam_appid' => null]])
                    ->groupBy('steam_appid')
                    ->having('COUNT(*) > 1')],
                'd.steam_appid = g.steam_appid'
            )
            ->all($this->db);

        // Group rows by appid.
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['steam_appid']][] = $r;
        }

        $deleteIds = [];
        foreach ($groups as $appidRows) {
            usort($appidRows, [$this, 'compareKeepPriority']);
            // First row (after sort) is the keeper; the rest are discarded.
            array_shift($appidRows);
            foreach ($appidRows as $r) {
                $deleteIds[] = (int)$r['id'];
            }
        }

        if (!$deleteIds) {
            echo "    > no duplicate games to remove\n";
        } else {
            foreach (self::CHILD_TABLES as $table) {
                $this->delete($table, ['game_id' => $deleteIds]);
            }
            $this->delete('{{%game}}', ['id' => $deleteIds]);
            echo '    > removed ' . count($deleteIds) . " duplicate game rows (and their child rows)\n";
        }

        $this->createIndex(self::INDEX_NAME, '{{%game}}', 'steam_appid', true);
        echo "    > added UNIQUE index on game.steam_appid\n";
    }

    public function safeDown()
    {
        $this->dropIndex(self::INDEX_NAME, '{{%game}}');
        echo "    > dropped UNIQUE index on game.steam_appid\n";
        echo "    > deleted duplicate rows are not restored (irreversible)\n";
    }

    /**
     * Sort comparator: keeper rows sort first.
     * active > carries data > oldest id.
     */
    private function compareKeepPriority(array $a, array $b): int
    {
        $activeA = (int)$a['status'] === \common\models\Game::STATUS_ACTIVE ? 1 : 0;
        $activeB = (int)$b['status'] === \common\models\Game::STATUS_ACTIVE ? 1 : 0;
        if ($activeA !== $activeB) {
            return $activeB <=> $activeA;
        }

        $dataA = $this->carriesData($a);
        $dataB = $this->carriesData($b);
        if ($dataA !== $dataB) {
            return $dataB <=> $dataA;
        }

        return (int)$a['id'] <=> (int)$b['id'];
    }

    private function carriesData(array $r): int
    {
        foreach (['has_slug', 'has_genre', 'has_dev', 'has_pub', 'has_cat', 'has_tag', 'has_review', 'has_meta', 'has_img'] as $flag) {
            if ((int)$r[$flag] === 1) {
                return 1;
            }
        }
        return 0;
    }
}
