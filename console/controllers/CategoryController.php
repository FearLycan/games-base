<?php

namespace console\controllers;

use common\models\Category;
use common\models\Game;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class CategoryController extends Controller
{
    /**
     * Refreshes the precomputed games_count column on every category row.
     * Counts active games of type=game linked to each category via the
     * game_category pivot table. Designed to run daily via cron, after steam/sync.
     */
    public function actionRecount(): int
    {
        $sql = '
            UPDATE {{%category}} c
            SET c.games_count = (
                SELECT COUNT(DISTINCT gc.game_id)
                FROM {{%game_category}} gc
                INNER JOIN {{%game}} ga ON ga.id = gc.game_id
                WHERE gc.category_id = c.id
                  AND ga.status = :status
                  AND ga.type   = :type
            )
        ';

        $affected = Yii::$app->db->createCommand($sql, [
            ':status' => Game::STATUS_ACTIVE,
            ':type'   => Game::TYPE_GAME,
        ])->execute();

        $top = Category::find()
            ->orderBy(['games_count' => SORT_DESC])
            ->limit(5)
            ->all();

        $this->stdout("Recounted {$affected} category rows.\n");
        $this->stdout("Top features:\n");
        foreach ($top as $category) {
            $this->stdout(sprintf("  %s — %d games\n", $category->name, $category->games_count));
        }

        Yii::$app->cache->delete('category.count');

        return ExitCode::OK;
    }
}
