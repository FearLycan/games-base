<?php

namespace frontend\modules\game\controllers;

use common\components\AccessControl;
use common\components\AdultContent;
use common\components\BotDetector;
use common\components\CurrencyResolver;
use common\components\steam\SteamAchievementSync;
use common\models\Category;
use common\models\GameImage;
use common\models\GameSale;
use common\models\Genre;
use common\models\Tag;
use common\models\UserAchievement;
use common\models\UserGame;
use frontend\components\Controller;
use frontend\modules\game\models\Game;
use frontend\modules\game\models\searches\GameSearch;
use Yii;
use yii\caching\Cache;
use yii\caching\DbDependency;
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
                            'view', 'achievements', 'search-list', 'list', 'list-by-tag', 'sale', 'genres', 'tags', 'categories', 'index', 'details',
                        ],
                        'roles'   => ['?', '@'],
                    ],
                ],
            ],
            [
                'class'      => PageCache::class,
                'only'       => ['view', 'achievements', 'details'],
                'duration'   => YII_DEBUG ? 1 : 3600,
                'variations' => [
                    Yii::$app->controller->action->id . Yii::$app->request->get('id'),
                    // Prices are rendered in the visitor's currency (cookie/GeoIP),
                    // so the cached page must vary by it — otherwise switching the
                    // currency reloads but keeps the previously cached one.
                    CurrencyResolver::forVisitor(),
                    // 18+ games render differently per audience: a guest gets a 404
                    // (never cached), an opted-out user the confirmation gate, an
                    // opted-in/confirmed user the full page. Non-adult games yield
                    // '' so the catalogue isn't fragmented. Keeps a cached gate from
                    // ever being served to someone allowed to see the real page.
                    $this->adultViewToken(),
                ],
                // Drop the cached page as soon as the game is (re-)synced. A sync
                // rewrites synchronized_at, so this dependency's value changes and
                // the entry is invalidated — no cross-app cache deletion needed
                // (console FileCache can't reach the frontend's Redis anyway).
                // 'view' is keyed by steam_appid; 'details' by primary id.
                'dependency' => $this->gameSyncDependency(
                    Yii::$app->controller->action->id === 'details' ? 'id' : 'steam_appid',
                    (int)Yii::$app->request->get('id'),
                ),
            ],
        ];
    }

    /**
     * Cache dependency that expires when the given game is (re-)synchronized.
     * Used by both the full-page cache and the per-game model cache so a
     * force_sync refresh is reflected immediately instead of after the TTL.
     */
    private function gameSyncDependency(string $column, int $id): DbDependency
    {
        return new DbDependency([
            'sql'      => "SELECT [[synchronized_at]] FROM {{%game}} WHERE [[{$column}]] = :id",
            'params'   => [':id' => $id],
            'reusable' => true,
        ]);
    }

    /**
     * PageCache variation token for the 18+ gate (see behaviors()). Returns ''
     * for ordinary games so the catalogue isn't fragmented; for adult games it
     * splits the cache by exactly what the viewer is served — 'ok' (full page),
     * 'gate' (confirmation interstitial) or 'deny' (guest 404, never cached) —
     * so a cached page can't leak across audiences. Mirrors {@see adultGate()}.
     */
    private function adultViewToken(): string
    {
        $id = (int)Yii::$app->request->get('id');
        if ($id <= 0) {
            return '';
        }

        // 'view'/'achievements' are keyed by steam_appid; 'details' by primary id.
        $column = Yii::$app->controller->action->id === 'details' ? 'id' : 'steam_appid';
        $isAdult = (bool)$this->cache->getOrSet(
            ['game.is-adult', $column, $id],
            static fn(): bool => (int)Game::find()
                ->select('is_adult')
                ->where([$column => $id, 'status' => Game::STATUS_ACTIVE])
                ->limit(1)
                ->scalar() === 1,
            3600,
        );
        if (!$isAdult) {
            return '';
        }
        if (AdultContent::showCatalog()) {
            return 'ok';
        }
        if (Yii::$app->user->isGuest) {
            return 'deny';
        }

        return $this->isAdultConfirmed($id) ? 'ok' : 'gate';
    }

    /**
     * Enforces the 18+ rule on a game page. Returns null when the page may render
     * normally; returns the confirmation gate for a signed-in, opted-out user who
     * hasn't confirmed yet; throws 404 for guests (the game is invisible to them).
     *
     * @throws NotFoundHttpException
     */
    private function adultGate(Game $model): ?string
    {
        if ((int)$model->is_adult !== 1 || AdultContent::showCatalog()) {
            return null;
        }
        if (Yii::$app->user->isGuest) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $id = (int)Yii::$app->request->get('id');
        if ($this->isAdultConfirmed($id)) {
            $this->rememberAdultConfirmation($id);

            return null;
        }

        return $this->render('adult-gate', ['model' => $model]);
    }

    /** True once the signed-in user has confirmed (this request, or earlier this session) they want to see this 18+ game. */
    private function isAdultConfirmed(int $id): bool
    {
        if ((int)Yii::$app->request->get('confirm') === 1) {
            return true;
        }

        $confirmed = (array)Yii::$app->session->get('adult.confirmed', []);

        return !empty($confirmed[$id]);
    }

    /** Remembers, for the rest of the session, that the user confirmed this 18+ game. */
    private function rememberAdultConfirmation(int $id): void
    {
        $confirmed = (array)Yii::$app->session->get('adult.confirmed', []);
        $confirmed[$id] = true;
        Yii::$app->session->set('adult.confirmed', $confirmed);
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

        if (($gate = $this->adultGate($model)) !== null) {
            return $gate;
        }

        return $this->render('view', [
            'model'   => $model,
            'related' => $this->findRelatedGames($model),
            'dlcs'    => $this->findDlc($model),
        ]);
    }

    public function actionAchievements($id, $slug)
    {
        $model = $this->findModel($id, $slug);

        if (($gate = $this->adultGate($model)) !== null) {
            return $gate;
        }

        // The achievements page only makes sense when there are achievements to
        // show; otherwise send visitors back to the game page (301) so the empty
        // URL doesn't sit in the index.
        if (!$model->hasAchievements()) {
            return $this->redirect(['/game/game/view', 'id' => $model->steam_appid, 'slug' => $model->slug], 301);
        }

        $achievements = $model->achievements;
        $shown = count($achievements);

        // remaining > 0 only happens in appdetails-fallback mode (no API key),
        // where Steam exposes just a highlighted subset. Cap the decorative
        // "locked" placeholders so a 500-achievement game isn't a wall of ghosts.
        $remaining = max(0, (int)$model->achievements_total - $shown);

        // Display extras computed here so the view only renders. rarest = the
        // hardest achievement to earn (lowest known unlock rate); tierCounts
        // drives the rarity filter chips (only non-empty tiers get a chip).
        $rarest = null;
        $hiddenCount = 0;
        $tierCounts = ['ultra' => 0, 'rare' => 0, 'uncommon' => 0, 'common' => 0];
        foreach ($achievements as $achievement) {
            if ($achievement->hidden) {
                $hiddenCount++;
            }
            if ($achievement->percent !== null) {
                $tierCounts[$achievement->getRarityTier()]++;
                if ($rarest === null || $achievement->percent < $rarest->percent) {
                    $rarest = $achievement;
                }
            }
        }

        // The signed-in user's own progress, when they own this game. Map of
        // api_name => unlocked_at lets the view mark each row earned/locked.
        $userGame = null;
        $userUnlocked = [];
        if (!Yii::$app->user->isGuest) {
            $userId = (int)Yii::$app->user->id;
            $userGame = UserGame::findOne(['user_id' => $userId, 'game_id' => $model->id]);
            if ($userGame !== null) {
                $this->refreshUserAchievements($userGame);
                $rows = UserAchievement::find()
                    ->select(['api_name', 'unlocked_at'])
                    ->where(['user_id' => $userId, 'game_id' => $model->id])
                    ->asArray()
                    ->all();
                $userUnlocked = array_column($rows, 'unlocked_at', 'api_name');
            }
        }

        return $this->render('achievements', [
            'model'        => $model,
            'achievements' => $achievements,
            'shown'        => $shown,
            'remaining'    => $remaining,
            'lockedCount'  => min($remaining, 12),
            'rarest'       => $rarest,
            'hiddenCount'  => $hiddenCount,
            'tierCounts'   => $tierCounts,
            'userGame'     => $userGame,
            'userUnlocked' => $userUnlocked,
        ]);
    }

    /**
     * On-demand achievement refresh for the page owner: at most once per 24h per
     * game, and only for a public profile. This is the one place we may call
     * Steam in the request cycle (a single GetPlayerAchievements call) — the bulk
     * fill stays on the paced cron. Failures are swallowed so the page still loads.
     */
    private function refreshUserAchievements(UserGame $userGame): void
    {
        $user = Yii::$app->user->identity;
        if (!$user->isSteamProfilePublic()) {
            return;
        }

        $fresh = $userGame->ach_synced_at !== null
            && strtotime($userGame->ach_synced_at) >= strtotime('-24 hours');
        if ($fresh) {
            return;
        }

        try {
            (new SteamAchievementSync($user))->syncGame($userGame);
        } catch (\Throwable) {
            // keep whatever we had; the cron will catch up
        }
    }

    /**
     * Active DLC for the given base game, with the data the DLC cards render
     * (genre + cheapest offer with prices/store) eager-loaded so a list of them
     * costs no extra query per row. A DLC itself has no DLC, so it returns [].
     * Capped — some catalogue titles carry hundreds of add-ons.
     *
     * @return Game[]
     */
    protected function findDlc(Game $model, int $limit = 24): array
    {
        if ($model->isDlc()) {
            return [];
        }

        $query = $model->getDlc()
            ->with([
                'genres',
                'gameOffers' => static function ($q): void {
                    $q->andWhere(['game_offer.status' => \common\models\GameOffer::STATUS_ACTIVE])
                        ->orderBy(['game_offer.order' => SORT_ASC])
                        ->with(['store', 'prices']);
                },
            ])
            ->limit($limit);

        \common\components\AdultContent::filterCatalog($query, 'game');

        return $query->all();
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
            ->hideAdultCatalog('g')
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
            ->hideAdultCatalog('game')
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

            // Same 18+ rule as the full page: don't serve adult details to a
            // viewer who can't see the game (guest, or signed-in + opted-out and
            // not yet confirmed — confirmation may be keyed by either id form).
            $allowed = AdultContent::showCatalog()
                || (!Yii::$app->user->isGuest
                    && ($this->isAdultConfirmed((int)$id) || $this->isAdultConfirmed((int)$model->steam_appid)));
            if ((int)$model->is_adult === 1 && !$allowed) {
                throw new NotFoundHttpException('The requested page does not exist.');
            }

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
        // No model-level cache here: the action is already wrapped in PageCache
        // (same TTL + synchronized_at dependency), so this only runs on a page
        // cache miss — roughly hourly per game. A second cache layer would share
        // the same lifetime and add nothing.
        $model = Game::findOne(['steam_appid' => $id, 'slug' => $slug, 'status' => Game::STATUS_ACTIVE]);

        if ($model !== null) {
            // Viewed games get re-synced more often, but only real visitors flag
            // them: a crawler sweeping the catalogue would otherwise mark
            // thousands of games as force_sync and starve first-time syncs of new
            // games (status = STATUS_WAIT_TO_SYNC).
            if (!BotDetector::isBot(Yii::$app->request->userAgent)) {
                $model->checkSyncDate();
            }
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