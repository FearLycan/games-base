<?php

namespace frontend\modules\developer\controllers;

use common\components\AccessControl;
use common\components\CompanyTimeline;
use common\models\Game;
use common\models\Publisher;
use frontend\components\Controller;
use frontend\modules\developer\models\Developer;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\PageCache;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DeveloperController extends Controller
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
        $model = Developer::find()->with('profile')->where(['slug' => $slug])->one();
        if (!$model) {
            throw new NotFoundHttpException('Developer not found.');
        }

        // If this name also exists as a publisher, the canonical page is /company/<slug>.
        if (Publisher::find()->where(['name' => $model->name])->exists()) {
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
            'kind'         => 'developer',
            'profile'      => $model->profile,
            'dataProvider' => $this->buildGamesProvider($model->id, $sort),
            'stats'        => $stats,
            'statCards'    => CompanyTimeline::statCards($stats, $model->profile, 'Games developed'),
            'roleLabels'   => ['Developer'],
            'roleTabs'     => [],
            'role'         => '',
            'gamesHeading' => 'Games developed',
            'timeline'     => CompanyTimeline::build($model->id, null, $model->profile, $stats['games']),
            'sort'         => $sort,
        ]);
    }

    public function actionIndex(): string
    {
        $sort = Yii::$app->request->get('sort', 'games');
        $country = Yii::$app->request->get('country');

        $query = Developer::find()
            ->alias('d')
            ->with('profile')
            ->where(['>', 'd.games_count', 0]);

        if ($country) {
            $query->innerJoin('{{%company_profile}} cp', 'cp.id = d.profile_id')
                ->andWhere(['cp.country' => $country]);
        }

        $query = match ($sort) {
            'name'    => $query->orderBy(['d.name' => SORT_ASC]),
            'newest'  => $query->orderBy(['d.id' => SORT_DESC]),
            default   => $query->orderBy(['d.games_count' => SORT_DESC, 'd.name' => SORT_ASC]),
        };

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 30],
        ]);

        $countries = (new Query())
            ->select('cp.country')
            ->distinct()
            ->from('{{%company_profile}} cp')
            ->innerJoin('{{%developer}} d', 'd.profile_id = cp.id')
            ->where(['not', ['cp.country' => null]])
            ->orderBy(['cp.country' => SORT_ASC])
            ->column();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'sort'         => $sort,
            'country'      => $country,
            'countries'    => $countries,
            'kind'         => 'developer',
            'kindPlural'   => 'developers',
        ]);
    }

    private function buildGamesProvider(int $developerId, string $sort): ActiveDataProvider
    {
        $query = Game::find()
            ->alias('game')
            ->innerJoin('{{%game_developer}} gd', 'gd.game_id = game.id')
            ->leftJoin('{{%review}} r', 'r.game_id = game.id')
            ->where([
                'gd.developer_id' => $developerId,
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
    private function buildStats(int $developerId): array
    {
        $games = (int)(new Query())
            ->from('{{%game_developer}} gd')
            ->innerJoin('{{%game}} g', 'g.id = gd.game_id')
            ->where(['gd.developer_id' => $developerId, 'g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
            ->count();

        $genreRows = (new Query())
            ->select(['genre.name', 'cnt' => 'COUNT(*)'])
            ->from('{{%game_developer}} gd')
            ->innerJoin('{{%game}} g', 'g.id = gd.game_id AND g.status = :s AND g.type = :t', [':s' => Game::STATUS_ACTIVE, ':t' => Game::TYPE_GAME])
            ->innerJoin('{{%game_genre}} gg', 'gg.game_id = g.id')
            ->innerJoin('{{%genre}} genre', 'genre.id = gg.genre_id')
            ->where(['gd.developer_id' => $developerId])
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
            ->from('{{%game_developer}} gd')
            ->innerJoin('{{%game}} g', 'g.id = gd.game_id AND g.status = :s AND g.type = :t', [':s' => Game::STATUS_ACTIVE, ':t' => Game::TYPE_GAME])
            ->innerJoin('{{%review}} r', 'r.game_id = g.id AND r.total_reviews > 50')
            ->where(['gd.developer_id' => $developerId])
            ->scalar();

        return [
            'games'          => $games,
            'topGenre'       => $breakdown[0]['name'] ?? null,
            'avgRating'      => $avg !== null && $avg !== false ? (int)round((float)$avg) : null,
            'genreBreakdown' => $breakdown,
        ];
    }
}
