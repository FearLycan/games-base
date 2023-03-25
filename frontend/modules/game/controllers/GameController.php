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
use yii\web\BadRequestHttpException;
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
                            'view', 'search-list', 'list', 'details'
                        ],
                        'roles' => ['?'],
                    ],
                ],
            ],
            [
                'class' => 'yii\filters\PageCache',
                'only' => ['view', 'details'],
                'duration' => YII_DEBUG ? 1 : 3600,
                'variations' => [
                    Yii::$app->controller->action->id . Yii::$app->request->get('id')
                ]
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
        $model = $this->findModel($id, $slug);

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
                    ->where("game_id = game.id AND type = 'header'")
                    ->limit(1),
            ])
            ->alias('game')
            ->onlyWithTitle($phrase)
            ->andWhere(['status' => Game::STATUS_ACTIVE])
            ->joinWith(['review'])
            ->orderBy(['review.total_reviews' => SORT_DESC, 'game.title' => SORT_ASC])
            ->limit(10)
            ->asArray()
            ->all();
    }

    public function actionDetails($id)
    {
        if (Yii::$app->request->isAjax) {
            $model = $this->findModelByMainId($id);
            return $this->renderPartial('_right-bar', ['model' => $model, 'gameViewButton' => true]);
        }

        throw new BadRequestHttpException();
    }

    /**
     * @param $id
     * @return Game|null
     * @throws NotFoundHttpException
     */
    protected function findModel($id, $slug)
    {
        if (($model = Game::findOne([
                'steam_appid' => $id,
                'slug' => $slug
            ])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * @param $id
     * @return Game|null
     * @throws NotFoundHttpException
     */
    protected function findModelByMainId($id)
    {
        if (($model = Game::findOne(['id' => $id])) !== null) {
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