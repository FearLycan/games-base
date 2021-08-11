<?php

namespace console\controllers;

use common\models\Game;
use yii\console\Controller;
use yii\helpers\VarDumper;
use yii\httpclient\Client;
use yii\base\Exception;

class SteamController extends Controller
{
    public $client;
    public $game;

    public function __construct($id, $module, $config = [])
    {   //https://store.steampowered.com/api/appdetails?appids=1687270
        $this->client = new Client(['baseUrl' => 'https://store.steampowered.com/api/appdetails']);
        //$this->game = new Game();

        parent::__construct($id, $module, $config);
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
            ->setData(['appids' => $app_id]);

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
}