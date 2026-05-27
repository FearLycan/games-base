<?php

namespace console\controllers;

use common\models\Game;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\VarDumper;
use yii\httpclient\Client;

class SteamSpyController extends Controller
{
    private const string API_URL = 'https://steamspy.com/api.php';

    private const int MAX_PAGES = 100;
    private const int REQUEST_DELAY_SECONDS = 60;

    public Client $client;

    public function __construct($id, $module, $config = [])
    {
        $this->client = new Client([
            'baseUrl'   => self::API_URL,
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        parent::__construct($id, $module, $config);
    }

    public function actionCreateAppList(): int
    {
        $existing = array_flip(Game::find()
            ->select('steam_appid')
            ->where(['is not', 'steam_appid', null])
            ->column()
        );

        $page = 0;
        $newCount = 0;

        while ($page < self::MAX_PAGES) {
            $this->stdout("Pobieranie strony {$page}\n");

            $request = $this->client->createRequest()
                ->setMethod('GET')
                ->setData(['request' => 'all', 'page' => $page]);

            $response = $request->send();

            if (!$response->isOk) {
                throw new Exception(
                    "Request to $request->url failed with response: \n"
                    . VarDumper::dumpAsString($response->data)
                );
            }

            if (empty($response->data)) {
                $this->stdout("Pusta strona — koniec listy.\n");
                break;
            }

            foreach ($response->data as $app) {
                if (isset($existing[$app['appid']])) {
                    continue;
                }

                $game = new Game();
                $game->steam_appid = (int)$app['appid'];
                $game->title = $app['name'] ?? null;

                if ($game->save()) {
                    $existing[$app['appid']] = true;
                    $newCount++;
                    $this->stdout("Nowa gra {$app['name']}\n");
                }
            }

            $page++;
            if ($page < self::MAX_PAGES) {
                sleep(self::REQUEST_DELAY_SECONDS);
            }
        }

        $this->stdout("Dodano nowych gier: {$newCount}\n");
        return ExitCode::OK;
    }
}
