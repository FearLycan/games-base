<?php

namespace frontend\modules\game\controllers;

use common\components\AccessControl;
use frontend\components\Controller;
use frontend\modules\game\models\Game;
use yii\web\NotFoundHttpException;

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
                            'view',
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