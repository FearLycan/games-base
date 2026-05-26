<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Collapses duplicate company_profile rows that were created when the same
 * company exists in both {{%developer}} and {{%publisher}}. After this
 * migration each name maps to a single profile row, regardless of role.
 *
 * Strategy:
 *   1. For every (developer, publisher) pair with identical name, keep the
 *      developer's profile_id and point the publisher at the same row.
 *   2. Drop the now-orphaned profile rows (those referenced by neither table).
 */
class m260526_130000_dedupe_company_profiles_by_name extends Migration
{
    public function safeUp()
    {
        $pairs = (new Query())
            ->select([
                'pub_id'      => 'p.id',
                'pub_profile' => 'p.profile_id',
                'dev_profile' => 'd.profile_id',
            ])
            ->from('{{%publisher}} p')
            ->innerJoin('{{%developer}} d', 'd.name = p.name')
            ->where(['and',
                ['not', ['d.profile_id' => null]],
                ['or',
                    ['not', ['p.profile_id' => null]],
                    ['p.profile_id' => null],
                ],
            ])
            ->all($this->db);

        $orphanedProfileIds = [];
        $updated = 0;

        foreach ($pairs as $pair) {
            $devProfile = (int)$pair['dev_profile'];
            $pubProfile = $pair['pub_profile'] !== null ? (int)$pair['pub_profile'] : null;

            if ($pubProfile === $devProfile) {
                continue;
            }

            $this->update(
                '{{%publisher}}',
                ['profile_id' => $devProfile],
                ['id' => (int)$pair['pub_id']]
            );

            if ($pubProfile !== null) {
                $orphanedProfileIds[$pubProfile] = true;
            }

            $updated++;
        }

        echo "    > re-pointed {$updated} publisher rows to shared profile\n";

        if ($orphanedProfileIds) {
            $stillReferenced = (new Query())
                ->select('profile_id')
                ->from('{{%developer}}')
                ->where(['profile_id' => array_keys($orphanedProfileIds)])
                ->union(
                    (new Query())
                        ->select('profile_id')
                        ->from('{{%publisher}}')
                        ->where(['profile_id' => array_keys($orphanedProfileIds)])
                )
                ->column($this->db);

            $toDelete = array_diff(array_keys($orphanedProfileIds), array_map('intval', $stillReferenced));

            if ($toDelete) {
                $this->delete('{{%company_profile}}', ['id' => $toDelete]);
                echo "    > deleted " . count($toDelete) . " orphaned profile rows\n";
            }
        }
    }

    public function safeDown()
    {
        echo "    > dedupe is not automatically reversible; rerun the seed migration if needed.\n";
    }
}
