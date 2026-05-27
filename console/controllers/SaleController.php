<?php

namespace console\controllers;

use common\models\Game;
use common\models\GameSale;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\httpclient\Client;

class SaleController extends Controller
{
    private const string SEARCH_URL = 'https://store.steampowered.com/search/results/';

    private const int PAGE_SIZE    = 50;
    private const int MAX_PAGES    = 5;
    private const int DELAY_MIN    = 5;
    private const int DELAY_MAX    = 15;
    private const int MAX_RETRIES  = 3;
    private const int BACKOFF_BASE = 5;

    public function actionSynchronize(): int
    {
        foreach (GameSale::getSteamFilters() as $type => $filter) {
            $this->stdout("=== Sync type={$type} filter={$filter} ===\n");

            try {
                $appids = $this->fetchOrderedAppids($filter);
            } catch (Throwable $e) {
                $this->stderr("fetch failed for type={$type}: {$e->getMessage()} — keeping existing data\n");
                continue;
            }

            if (!$appids) {
                $this->stderr("no appids for type={$type} — keeping existing data\n");
                continue;
            }

            try {
                $this->replaceSales($type, $appids);
                $this->stdout("saved " . count($appids) . " entries for type={$type}\n");
            } catch (Throwable $e) {
                $this->stderr("persist failed for type={$type}: {$e->getMessage()}\n");
            }
        }

        return ExitCode::OK;
    }

    /**
     * Pulls up to MAX_PAGES × PAGE_SIZE appids from Steam search, preserving
     * Steam's ranking. Throws on any page error — caller treats this as
     * "skip this type, keep previous data".
     *
     * @return int[] unique appids in display order
     */
    private function fetchOrderedAppids(string $filter): array
    {
        $client = new Client();
        $appids = [];
        $seen = [];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $start = $page * self::PAGE_SIZE;
            $this->stdout("  page={$page} start={$start}\n");

            $data = $this->requestPageWithRetry($client, $filter, $start);

            $crawler = new Crawler($data['results_html'] ?? '');
            $groups = $crawler->filter('a[data-ds-appid]')->extract(['data-ds-appid']);

            foreach ($groups as $group) {
                foreach (explode(',', $group) as $raw) {
                    $appid = (int)trim($raw);
                    if ($appid <= 0 || isset($seen[$appid])) {
                        continue;
                    }
                    $seen[$appid] = true;
                    $appids[] = $appid;
                }
            }

            if ($page + 1 < self::MAX_PAGES) {
                sleep(random_int(self::DELAY_MIN, self::DELAY_MAX));
            }
        }

        return $appids;
    }

    /**
     * One page fetch with exponential backoff retries.
     * Retries on network errors, non-2xx responses, and Steam-side success=false.
     * Throws once retries are exhausted — caller (fetchOrderedAppids) propagates
     * the throw so actionSynchronize can skip the type and keep the old data.
     *
     * @return array decoded JSON response
     */
    private function requestPageWithRetry(Client $client, string $filter, int $start): array
    {
        $lastError = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                $delay = self::BACKOFF_BASE * (2 ** ($attempt - 1));
                $this->stdout("    retry {$attempt}/" . self::MAX_RETRIES . " in {$delay}s ({$lastError})\n");
                sleep($delay);
            }

            try {
                $response = $client->createRequest()
                    ->setUrl(self::SEARCH_URL)
                    ->setHeaders(['Content-language' => 'en'])
                    ->setMethod('GET')
                    ->setData([
                        'query'              => '',
                        'start'              => $start,
                        'count'              => self::PAGE_SIZE,
                        'dynamic_data'       => '',
                        'ignore_preferences' => 1,
                        'os'                 => 'win',
                        'filter'             => $filter,
                        'infinite'           => 1,
                        'cc'                 => 'us',
                        'supportedlang'      => 'english',
                    ])
                    ->send();
            } catch (Throwable $e) {
                $lastError = "network error: {$e->getMessage()}";
                continue;
            }

            if (!$response->isOk || !is_array($response->data) || empty($response->data['success'])) {
                $lastError = "HTTP {$response->statusCode}";
                continue;
            }

            return $response->data;
        }

        throw new \RuntimeException("Steam search failed at start={$start} after " . self::MAX_RETRIES . " retries: {$lastError}");
    }

    /**
     * Atomic swap: delete all sales of $type and batch-insert fresh ranked rows.
     * Creates Game placeholders for unknown appids before opening the transaction.
     *
     * @param int[] $appids in display order (first = order 1)
     */
    private function  replaceSales(int $type, array $appids): void
    {
        $idMap = array_column(
            Game::find()
                ->select(['id', 'steam_appid'])
                ->where(['steam_appid' => $appids])
                ->asArray()
                ->all(),
            'id',
            'steam_appid'
        );

        foreach ($appids as $appid) {
            if (isset($idMap[$appid])) {
                continue;
            }
            $game = new Game();
            $game->steam_appid = $appid;
            if ($game->save(false)) {
                $idMap[$appid] = $game->id;
            } else {
                $this->stderr("could not create Game placeholder for appid={$appid}\n");
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            GameSale::deleteAll(['type' => $type]);

            $now = date('Y-m-d H:i:s');
            $rows = [];
            $order = 1;
            foreach ($appids as $appid) {
                if (!isset($idMap[$appid])) {
                    continue;
                }
                $rows[] = [$idMap[$appid], $type, $order++, $now];
            }

            if ($rows) {
                Yii::$app->db->createCommand()
                    ->batchInsert(
                        GameSale::tableName(),
                        ['game_id', 'type', 'order', 'created_at'],
                        $rows
                    )
                    ->execute();
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
