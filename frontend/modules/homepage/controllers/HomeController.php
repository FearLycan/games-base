<?php

namespace frontend\modules\homepage\controllers;

use common\components\AccessControl;
use common\models\Game;
use common\models\GameSale;
use common\models\Genre;
use frontend\components\Controller;
use Yii;
use yii\caching\Cache;

class HomeController extends Controller
{
    private Cache $cache;

    public function __construct($id, $module, $config = [])
    {
        $this->cache = Yii::$app->cache;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access'    => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => [
                            'index',
                        ],
                        'roles'   => ['?'],
                    ],
                ],
            ],
            /*'pageCache' => [
                'class'    => 'yii\filters\PageCache',
                'only'     => ['index'],
                'duration' => YII_DEBUG ? 1 : 3600,
            ],*/
        ];
    }

    public function actionIndex()
    {
        $genres = Genre::find()
            ->orderBy(['games_count' => SORT_DESC, 'name' => SORT_ASC])
            ->limit(8)
            ->all();

        $bestsellers = Game::getSales(GameSale::TYPE_BESTSELLERS);
        $new_and_noteworthy = Game::getSales(GameSale::TYPE_NEW_AND_NOTEWORTHY);
        $popular_upcoming = Game::getSales(GameSale::TYPE_POPULAR_UPCOMING);

        $gamesCount = Game::count();
        $genresCount = $this->cache->getOrSet('genre.count', static fn(): int => (int)Genre::find()->count(), 86400);

        return $this->render('index', [
            'genres'             => $genres,
            'bestsellers'        => $bestsellers,
            'new_and_noteworthy' => $new_and_noteworthy,
            'popular_upcoming'   => $popular_upcoming,
            'gamesCount'         => $gamesCount,
            'genresCount'        => $genresCount,
        ]);
    }
}