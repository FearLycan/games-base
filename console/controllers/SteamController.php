<?php

namespace console\controllers;

use common\models\Game;
use yii\console\Controller;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\base\Exception;
use Symfony\Component\DomCrawler\Crawler;

class SteamController extends Controller
{
    public $client;
    public $game;

    public function __construct($id, $module, $config = [])
    {
        $this->client = new Client(['baseUrl' => 'https://store.steampowered.com/api/appdetails']);

        parent::__construct($id, $module, $config);
    }

    public function actionSync()
    {
        
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

        if ($response->isOk && $response->data[$app_id]['success']) {

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
            'start' => -50,
            'count' => 50,
            'dynamic_data',
            'sort_by' => '_ASC',
            'signore_preferences' => 1,
            'os' => 'win',
            'filter' => 'comingsoon',
            'infinite' => 1,
            'cc' => 'us'
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
            sleep(rand(5, 15));
        } while ($response->data['total_count'] > $start);
    }
}