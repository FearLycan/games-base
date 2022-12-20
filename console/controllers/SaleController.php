<?php

namespace console\controllers;

use common\models\Game;
use common\models\GameSale;
use Symfony\Component\DomCrawler\Crawler;
use yii\console\Controller;
use yii\httpclient\Client;

class SaleController extends Controller
{
    public $client;
    public $game;

    public function __construct($id, $module, $config = [])
    {
        $this->client = new Client();
        $this->game = new Game();

        parent::__construct($id, $module, $config);
    }

    public function actionSynchronize()
    {
        foreach (GameSale::getSteamFilters() as $type => $filter) {

            GameSale::deleteAll(['type' => $type]);

            $url = 'https://store.steampowered.com/search/results';

            //      https://store.steampowered.com/search/results?sort_by=Price_ASC&force_infinite=1&supportedlang=english&filter=topsellers&ndl=1&snr=1_7_7_7000_7
            //      https://store.steampowered.com/search/results/?query&start=0&count=50&dynamic_data=&force_infinite=1&supportedlang=english&filter=topsellers&ndl=1&snr=1_7_7_7000_7&infinite=1
            $data = [
                'query',
                'start' => 0,
                'count' => 50,
                'dynamic_data',
                'sort_by' => '_ASC',
                'signore_preferences' => 1,
                'os' => 'win',
                'filter' => $filter,
                'infinite' => 1,
                'cc' => 'us',
                'supportedlang' => 'english'
            ];

            $order = 1;

            for ($i = 50; $i < 300; $i += 50) {
                $request = $this->client->createRequest()
                    ->setUrl($url)
                    ->setHeaders(['Content-language' => 'en'])
                    ->setData($data)
                    ->setMethod('GET');

                $response = $request->send();

                if ($response->isOk && $response->data['success']) {
                    $crawler = new Crawler($response->data['results_html']);

                    $appids = $crawler->filter('a[data-ds-appid]')->extract(['data-ds-appid']);

                    foreach ($appids as $appid) {

                        $ids = explode(',', $appid);

                        foreach ($ids as $id) {
                            $game = Game::findOne(['steam_appid' => $id]);

                            if (!$game) {
                                $game = new Game();
                                $game->steam_appid = $id;
                                $game->save(false);
                            }

                            $sale = GameSale::findOne([
                                'game_id' => $game->id,
                                'type' => $type,
                            ]);

                            if (!$sale) {
                                $sale = new GameSale();
                                $sale->game_id = $game->id;
                                $sale->type = $type;
                                $sale->order = $order;
                                $sale->save();

                                $order++;
                            }
                        }

                    }
                }
                $data['start'] = $i;

                sleep(rand(5, 15));
            }
        }
    }
}