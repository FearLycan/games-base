<?php

namespace console\controllers;

use common\models\Game;
use common\models\Genre;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class GenreController extends Controller
{
    /**
     * Refreshes the precomputed games_count column on every genre row.
     * Counts active games of type=game linked to each genre via the
     * game_genre pivot table. Designed to run daily via cron.
     */
    public function actionRecount(): int
    {
        $sql = '
            UPDATE {{%genre}} g
            SET g.games_count = (
                SELECT COUNT(DISTINCT gg.game_id)
                FROM {{%game_genre}} gg
                INNER JOIN {{%game}} ga ON ga.id = gg.game_id
                WHERE gg.genre_id = g.id
                  AND ga.status = :status
                  AND ga.type   = :type
            )
        ';

        $affected = Yii::$app->db->createCommand($sql, [
            ':status' => Game::STATUS_ACTIVE,
            ':type'   => Game::TYPE_GAME,
        ])->execute();

        $top = Genre::find()
            ->orderBy(['games_count' => SORT_DESC])
            ->limit(5)
            ->all();

        $this->stdout("Recounted {$affected} genre rows.\n");
        $this->stdout("Top genres:\n");
        foreach ($top as $genre) {
            $this->stdout(sprintf("  %s — %d games\n", $genre->name, $genre->games_count));
        }

        Yii::$app->cache->delete('genre.count');

        return ExitCode::OK;
    }
}
