<?php

namespace console\controllers;

use common\models\Game;
use Symfony\Component\DomCrawler\Crawler;
use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\VarDumper;
use yii\httpclient\Client;

class SteamController extends Controller
{
    private const string APPDETAILS_URL = 'https://store.steampowered.com/api/appdetails';
    private const string SEARCH_URL = 'https://store.steampowered.com/search/results/';
    private const string APP_LIST_URL = 'http://api.steampowered.com/ISteamApps/GetAppList/v0002';

    private const int SYNC_DELAY_MIN = 2;
    private const int SYNC_DELAY_MAX = 4;
    private const int SEARCH_DELAY_MIN = 5;
    private const int SEARCH_DELAY_MAX = 15;
    private const int SEARCH_PAGE_SIZE = 50;
    private const int SEARCH_MAX_PAGES = 200;

    public function actionSync(int $limit = 0): int
    {
        $query = Game::find()->where([
            'or',
            ['status' => Game::STATUS_WAIT_TO_SYNC],
            ['force_sync' => 1],
        ])->orderBy(['force_sync' => SORT_DESC, 'id' => SORT_DESC]);

        $iterator = $limit > 0
            ? $query->limit($limit)->all()
            : $query->each();

        foreach ($iterator as $game) {
            /** @var Game $game */
            try {
                $this->stdout("Syncing {$game->steam_appid}\n");
                $this->actionGetInfo($game->steam_appid);
            } catch (Exception $e) {
                $this->stderr($e->getMessage() . " - appid: {$game->steam_appid}\n");
            }
            sleep(random_int(self::SYNC_DELAY_MIN, self::SYNC_DELAY_MAX));
        }

        return ExitCode::OK;
    }

    public function actionGetInfo(int $app_id): int
    {
        $game = Game::findOne(['steam_appid' => $app_id]);

        if (!$game) {
            $this->stderr("no game with id $app_id\n");
            return ExitCode::DATAERR;
        }

        $client = new Client(['baseUrl' => self::APPDETAILS_URL]);
        $request = $client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET')
            ->setData(['appids' => $app_id, 'cc' => 'us']);

        $response = $request->send();

        if (!$response->isOk) {
            throw new Exception(
                "Request to $request->url failed with response: \n"
                . VarDumper::dumpAsString($response->data)
            );
        }

        if (empty($response->data[$app_id]['success'])) {
            $game->status = Game::STATUS_SUCCESS_FALSE;
            $game->save(false);
            return ExitCode::OK;
        }

        $game->setBaseInformation($response->data[$app_id]['data']);
        return ExitCode::OK;
    }

    public function actionGetComingSoon(): int
    {
        $client = new Client(['baseUrl' => self::SEARCH_URL]);

        $start = 0;
        $page = 0;
        $query = [
            'query'              => '',
            'count'              => self::SEARCH_PAGE_SIZE,
            'dynamic_data'       => '',
            'sort_by'            => 'Released_ASC',
            'ignore_preferences' => 1,
            'os'                 => 'win',
            'filter'             => 'comingsoon',
            'infinite'           => 1,
            'cc'                 => 'us',
        ];

        do {
            $query['start'] = $start;

            $request = $client->createRequest()
                ->setHeaders(['Content-language' => 'en'])
                ->setMethod('GET')
                ->setData($query);

            $response = $request->send();

            if (!$response->isOk || empty($response->data['success'])) {
                $this->stderr("comingsoon request failed at start={$start}\n");
                break;
            }

            $crawler = new Crawler($response->data['results_html']);
            $appidGroups = $crawler->filter('a[data-ds-appid]')->extract(['data-ds-appid']);

            foreach ($appidGroups as $group) {
                foreach (explode(',', $group) as $appid) {
                    $appid = trim($appid);
                    if ($appid === '') {
                        continue;
                    }
                    $this->stdout("$appid\n");

                    if (!Game::find()->where(['steam_appid' => $appid])->exists()) {
                        $game = new Game();
                        $game->steam_appid = (int)$appid;
                        $game->save(false);
                    }
                }
            }

            $total = (int)($response->data['total_count'] ?? 0);
            $start += self::SEARCH_PAGE_SIZE;
            $page++;

            if ($page >= self::SEARCH_MAX_PAGES) {
                $this->stderr("hit SEARCH_MAX_PAGES safety cap at start={$start}, total={$total}\n");
                break;
            }

            if ($start < $total) {
                sleep(random_int(self::SEARCH_DELAY_MIN, self::SEARCH_DELAY_MAX));
            }
        } while ($start < $total);

        $controller = new GenreController(Yii::$app->controller->id, Yii::$app);
        $controller->actionRecount();

        $controller = new TagController(Yii::$app->controller->id, Yii::$app);
        $controller->actionRecount();

        return ExitCode::OK;
    }

    public function actionCreateAppList(): int
    {
        $client = new Client(['baseUrl' => self::APP_LIST_URL]);

        $request = $client->createRequest()
            ->setMethod('GET')
            ->setData([
                'key'    => Yii::$app->params['steamkey'],
                'format' => 'json',
            ]);

        $response = $request->send();

        if (!$response->isOk) {
            $this->stderr("GetAppList request failed\n");
            return ExitCode::TEMPFAIL;
        }

        $existing = array_flip(Game::find()
            ->select('steam_appid')
            ->where(['is not', 'steam_appid', null])
            ->column()
        );
        $newCount = 0;

        foreach ($response->data['applist']['apps'] as $app) {
            if (isset($existing[$app['appid']])) {
                continue;
            }

            $game = new Game();
            $game->steam_appid = (int)$app['appid'];
            $game->title = $app['name'];

            if ($game->save()) {
                $existing[$app['appid']] = true;
                $newCount++;
                $this->stdout("Nowa gra {$app['name']}\n");
            }
        }

        $this->stdout("Dodano nowych gier: {$newCount}\n");
        return ExitCode::OK;
    }
}