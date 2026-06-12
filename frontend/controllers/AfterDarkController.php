<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\components\AccessControl;
use common\components\CurrencyResolver;
use common\enums\AfterDarkLayout;
use common\models\Game;
use common\models\GameOffer;
use common\models\User;
use frontend\components\Controller;
use frontend\modules\game\models\searches\GameSearch;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * "After Dark" — a standalone 18+ area separate from the public catalogue.
 *
 * Hard-gated: it is only reachable by a signed-in account that has explicitly
 * opted into adult content (User::$show_adult). Guests are bounced to login by
 * the access filter; opted-out members are redirected to their settings with a
 * prompt to enable 18+. The whole area is kept out of search indexes — the
 * {@see \frontend\views\layouts\afterdark} layout emits robots=noindex and the
 * path is disallowed in robots.txt and absent from the sitemap.
 *
 * The page is rendered in one of three interchangeable visual themes
 * ({@see AfterDarkLayout}); the member switches between them live and the choice
 * is persisted on their account.
 */
class AfterDarkController extends Controller
{
    /** How often the spotlight trio rotates, in hours. */
    private const int SPOTLIGHT_ROTATE_HOURS = 4;

    /** How many top-rated titles the rotating spotlight draws its trio from. */
    private const int SPOTLIGHT_POOL = 40;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => ['index', 'layout', 'quick-view'],
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => ['layout' => ['post']],
            ],
        ];
    }

    /**
     * Second gate beyond the login requirement: a signed-in member who hasn't
     * turned on 18+ content has nothing to see here, so send them to settings
     * with a nudge instead of showing the area.
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        /** @var User|null $user */
        $user = Yii::$app->user->identity;
        if ($user !== null && !$user->show_adult) {
            Yii::$app->session->setFlash('info', 'Turn on 18+ content in your settings to open the After Dark area.');
            Yii::$app->response->redirect(['/user/profile/settings']);

            return false;
        }

        return true;
    }

    public function actionIndex(): string
    {
        $this->layout = '@frontend/views/layouts/afterdark';

        /** @var User $user */
        $user = Yii::$app->user->identity;

        $searchModel = new GameSearch();
        // Force the catalogue to adult-only regardless of any query params.
        $params = array_merge(Yii::$app->request->queryParams, ['age_adult' => '1']);
        $dataProvider = $searchModel->search($params);

        // "Filtering" = a search term or a picked facet. In that mode the page
        // collapses to just the results: the hero, spotlight and "Just arrived"
        // rail belong to the unfiltered landing only.
        $filtering = ($searchModel->q ?? '') !== ''
            || !empty($searchModel->genre_ids)
            || !empty($searchModel->tag_ids);

        return $this->render('index', [
            'layout'       => $user->getAfterDarkLayout(),
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'filtering'    => $filtering,
            'featured'     => $filtering ? [] : $this->featuredGames(),
            'fresh'        => $filtering ? [] : $this->freshGames(),
            'facets'       => $this->adultFacets(),
            'total'        => $dataProvider->getTotalCount(),
        ]);
    }

    /**
     * Persists the chosen visual theme and returns to the page, which then
     * renders in the new look.
     */
    public function actionLayout(): Response
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;

        $choice = AfterDarkLayout::tryFrom((string)Yii::$app->request->post('layout', ''));
        if ($choice !== null) {
            $user->after_dark_layout = $choice->value;
            $user->save(false, ['after_dark_layout']);
            Yii::$app->session->setFlash('success', 'Switched to the ' . $choice->label() . ' look.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Renders the quick-view card (name, blurb, trailer, screenshot slider,
     * prices and a link through to the full page) for one adult title. Loaded
     * over AJAX into the shared modal when a member taps a game tile, so it is
     * returned as a bare partial — no layout. Stays inside the same adult-only
     * query as the rest of the area, so a non-adult or unknown id 404s.
     */
    public function actionQuickView(int $id): string
    {
        /** @var Game|null $game */
        $game = $this->adultGames()
            ->andWhere(['g.steam_appid' => $id])
            ->with('videos')
            ->one();

        if ($game === null) {
            throw new NotFoundHttpException('Title not found.');
        }

        $currency = CurrencyResolver::forVisitor();

        return $this->renderPartial('@frontend/views/after-dark/_quick-view', [
            'game'          => $game,
            'shots'         => $this->screenshotUrls($game),
            'offers'        => $game->getSortedOffers($currency),
            'bestOffer'     => $game->getBestOffer($currency),
            'offerCurrency' => $currency,
        ]);
    }

    /**
     * Up to $limit screenshot URLs for the quick-view slider, swapped from the
     * stored full-size shot to Steam's lightweight 600x338 variant (same CDN
     * path) — plenty for the modal and a fraction of the weight.
     *
     * @return string[]
     */
    private function screenshotUrls(Game $game, int $limit = 8): array
    {
        return array_map(
            static fn(string $url): string => str_replace('.1920x1080.', '.600x338.', $url),
            array_map(
                static fn($img): string => $img->url,
                array_slice($game->getScreenshots(), 0, $limit),
            ),
        );
    }

    /** Base query for adult, catalogued games with the data the tiles render. */
    private function adultGames(): ActiveQuery
    {
        return Game::find()
            ->alias('g')
            ->andWhere(['g.status' => Game::STATUS_ACTIVE, 'g.is_adult' => 1, 'g.type' => Game::TYPE_GAME])
            ->with([
                'genres',
                'gameOffers' => static function ($q): void {
                    $q->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])
                        ->orderBy(['game_offer.order' => SORT_ASC])
                        ->with(['store', 'prices']);
                },
            ]);
    }

    /**
     * The hero spotlight trio. Draws from the {@see SPOTLIGHT_POOL} best-reviewed
     * adult titles and rotates which three are shown every
     * {@see SPOTLIGHT_ROTATE_HOURS} hours: a shuffle seeded by the current time
     * bucket keeps the pick stable within a window but fresh across windows, so
     * the same handful of top games take turns instead of one fixed trio.
     *
     * @return Game[]
     */
    private function featuredGames(int $count = 3): array
    {
        // The trio is fixed for the whole rotation window, so cache it per window
        // (keyed by the time bucket): the heavy top-pool query (40 rows + eager
        // loads) then runs once per window instead of on every request.
        $bucket = intdiv(time(), self::SPOTLIGHT_ROTATE_HOURS * 3600);

        return Yii::$app->cache->getOrSet(['after-dark.featured', $bucket], function () use ($count, $bucket): array {
            $pool = $this->adultGames()
                ->select('g.*')
                ->leftJoin('{{%review}} r', 'r.game_id = g.id')
                ->andWhere(['>', 'r.total_reviews', 50])
                ->with('review')
                ->orderBy(['r.total_positive' => SORT_DESC])
                ->limit(self::SPOTLIGHT_POOL)
                ->all();

            if (count($pool) <= $count) {
                return $pool;
            }

            // Deterministic shuffle seeded by the bucket — same trio all window.
            mt_srand($bucket);
            shuffle($pool);
            mt_srand(); // restore non-deterministic RNG for the rest of the request

            return array_slice($pool, 0, $count);
        }, self::SPOTLIGHT_ROTATE_HOURS * 3600);
    }

    /**
     * Newest adult releases — drives the "Just arrived" rail.
     *
     * @return Game[]
     */
    private function freshGames(int $limit = 5): array
    {
        // New releases land at most as often as the catalogue sync runs, so a
        // short TTL is plenty and saves the eager-loaded query on every request.
        return Yii::$app->cache->getOrSet('after-dark.fresh', fn(): array => $this->adultGames()
            ->andWhere(['not', ['g.release_date' => null]])
            ->andWhere(['<=', 'g.release_date', date('Y-m-d')])
            ->orderBy(['g.release_date' => SORT_DESC])
            ->limit($limit)
            ->all(), 1800);
    }

    /**
     * The most common genres and tags across the adult catalogue — drives the
     * catalogue's filter chips. Cached for an hour since it only shifts as the
     * catalogue grows.
     *
     * @return array{genres:array<int,array{id:int,name:string}>,tags:array<int,array{id:int,name:string}>}
     */
    private function adultFacets(): array
    {
        return Yii::$app->cache->getOrSet('after-dark.facets.v2', fn(): array => [
            'genres' => $this->topFacet('{{%game_genre}}', 'genre_id', '{{%genre}}', 16),
            'tags'   => $this->topFacet('{{%game_tag}}', 'tag_id', '{{%tag}}', 30),
        ], 3600);
    }

    /**
     * Top $limit entities of one kind (genre/tag/category) by how many adult
     * games carry them, most-used first.
     *
     * @return array<int,array{id:int,name:string}>
     */
    private function topFacet(string $pivot, string $fk, string $table, int $limit = 14): array
    {
        $rows = (new Query())
            ->select(['f.id', 'f.name', 'cnt' => 'COUNT(DISTINCT g.id)'])
            ->from(['g' => Game::tableName()])
            ->innerJoin($pivot . ' pv', 'pv.game_id = g.id')
            ->innerJoin($table . ' f', 'f.id = pv.' . $fk)
            ->where(['g.status' => Game::STATUS_ACTIVE, 'g.is_adult' => 1, 'g.type' => Game::TYPE_GAME])
            ->andWhere(['not', ['f.name' => null]])
            ->andWhere(['<>', 'f.name', ''])
            ->groupBy(['f.id', 'f.name'])
            ->orderBy(['cnt' => SORT_DESC])
            ->limit($limit)
            ->all();

        return array_map(static fn(array $r): array => ['id' => (int)$r['id'], 'name' => (string)$r['name']], $rows);
    }
}
