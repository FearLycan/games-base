<?php

namespace frontend\modules\game\controllers;

use common\components\AccessControl;
use common\models\GameImage;
use frontend\components\Controller;
use frontend\modules\game\models\Game;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class GameController extends Controller
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
                            'view', 'search-list'
                        ],
                        'roles' => ['?'],
                    ],
                ],
            ],
        ];
    }

    public function actionView($id, $slug)
    {
        $model = $this->findModel($id);

        if ($model->slug != $slug) {
            return $this->redirect(['view', 'id' => $model->steam_appid, 'slug' => $model->slug]);
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionSearchList($phrase)
    {
        $games = Game::find()
            ->select([
                'game.steam_appid as id',
                'game.title as title',
                'game.slug as slug',
                'url' => GameImage::find()
                    ->select('url')
                    ->where("game_id = game.id AND type = 'header'"),
            ])
            ->alias('game')
            ->onlyWithTitle($phrase)
            ->orderBy(['game.title' => SORT_ASC])
            ->limit(10)
            ->asArray()
            ->all();

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $games;
    }

    /**
     * @param $id
     * @return Game|null
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Game::findOne(['steam_appid' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}