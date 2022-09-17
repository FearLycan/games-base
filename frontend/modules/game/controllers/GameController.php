<?php

namespace frontend\modules\game\controllers;

use common\components\AccessControl;
use common\models\Category;
use common\models\GameImage;
use common\models\Genre;
use frontend\components\Controller;
use frontend\modules\game\models\Game;
use frontend\modules\game\models\searches\GameSearch;
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
                            'view', 'search-list', 'list'
                        ],
                        'roles' => ['?'],
                    ],
                ],
            ],
        ];
    }

    public function actionList($slug)
    {
        $model = $this->findCategoryOrGenre($slug);

        $searchModel = new GameSearch();

        if ($model instanceof Category) {
            $searchModel->category_id = $model->id;
        } elseif ($model instanceof Genre) {
            $searchModel->genre_id = $model->id;
        } else {
            $this->notFound();
        }

        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('list', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);

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
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (strlen($phrase) < 3) {
            return [];
        }

        return Game::find()
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
            ->joinWith(['review'])
            ->orderBy(['review.total_reviews' => SORT_DESC, 'game.title' => SORT_ASC])
            ->limit(10)
            ->asArray()
            ->all();
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

    protected function findCategoryOrGenre($slug)
    {
        $category = Category::findOne(['slug' => $slug]);

        if (!$category) {
            $genre = Genre::findOne(['slug' => $slug]);
            if ($genre) {
                return $genre;
            }

            throw new NotFoundHttpException('The requested page does not exist.');
        } else {
            return $category;
        }
    }

}