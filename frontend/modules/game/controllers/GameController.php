<?php

namespace frontend\modules\game\controllers;

use common\components\AccessControl;
use common\models\Category;
use common\models\GameImage;
use common\models\GameSale;
use common\models\Genre;
use common\models\Tag;
use frontend\components\Controller;
use frontend\modules\game\models\Game;
use frontend\modules\game\models\searches\GameSearch;
use Yii;
use yii\caching\Cache;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\PageCache;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class GameController extends Controller
{
    /** @var Cache */
    private $cache;

    public function __construct($id, $module, $config = [])
    {
        $this->cache = Yii::$app->cache;
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => [
                            'view', 'search-list', 'list', 'list-by-tag', 'sale', 'genres', 'tags', 'categories', 'index', 'details',
                        ],
                        'roles'   => ['?'],
                    ],
                ],
            ],
            [
                'class'      => PageCache::class,
                'only'       => ['view', 'details'],
                'duration'   => YII_DEBUG ? 1 : 3600,
                'variations' => [
                    Yii::$app->controller->action->id . Yii::$app->request->get('id'),
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new GameSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionList($slug): string
    {
        $model = $this->findCategoryOrGenre($slug);

        $searchModel = new GameSearch();

        if ($model instanceof Category) {
            $searchModel->category_id = $model->id;
        } else if ($model instanceof Genre) {
            $searchModel->genre_id = $model->id;
        } else {
            $this->notFound();
        }

        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('list', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'model'        => $model,
            'stats'        => $this->buildListData($model),
        ]);

    }

    public function actionSale($type): string
    {
        $typeMap = [
            'bestsellers'        => GameSale::TYPE_BESTSELLERS,
            'new-and-noteworthy' => GameSale::TYPE_NEW_AND_NOTEWORTHY,
            'upcoming'           => GameSale::TYPE_POPULAR_UPCOMING,
        ];

        if (!isset($typeMap[$type])) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $saleType = $typeMap[$type];
        $games = \common\models\Game::getSales($saleType, 250);

        return $this->render('sale', [
            'slug'  => $type,
            'type'  => $saleType,
            'games' => $games,
        ]);
    }

    public function actionGenres(): string
    {
        $genres = Genre::find()
            ->where(['>', 'games_count', 0])
            ->orderBy(['games_count' => SORT_DESC, 'name' => SORT_ASC])
            ->all();

        return $this->render('genres', [
            'genres' => $genres,
            'hub'    => $this->buildHubStats($genres, 'genre'),
        ]);
    }

    public function actionTags(): string
    {
        $tags = Tag::find()
            ->where(['>', 'games_count', 0])
            ->orderBy(['games_count' => SORT_DESC, 'name' => SORT_ASC])
            ->all();

        return $this->render('tags', [
            'tags' => $tags,
            'hub'  => $this->buildHubStats($tags, 'tag'),
        ]);
    }

    public function actionCategories(): string
    {
        $categories = Category::find()
            ->where(['>', 'games_count', 0])
            ->orderBy(['games_count' => SORT_DESC, 'name' => SORT_ASC])
            ->all();

        return $this->render('categories', [
            'categories' => $categories,
        ]);
    }

    public function actionListByTag($slug): string
    {
        $tag = Tag::findOne(['slug' => $slug]);
        if (!$tag) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $searchModel = new GameSearch();
        $searchModel->tag_ids = [$tag->id];
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('list', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'model'        => $tag,
            'stats'        => $this->buildListData($tag),
        ]);
    }

    public function actionView($id, $slug)
    {
        $model = $this->findModel($id, $slug);

        return $this->render('view', [
            'model'   => $model,
            'related' => $this->findRelatedGames($model),
        ]);
    }

    /**
     * Games that share the most genres with the current one, ranked by overlap
     * then by review volume. Drives the "more games like this" block to keep
     * visitors browsing. Cheap enough — the whole view is PageCache'd per id.
     *
     * @return Game[]
     */
    protected function findRelatedGames(Game $model, int $limit = 6): array
    {
        $genreIds = array_map(static fn($genre) => (int)$genre->id, $model->genres);
        if ($genreIds === []) {
            return [];
        }

        return Game::find()
            ->alias('g')
            ->select(['g.*', 'shared' => 'COUNT(DISTINCT gg.genre_id)', 'reviews' => 'MAX(r.total_reviews)'])
            ->innerJoin('{{%game_genre}} gg', 'gg.game_id = g.id')
            ->leftJoin('{{%review}} r', 'r.game_id = g.id')
            // andWhere (not where): GameQuery::where() force-injects the
            // unaliased `game.status`, which breaks against our `g` alias.
            ->andWhere(['gg.genre_id' => $genreIds])
            ->andWhere(['g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
            ->andWhere(['<>', 'g.id', (int)$model->id])
            ->groupBy('g.id')
            ->orderBy(['shared' => SORT_DESC, 'reviews' => SORT_DESC])
            ->limit($limit)
            ->with(['genres', 'review'])
            ->all();
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
        $key = Yii::$app->controller->id . $id . $slug;
        $model = $this->cache->getOrSet($key, function () use ($id, $slug) {
            $game = Game::findOne(['steam_appid' => $id, 'slug' => $slug]);
            // Viewed games get re-synced more often. Runs only on cache miss
            // (~hourly per game), so it's not a write on every request.
            $game?->checkSyncDate();
            return $game;
        }, 3600);

        if ($model !== null) {
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

    /**
     * Per-landing-page stats + a unique, data-driven intro + related
     * cross-links, cached for an hour. Counting/SQL/string assembly all live
     * here so the view only renders.
     *
     * @param Genre|Tag|Category $model
     * @return array{total:int,free:int,avgRating:?int,yearText:?string,priceText:?string,intro:string,related:array,relatedLabel:string}
     */
    protected function buildListData($model): array
    {
        [$pivot, $fk, $relatedKind, $relatedLabel] = match (true) {
            $model instanceof Tag => ['{{%game_tag}}', 'tag_id', 'genre', 'Common genres'],
            $model instanceof Category => ['{{%game_category}}', 'category_id', 'genre', 'Common genres'],
            default => ['{{%game_genre}}', 'genre_id', 'tag', 'Popular tags'],
        };

        $cacheKey = 'list.data.' . $fk . '.' . (int)$model->id;

        return $this->cache->getOrSet($cacheKey, function () use ($model, $pivot, $fk, $relatedKind, $relatedLabel) {
            $stats = $this->aggregateListStats($pivot, $fk, (int)$model->id);
            $stats['yearText'] = $this->formatYearSpan($stats['minYear'], $stats['maxYear']);
            $stats['priceText'] = $this->formatPriceSpan($stats['free'], $stats['total'], $stats['minPrice'], $stats['maxPrice']);
            $stats['related'] = $this->relatedListLinks($pivot, $fk, (int)$model->id, $relatedKind);
            $stats['relatedLabel'] = $relatedLabel;
            $stats['intro'] = $this->composeListIntro($model, $stats);
            return $stats;
        }, 3600);
    }

    private function aggregateListStats(string $pivot, string $fk, int $id): array
    {
        $base = (new Query())
            ->from('{{%game}} g')
            ->innerJoin($pivot . ' pv', 'pv.game_id = g.id')
            ->where(['pv.' . $fk => $id, 'g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME]);

        $row = (clone $base)
            ->select([
                'total'     => 'COUNT(DISTINCT g.id)',
                'free'      => 'COUNT(DISTINCT CASE WHEN g.is_free = 1 THEN g.id END)',
                'min_price' => 'MIN(CASE WHEN g.is_free = 0 AND g.steam_price_final > 0 THEN g.steam_price_final END)',
                'max_price' => 'MAX(CASE WHEN g.is_free = 0 AND g.steam_price_final > 0 THEN g.steam_price_final END)',
                'min_year'  => 'MIN(YEAR(g.release_date))',
                'max_year'  => 'MAX(YEAR(g.release_date))',
            ])
            ->one();

        $avg = (clone $base)
            ->innerJoin('{{%review}} r', 'r.game_id = g.id AND r.total_reviews > 50')
            ->select(new Expression('AVG(r.total_positive / NULLIF(r.total_reviews, 0) * 100)'))
            ->scalar();

        return [
            'total'     => (int)($row['total'] ?? 0),
            'free'      => (int)($row['free'] ?? 0),
            'minPrice'  => isset($row['min_price']) && $row['min_price'] !== null ? (int)$row['min_price'] : null,
            'maxPrice'  => isset($row['max_price']) && $row['max_price'] !== null ? (int)$row['max_price'] : null,
            'minYear'   => isset($row['min_year']) && $row['min_year'] !== null ? (int)$row['min_year'] : null,
            'maxYear'   => isset($row['max_year']) && $row['max_year'] !== null ? (int)$row['max_year'] : null,
            'avgRating' => ($avg !== null && $avg !== false) ? (int)round((float)$avg) : null,
        ];
    }

    /**
     * Top related entities (tags for a genre, genres for a tag/category) that
     * co-occur on games in this set — used for hub-and-spoke internal linking.
     *
     * @return array<int, array{name:string,slug:string,count:int,url:string}>
     */
    private function relatedListLinks(string $pivot, string $fk, int $id, string $relatedKind): array
    {
        if ($relatedKind === 'tag') {
            $relPivot = '{{%game_tag}}';
            $relFk = 'tag_id';
            $relTable = '{{%tag}}';
            $urlPrefix = '/game/tag/';
        } else {
            $relPivot = '{{%game_genre}}';
            $relFk = 'genre_id';
            $relTable = '{{%genre}}';
            $urlPrefix = '/games/';
        }

        $rows = (new Query())
            ->select(['rel.name', 'rel.slug', 'cnt' => 'COUNT(DISTINCT g.id)'])
            ->from('{{%game}} g')
            ->innerJoin($pivot . ' pv', 'pv.game_id = g.id')
            ->innerJoin($relPivot . ' rp', 'rp.game_id = g.id')
            ->innerJoin($relTable . ' rel', 'rel.id = rp.' . $relFk)
            ->where(['pv.' . $fk => $id, 'g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
            ->andWhere(['not', ['rel.slug' => null]])
            ->andWhere(['<>', 'rel.slug', ''])
            ->groupBy(['rel.id', 'rel.name', 'rel.slug'])
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(10)
            ->all();

        $links = [];
        foreach ($rows as $r) {
            $links[] = [
                'name'  => (string)$r['name'],
                'slug'  => (string)$r['slug'],
                'count' => (int)$r['cnt'],
                'url'   => $urlPrefix . $r['slug'],
            ];
        }

        return $links;
    }

    private function formatYearSpan(?int $minYear, ?int $maxYear): ?string
    {
        if (!$minYear || !$maxYear) {
            return null;
        }

        return $minYear === $maxYear ? (string)$minYear : $minYear . '–' . $maxYear;
    }

    private function formatPriceSpan(int $free, int $total, ?int $minPrice, ?int $maxPrice): ?string
    {
        if ($total > 0 && $free === $total) {
            return 'Free to play';
        }

        if ($minPrice === null || $maxPrice === null) {
            return null;
        }

        if ($minPrice === $maxPrice) {
            return $this->formatPrice($minPrice);
        }

        return $this->formatPrice($minPrice) . ' – ' . $this->formatPrice($maxPrice);
    }

    private function formatPrice(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }

    private function composeListIntro($model, array $stats): string
    {
        $editorDescription = $model->hasAttribute('description') ? trim((string)$model->description) : '';
        if ($editorDescription !== '') {
            return $editorDescription;
        }

        $name = (string)$model->name;
        $total = $stats['total'];
        if ($total < 1) {
            return sprintf('Hand-picked %s games from Steam — synced daily as new titles land.', $name);
        }

        $sentence = sprintf('%s %s game%s in our Steam catalog', number_format($total), $name, $total === 1 ? '' : 's');
        if ($stats['yearText'] !== null) {
            $sentence .= str_contains($stats['yearText'], '–')
                ? ', spanning ' . $stats['yearText']
                : ', released in ' . $stats['yearText'];
        }
        $intro = $sentence . '.';

        if ($stats['free'] > 0 && $stats['free'] === $total) {
            $intro .= ' Every one is free to play.';
        } else if ($stats['free'] > 0 && $stats['minPrice'] !== null) {
            $intro .= sprintf(' %s free to play; the rest run %s.', number_format($stats['free']), $this->formatPriceSpan(0, $total, $stats['minPrice'], $stats['maxPrice']));
        } else if ($stats['priceText'] !== null) {
            $intro .= ' Prices run ' . $stats['priceText'] . '.';
        }

        if ($stats['avgRating'] !== null) {
            $intro .= sprintf(' Reviews average %d%% positive on Steam.', $stats['avgRating']);
        }

        return $intro;
    }

    /**
     * Aggregate stats + a data-driven intro for a directory hub (genres/tags).
     * The distinct active-game count is cached; the top entity comes from the
     * already-sorted collection. Counting/SQL/copy all live here.
     *
     * @param array         $items entities sorted by games_count DESC
     * @param 'genre'|'tag' $kind
     * @return array{count:int,games:int,intro:string}
     */
    protected function buildHubStats(array $items, string $kind): array
    {
        $pivot = $kind === 'tag' ? '{{%game_tag}}' : '{{%game_genre}}';

        $games = (int)$this->cache->getOrSet('hub.games.' . $kind, function () use ($pivot) {
            return (new Query())
                ->from('{{%game}} g')
                ->innerJoin($pivot . ' pv', 'pv.game_id = g.id')
                ->where(['g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME])
                ->count('DISTINCT g.id');
        }, 3600);

        return [
            'count' => count($items),
            'games' => $games,
            'intro' => $this->composeHubIntro($kind, count($items), $games, $items[0] ?? null),
        ];
    }

    private function composeHubIntro(string $kind, int $count, int $games, $top): string
    {
        if ($count < 1) {
            return $kind === 'tag'
                ? 'Crowd-sourced Steam tags — more specific than genres. They populate as games sync.'
                : 'Every genre we track on Steam. Genres populate as games sync.';
        }

        $topName = $top !== null ? (string)$top->name : '';
        $topCount = $top !== null ? (int)$top->games_count : 0;

        if ($kind === 'tag') {
            $intro = sprintf('%s crowd-sourced Steam tags linked to %s games — more specific than genres.',
                number_format($count), number_format($games));
            if ($topName !== '') {
                $intro .= sprintf(' %s leads with %s titles.', $topName, number_format($topCount));
            }
            return $intro . ' Find your niche.';
        }

        $intro = sprintf('We track %s genres across %s games on Steam, sorted by how much there is to dig through.',
            number_format($count), number_format($games));
        if ($topName !== '') {
            $intro .= sprintf(' %s leads with %s titles.', $topName, number_format($topCount));
        }
        return $intro . ' Pick a corner that calls to you.';
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