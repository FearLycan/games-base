<?php

namespace console\controllers;

use common\components\CompanyProfileImporter;
use common\models\Developer;
use common\models\Publisher;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\FileHelper;

/**
 * Watches a drop folder for *.json files and imports company profiles from them.
 *
 * Designed for a short cron (e.g. every 5 minutes): each file is read once and
 * then deleted on success (or moved to failed/ if it can't be parsed), so
 * re-runs only pick up newly dropped files. Each record is imported in its own
 * transaction, so one bad row never aborts the rest of the file.
 */
class ImportController extends Controller
{
    /** Override the watched directory (path or Yii alias). */
    public ?string $dir = null;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['dir']);
    }

    /**
     * Runs every import in turn. This is the entry point to schedule on cron;
     * add new feeds here as they appear so the schedule never has to change.
     */
    public function actionAll(): int
    {
        $this->actionDevelopers();
        $this->actionPublishers();

        return ExitCode::OK;
    }

    public function actionDevelopers(): int
    {
        return $this->scanDir('@console/data/import/developers', Developer::class, 'developer');
    }

    public function actionPublishers(): int
    {
        return $this->scanDir('@console/data/import/publishers', Publisher::class, 'publisher');
    }

    /**
     * @param class-string<Developer|Publisher> $modelClass
     */
    private function scanDir(string $defaultDir, string $modelClass, string $kind): int
    {
        $dir = Yii::getAlias($this->dir ?? $defaultDir);

        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir);
            $this->stdout("Created watch dir (drop *.json here): {$dir}\n");
            return ExitCode::OK;
        }

        $files = glob($dir . '/*.json') ?: [];
        if (!$files) {
            return ExitCode::OK; // nothing to do — stay silent for cron
        }

        $failedDir = $dir . '/failed';

        foreach ($files as $file) {
            $this->importFile($file, $modelClass, $kind, $failedDir);
        }

        return ExitCode::OK;
    }

    /**
     * @param class-string<Developer|Publisher> $modelClass
     */
    private function importFile(string $file, string $modelClass, string $kind, string $failedDir): void
    {
        $base = basename($file);
        $data = json_decode((string)file_get_contents($file), true);

        if (!is_array($data)) {
            $this->stderr("[{$base}] invalid JSON — moving to failed/\n");
            $this->moveToFailed($file, $failedDir);
            return;
        }

        // Accept a bare array or an envelope: { "version": 1, "items": [...] }.
        $items = array_is_list($data) ? $data : ($data['items'] ?? null);
        if (!is_array($items)) {
            $this->stderr("[{$base}] no list of records found — moving to failed/\n");
            $this->moveToFailed($file, $failedDir);
            return;
        }

        $created = $updated = $skipped = 0;
        foreach ($items as $i => $row) {
            if (!is_array($row)) {
                $skipped++;
                $this->stderr("  - row #{$i}: not an object, skipped\n");
                continue;
            }

            $tx = Yii::$app->db->beginTransaction();
            try {
                $result = CompanyProfileImporter::import($row, $modelClass);
                $tx->commit();

                match ($result['action']) {
                    'created' => $created++,
                    'updated' => $updated++,
                    default   => $skipped++,
                };
                if ($result['action'] === 'skipped') {
                    $this->stdout("  - skip {$result['name']}: " . ($result['error'] ?? '') . "\n");
                }
            } catch (\Throwable $e) {
                $tx->rollBack();
                $skipped++;
                $this->stderr("  - row #{$i}: {$e->getMessage()}\n");
            }
        }

        $this->stdout(sprintf("[%s] %s — created=%d updated=%d skipped=%d\n", $base, $kind, $created, $updated, $skipped));

        // Processed successfully — delete so the cron doesn't reprocess it.
        if (!@unlink($file)) {
            $this->stderr("  ! could not delete " . $base . " after import\n");
        }
    }

    private function moveToFailed(string $file, string $failedDir): void
    {
        FileHelper::createDirectory($failedDir);
        $dest = $failedDir . '/' . date('Ymd-His') . '-' . basename($file);
        if (!@rename($file, $dest)) {
            $this->stderr("  ! could not move " . basename($file) . " to " . $failedDir . "\n");
        }
    }
}
