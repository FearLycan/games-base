<?php

namespace frontend\modules\company\controllers;

use common\components\AccessControl;
use common\components\CompanyTimeline;
use common\models\Developer;
use common\models\Game;
use common\models\Publisher;
use frontend\components\Controller;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\PageCache;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CompanyController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => ['view'],
                        'roles'   => ['?', '@'],
                    ],
                ],
            ],
            [
                'class'      => PageCache::class,
                'only'       => ['view'],
                'duration'   => YII_DEBUG ? 1 : 86400,
                'variations' => [
                    Yii::$app->request->get('slug')
                    . Yii::$app->request->get('role')
                    . Yii::$app->request->get('sort')
                    . Yii::$app->request->get('page'),
                ],
            ],
        ];
    }

    public function actionView(string $slug): Response|string
    {
        $developer = Developer::find()->with('profile')->where(['slug' => $slug])->one();
        $publisher = Publisher::find()->with('profile')->where(['slug' => $slug])->one();

        if (!$developer && !$publisher) {
            throw new NotFoundHttpException('Company not found.');
        }

        // Single-role companies have their own canonical page under /developer or /publisher.
        if ($developer && !$publisher && !Publisher::find()->where(['name' => $developer->name])->exists()) {
            return $this->redirect(['/developer/developer/view', 'slug' => $slug] + Yii::$app->request->get(), 301);
        }
        if ($publisher && !$developer && !Developer::find()->where(['name' => $publisher->name])->exists()) {
            return $this->redirect(['/publisher/publisher/view', 'slug' => $slug] + Yii::$app->request->get(), 301);
        }

        $profile = $developer?->profile ?? $publisher?->profile;
        $name = $developer?->name ?? $publisher?->name;

        $role = Yii::$app->request->get('role', $developer && $publisher ? 'all' : ($developer ? 'developed' : 'published'));
        $sort = Yii::$app->request->get('sort', 'reviews');

        $stats = $this->buildStats($developer?->id, $publisher?->id);
        $stats['games'] = $stats['totalCount']; // normalized total for the shared view

        $roleLabels = [];
        $statCards = [];
        if ($developer) {
            $roleLabels[] = 'Developer';
            $statCards[] = ['value' => number_format($stats['developedCount']), 'label' => 'Games developed'];
        }
        if ($publisher) {
            $roleLabels[] = 'Publisher';
            $statCards[] = ['value' => number_format($stats['publishedCount']), 'label' => 'Games published'];
        }
        if ($stats['topGenre']) {
            $statCards[] = ['value' => $stats['topGenre'], 'label' => 'Most common genre', 'accent' => true];
        }
        if ($stats['avgRating'] !== null) {
            $statCards[] = ['value' => $stats['avgRating'] . '%', 'label' => 'Avg. Steam rating'];
        }

        $roleTabs = [];
        if ($developer && $publisher) {
            $roleTabs['all']       = ['All games', $stats['totalCount']];
            $roleTabs['developed'] = ['Developed', $stats['developedCount']];
            $roleTabs['published'] = ['Published', $stats['publishedCount']];
        }

        return $this->render('@frontend/views/company/_view', [
            'name'         => $name,
            'slug'         => $slug,
            'kind'         => 'company',
            'profile'      => $profile,
            'dataProvider' => $this->buildGamesProvider($developer?->id, $publisher?->id, $role, $sort),
            'stats'        => $stats,
            'statCards'    => $statCards,
            'roleLabels'   => $roleLabels,
            'roleTabs'     => $roleTabs,
            'role'         => $role,
            'gamesHeading' => 'Games',
            'timeline'     => CompanyTimeline::build($developer?->id, $publisher?->id, $profile, $stats['totalCount']),
            'sort'         => $sort,
        ]);
    }

    private function buildGamesProvider(?int $developerId, ?int $publisherId, string $role, string $sort): ActiveDataProvider
    {
        $query = Game::find()
            ->alias('game')
            ->leftJoin('{{%review}} r', 'r.game_id = game.id')
            ->where(['game.status' => Game::STATUS_ACTIVE, 'game.type' => Game::TYPE_GAME])
            ->hideAdultCatalog('game');

        $devExists = $developerId !== null;
        $pubExists = $publisherId !== null;

        if ($role === 'developed' && $devExists) {
            $query->andWhere(['game.id' => (new Query())
                ->select('game_id')
                ->from('{{%game_developer}}')
                ->where(['developer_id' => $developerId])]);
        } elseif ($role === 'published' && $pubExists) {
            $query->andWhere(['game.id' => (new Query())
                ->select('game_id')
                ->from('{{%game_publisher}}')
                ->where(['publisher_id' => $publisherId])]);
        } else {
            $conditions = ['or'];
            if ($devExists) {
                $conditions[] = ['game.id' => (new Query())->select('game_id')->from('{{%game_developer}}')->where(['developer_id' => $developerId])];
            }
            if ($pubExists) {
                $conditions[] = ['game.id' => (new Query())->select('game_id')->from('{{%game_publisher}}')->where(['publisher_id' => $publisherId])];
            }
            $query->andWhere($conditions);
        }

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
     * @return array{
     *   developedCount:int,
     *   publishedCount:int,
     *   totalCount:int,
     *   topGenre:?string,
     *   avgRating:?int,
     *   genreBreakdown:array<int,array{name:string,count:int,percent:int}>
     * }
     */
    private function buildStats(?int $developerId, ?int $publisherId): array
    {
        $developedCount = $developerId
            ? (int)(new Query())
                ->from('{{%game_developer}} gd')
                ->innerJoin('{{%game}} g', 'g.id = gd.game_id')
                ->where(['gd.developer_id' => $developerId, 'g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
                ->count()
            : 0;

        $publishedCount = $publisherId
            ? (int)(new Query())
                ->from('{{%game_publisher}} gp')
                ->innerJoin('{{%game}} g', 'g.id = gp.game_id')
                ->where(['gp.publisher_id' => $publisherId, 'g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
                ->count()
            : 0;

        $unionParts = [];
        if ($developerId) {
            $unionParts[] = "SELECT g.id, gg.genre_id FROM game_developer gd
                JOIN game g ON g.id = gd.game_id AND g.status = " . Game::STATUS_ACTIVE . " AND g.type = '" . Game::TYPE_GAME . "'
                JOIN game_genre gg ON gg.game_id = g.id
                WHERE gd.developer_id = " . $developerId;
        }
        if ($publisherId) {
            $unionParts[] = "SELECT g.id, gg.genre_id FROM game_publisher gp
                JOIN game g ON g.id = gp.game_id AND g.status = " . Game::STATUS_ACTIVE . " AND g.type = '" . Game::TYPE_GAME . "'
                JOIN game_genre gg ON gg.game_id = g.id
                WHERE gp.publisher_id = " . $publisherId;
        }

        $genreRows = [];
        $avg = null;
        $totalForRating = 0;
        if ($unionParts) {
            $unionSql = '(' . implode(' UNION ', $unionParts) . ') AS games_genres';
            $genreRows = (new Query())
                ->select(['genre.name', 'cnt' => 'COUNT(DISTINCT games_genres.id)'])
                ->from(new Expression($unionSql))
                ->innerJoin('{{%genre}} genre', 'genre.id = games_genres.genre_id')
                ->groupBy('genre.id, genre.name')
                ->orderBy(['cnt' => SORT_DESC])
                ->limit(6)
                ->all();

            // Avg rating across union of both roles
            $unionGames = [];
            if ($developerId) {
                $unionGames[] = "SELECT g.id FROM game_developer gd JOIN game g ON g.id = gd.game_id
                    WHERE gd.developer_id = " . $developerId . " AND g.status = " . Game::STATUS_ACTIVE . " AND g.type = '" . Game::TYPE_GAME . "'";
            }
            if ($publisherId) {
                $unionGames[] = "SELECT g.id FROM game_publisher gp JOIN game g ON g.id = gp.game_id
                    WHERE gp.publisher_id = " . $publisherId . " AND g.status = " . Game::STATUS_ACTIVE . " AND g.type = '" . Game::TYPE_GAME . "'";
            }
            $avgSql = '(' . implode(' UNION ', $unionGames) . ') AS uniq_games';
            $avg = (new Query())
                ->select(new Expression('AVG(r.total_positive / NULLIF(r.total_reviews, 0) * 100)'))
                ->from(new Expression($avgSql))
                ->innerJoin('{{%review}} r', 'r.game_id = uniq_games.id AND r.total_reviews > 50')
                ->scalar();
        }

        $totalForBreakdown = array_sum(array_column($genreRows, 'cnt')) ?: 1;
        $breakdown = array_map(static fn($row) => [
            'name'    => $row['name'],
            'count'   => (int)$row['cnt'],
            'percent' => (int)round(((int)$row['cnt'] / $totalForBreakdown) * 100),
        ], $genreRows);

        // Approximate unique total = developed + published - overlap. Cheap upper bound:
        $totalCount = max($developedCount, $publishedCount);

        return [
            'developedCount' => $developedCount,
            'publishedCount' => $publishedCount,
            'totalCount'     => $totalCount,
            'topGenre'       => $breakdown[0]['name'] ?? null,
            'avgRating'      => $avg !== null && $avg !== false ? (int)round((float)$avg) : null,
            'genreBreakdown' => $breakdown,
        ];
    }
}
