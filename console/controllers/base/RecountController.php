<?php

namespace console\controllers\base;

use common\components\GamesCountRecounter;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Shared base for the per-entity `games_count` recount commands
 * (genre/recount, tag/recount, category/recount, developer/recount,
 * publisher/recount).
 *
 * Subclasses only declare which entity they target; the actual SQL lives in
 * {@see GamesCountRecounter}. Keeping the command split per entity preserves
 * the existing cron schedule while removing the duplicated UPDATE logic.
 */
abstract class RecountController extends Controller
{
    /** Entity key understood by {@see GamesCountRecounter::SPECS}. */
    abstract protected function entity(): string;

    public function actionRecount(): int
    {
        $entity     = $this->entity();
        $recounter  = new GamesCountRecounter();
        $affected   = $recounter->recount($entity);

        $this->stdout("Recounted {$affected} {$entity} rows.\n");
        $this->stdout("Top {$entity}s:\n");
        foreach ($recounter->topRows($entity) as $row) {
            $this->stdout(sprintf("  %s — %d games\n", $row->name, $row->games_count));
        }

        return ExitCode::OK;
    }
}
