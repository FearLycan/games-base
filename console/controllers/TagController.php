<?php

namespace console\controllers;

use common\models\Game;
use common\models\Tag;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class TagController extends Controller
{
    /**
     * Refreshes the precomputed games_count column on every tag row.
     * Counts active games of type=game linked to each tag via the game_tag
     * pivot table. Designed to run daily via cron, after steam/sync.
     */
    public function actionRecount(): int
    {
        $sql = '
            UPDATE {{%tag}} t
            SET t.games_count = (
                SELECT COUNT(DISTINCT gt.game_id)
                FROM {{%game_tag}} gt
                INNER JOIN {{%game}} ga ON ga.id = gt.game_id
                WHERE gt.tag_id = t.id
                  AND ga.status = :status
                  AND ga.type   = :type
            )
        ';

        $affected = Yii::$app->db->createCommand($sql, [
            ':status' => Game::STATUS_ACTIVE,
            ':type'   => Game::TYPE_GAME,
        ])->execute();

        $top = Tag::find()
            ->orderBy(['games_count' => SORT_DESC])
            ->limit(5)
            ->all();

        $this->stdout("Recounted {$affected} tag rows.\n");
        $this->stdout("Top tags:\n");
        foreach ($top as $tag) {
            $this->stdout(sprintf("  %s — %d games\n", $tag->name, $tag->games_count));
        }

        Yii::$app->cache->delete('tag.count');

        return ExitCode::OK;
    }
}
