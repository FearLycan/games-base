<?php

namespace frontend\modules\publisher\controllers;

use common\components\AccessControl;
use common\components\CompanyTimeline;
use common\models\Developer;
use common\models\Game;
use frontend\components\Controller;
use frontend\modules\publisher\models\Publisher;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\PageCache;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PublisherController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => ['view', 'index'],
                        'roles'   => ['?'],
                    ],
                ],
            ],
            [
                'class'      => PageCache::class,
                'only'       => ['view', 'index'],
                'duration'   => YII_DEBUG ? 1 : 86400,
                'variations' => [
                    Yii::$app->controller->action->id
                    . Yii::$app->request->get('slug')
                    . Yii::$app->request->get('page')
                    . Yii::$app->request->get('sort')
                    . Yii::$app->request->get('country'),
                ],
            ],
        ];
    }

    public function actionView(string $slug): Response|string
    {
        $model = Publisher::find()->with('profile')->where(['slug' => $slug])->one();
        if (!$model) {
            throw new NotFoundHttpException('Publisher not found.');
        }

        if (Developer::find()->where(['name' => $model->name])->exists()) {
            return $this->redirect(
                ['/company/company/view', 'slug' => $slug] + Yii::$app->request->get(),
                301
            );
        }

        $sort = Yii::$app->request->get('sort', 'reviews');
        $stats = $this->buildStats($model->id);

        return $this->render('@frontend/views/company/_view', [
            'name'         => $model->name,
            'slug'         => $model->slug,
            'kind'         => 'publisher',
            'profile'      => $model->profile,
            'dataProvider' => $this->buildGamesProvider($model->id, $sort),
            'stats'        => $stats,
            'statCards'    => CompanyTimeline::statCards($stats, $model->profile, 'Games published'),
            'roleLabels'   => ['Publisher'],
            'roleTabs'     => [],
            'role'         => '',
            'gamesHeading' => 'Games published',
            'timeline'     => CompanyTimeline::build(null, $model->id, $model->profile, $stats['games']),
            'sort'         => $sort,
        ]);
    }

    public function actionIndex(): string
    {
        $sort = Yii::$app->request->get('sort', 'games');
        $country = Yii::$app->request->get('country');

        $query = Publisher::find()
            ->alias('p')
            ->with('profile')
            ->where(['>', 'p.games_count', 0])
            ->andWhere(['not', ['p.name' => null]])
            ->andWhere(['<>', 'p.name', '']);

        if ($country) {
            $query->innerJoin('{{%company_profile}} cp', 'cp.id = p.profile_id')
                ->andWhere(['cp.country' => $country]);
        }

        $query = match ($sort) {
            'name'    => $query->orderBy(['p.name' => SORT_ASC]),
            'newest'  => $query->orderBy(['p.id' => SORT_DESC]),
            default   => $query->orderBy(['p.games_count' => SORT_DESC, 'p.name' => SORT_ASC]),
        };

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 30],
        ]);

        $countries = (new Query())
            ->select('cp.country')
            ->distinct()
            ->from('{{%company_profile}} cp')
            ->innerJoin('{{%publisher}} p', 'p.profile_id = cp.id')
            ->where(['not', ['cp.country' => null]])
            ->orderBy(['cp.country' => SORT_ASC])
            ->column();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'sort'         => $sort,
            'country'      => $country,
            'countries'    => $countries,
            'kind'         => 'publisher',
            'kindPlural'   => 'publishers',
        ]);
    }

    private function buildGamesProvider(int $publisherId, string $sort): ActiveDataProvider
    {
        $query = Game::find()
            ->alias('game')
            ->innerJoin('{{%game_publisher}} gp', 'gp.game_id = game.id')
            ->leftJoin('{{%review}} r', 'r.game_id = game.id')
            ->where([
                'gp.publisher_id' => $publisherId,
                'game.status'     => Game::STATUS_ACTIVE,
                'game.type'       => Game::TYPE_GAME,
            ]);

        $query = match ($sort) {
            'newest' => $query->orderBy(['game.release_date' => SORT_DESC]),
            'oldest' => $query->orderBy(['game.release_date' => SORT_ASC]),
            'rating' => $query->andWhere(['>', 'r.total_reviews', 100])
                ->orderBy(['r.total_positive' => SORT_DESC]),
            default  => $query->orderBy(['r.total_reviews' => SORT_DESC]),
        };

        return new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 24],
        ]);
    }

    /**
     * @return array{games:int,topGenre:?string,avgRating:?int,genreBreakdown:array<int,array{name:string,count:int,percent:int}>}
     */
    private function buildStats(int $publisherId): array
    {
        $games = (int)(new Query())
            ->from('{{%game_publisher}} gp')
            ->innerJoin('{{%game}} g', 'g.id = gp.game_id')
            ->where(['gp.publisher_id' => $publisherId, 'g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
            ->count();

        $genreRows = (new Query())
            ->select(['genre.name', 'cnt' => 'COUNT(*)'])
            ->from('{{%game_publisher}} gp')
            ->innerJoin('{{%game}} g', 'g.id = gp.game_id AND g.status = :s AND g.type = :t', [':s' => Game::STATUS_ACTIVE, ':t' => Game::TYPE_GAME])
            ->innerJoin('{{%game_genre}} gg', 'gg.game_id = g.id')
            ->innerJoin('{{%genre}} genre', 'genre.id = gg.genre_id')
            ->where(['gp.publisher_id' => $publisherId])
            ->groupBy('genre.id, genre.name')
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(6)
            ->all();

        $totalForBreakdown = array_sum(array_column($genreRows, 'cnt')) ?: 1;
        $breakdown = array_map(static fn($row) => [
            'name'    => $row['name'],
            'count'   => (int)$row['cnt'],
            'percent' => (int)round(((int)$row['cnt'] / $totalForBreakdown) * 100),
        ], $genreRows);

        $avg = (new Query())
            ->select(new Expression('AVG(r.total_positive / NULLIF(r.total_reviews, 0) * 100)'))
            ->from('{{%game_publisher}} gp')
            ->innerJoin('{{%game}} g', 'g.id = gp.game_id AND g.status = :s AND g.type = :t', [':s' => Game::STATUS_ACTIVE, ':t' => Game::TYPE_GAME])
            ->innerJoin('{{%review}} r', 'r.game_id = g.id AND r.total_reviews > 50')
            ->where(['gp.publisher_id' => $publisherId])
            ->scalar();

        return [
            'games'          => $games,
            'topGenre'       => $breakdown[0]['name'] ?? null,
            'avgRating'      => $avg !== null && $avg !== false ? (int)round((float)$avg) : null,
            'genreBreakdown' => $breakdown,
        ];
    }
}
