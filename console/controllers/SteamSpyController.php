<?php

namespace console\controllers;

use common\models\Game;
use yii\base\Exception;
use yii\console\Controller;
use yii\helpers\VarDumper;
use yii\httpclient\Client;

class SteamSpyController extends Controller
{
    public $client;
    public $game;

    public function __construct($id, $module, $config = [])
    {
        $this->client = new Client(['baseUrl' => 'https://steamspy.com/api.php', 'transport' => 'yii\httpclient\CurlTransport']);
        $this->game = new Game();

        parent::__construct($id, $module, $config);
    }

    public function actionCreateAppList()
    {
        $page = 1;
        $repeat = true;
        do {
            echo "Strona {$page}\n";

            $request = $this->client->createRequest()
                ->setMethod('GET')
                ->setData(['request' => 'all', 'page' => $page]);

            $response = $request->send();

            if ($response->isOk) {

                foreach ($response->data as $game) {
                    $app = $this->game->createApp($game);

                    if ($app->status === Game::STATUS_WAIT_TO_SYNC) {
                        echo "Nowa gra " . $game['name'] . "\n";
                    }
                }

            } else {
                throw new Exception(
                    "Request to $request->url failed with response: \n"
                    . VarDumper::dumpAsString($response->data)
                );
            }

            $page++;
        } while ($repeat);
    }
}