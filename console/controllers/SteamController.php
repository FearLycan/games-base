<?php

namespace console\controllers;

use common\models\Game;
use Yii;
use yii\console\Controller;
use yii\db\Expression;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\base\Exception;
use Symfony\Component\DomCrawler\Crawler;

class SteamController extends Controller
{
    public Client $client;
    public Game   $game;

    public function __construct($id, $module, $config = [])
    {
        $this->client = new Client(['baseUrl' => 'https://store.steampowered.com/api/appdetails']);
        $this->game = new Game();
        parent::__construct($id, $module, $config);
    }

    public function actionSync($limit = 0)
    {
        $games = Game::find()
            ->orWhere(['status' => Game::STATUS_WAIT_TO_SYNC])
            ->orWhere(['force_sync' => true]);
            //->orWhere(['<=', 'synchronized_at', new Expression('NOW()')]);

        if ($limit) {
            $games->limit = $limit;
        }

        foreach ($games->each() as $game) {
            /** @var Game $game */
            try {
                $this->actionGetInfo($game->steam_appid);
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    public function actionGetInfo($app_id)
    {
        $game = Game::findOne(['steam_appid' => $app_id]);

        if (!$game) {
            echo "no game with id  $app_id \n";
            exit();
        }

        $request = $this->client->createRequest()
            ->setHeaders(['Content-language' => 'en'])
            ->setMethod('GET')
            ->setData(['appids' => $app_id, 'cc' => 'us']);

        $response = $request->send();

        if ($response->isOk) {
            if (!isset($response->data[$app_id]) || (isset($response->data[$app_id]) && $response->data[$app_id]['success'] === false)) {
                $game->status = Game::STATUS_SUCCESS_FALSE;
                $game->save(false);
                return;
            }

            $game->setBaseInformation($response->data[$app_id]['data']);
        } else {
            throw new Exception(
                "Request to $request->url failed with response: \n"
                . VarDumper::dumpAsString($response->data)
            );
        }
    }

    public function actionGetComingSoon()
    {
        $this->client = new Client(['baseUrl' => 'https://store.steampowered.com/search/results/?query']);

        $start = 0;
        $query = [
            'query',
            'start'               => -50,
            'count'               => 50,
            'dynamic_data',
            'sort_by'             => '_ASC',
            'signore_preferences' => 1,
            'os'                  => 'win',
            'filter'              => 'comingsoon',
            'infinite'            => 1,
            'cc'                  => 'us',
        ];

        do {
            $start += 50;
            $query['start'] = $start;

            $request = $this->client->createRequest()
                ->setHeaders(['Content-language' => 'en'])
                ->setMethod('GET')
                ->setData($query);

            $response = $request->send();

            if ($response->isOk && $response->data['success']) {
                $crawler = new Crawler($response->data['results_html']);

                $appids = $crawler->filter('a[data-ds-appid]')->extract(['data-ds-appid']);

                foreach ($appids as $appid) {
                    $game = Game::findOne(['steam_appid' => $appid]);
                    echo $appid . "\n";
                    if (!$game) {
                        $game = new Game();
                        $game->steam_appid = $appid;
                        $game->save(false);
                    }
                }
            }
            sleep(random_int(5, 15));
        } while ($response->data['total_count'] > $start);
    }

    public function actionCreateAppList()
    {
        $this->client = new Client(['baseUrl' => 'http://api.steampowered.com/ISteamApps/GetAppList/v0002']);

        $request = $this->client->createRequest()
            ->setMethod('GET')
            ->setData([
                'key'    => Yii::$app->params['steamkey'],
                'format' => 'json',
            ]);

        $response = $request->send();

        if ($response->isOk) {
            foreach ($response->data['applist']['apps'] as $game) {
                $app = $this->game->createApp($game);

                if ($app->isNewRecord) {
                    echo "Nowa gra " . $game['name'] . "\n";
                }
            }
        }
    }
}