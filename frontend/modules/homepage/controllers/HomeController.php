<?php

namespace frontend\modules\homepage\controllers;

use common\components\AccessControl;
use common\models\GameGenre;
use frontend\components\Controller;

class HomeController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => [
                            'index',
                        ],
                        'roles' => ['?'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $genres = GameGenre::find()
            ->select('count(game_genre.genre_id) as count, game_genre.genre_id')
            ->groupBy('game_genre.genre_id')
            ->orderBy(['count' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('index', [
            'genres' => $genres,
        ]);
    }
}