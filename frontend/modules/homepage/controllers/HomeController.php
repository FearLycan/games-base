<?php

namespace frontend\modules\homepage\controllers;

use common\components\AccessControl;
use common\models\Game;
use common\models\GameSale;
use common\models\Genre;
use frontend\components\Controller;
use Yii;
use yii\caching\Cache;

class HomeController extends Controller
{
    private Cache $cache;

    public function __construct($id, $module, $config = [])
    {
        $this->cache = Yii::$app->cache;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access'    => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow'   => true,
                        'actions' => [
                            'index',
                        ],
                        'roles'   => ['?', '@'],
                    ],
                ],
            ],
            /*'pageCache' => [
                'class'    => 'yii\filters\PageCache',
                'only'     => ['index'],
                'duration' => YII_DEBUG ? 1 : 3600,
            ],*/
        ];
    }

    public function actionIndex()
    {
        // Genre tiles (with counts) for the "browse by genre" directory.
        $genreTiles = $this->cache->getOrSet('home.genre-tiles', static fn(): array => Genre::find()
            ->where(['>', 'games_count', 0])
            ->orderBy(['games_count' => SORT_DESC, 'name' => SORT_ASC])
            ->limit(12)
            ->all(), 3600);

        // Homepage hides free-to-play everywhere (no price to compare).
        $bestsellers = Game::getSales(GameSale::TYPE_BESTSELLERS, 30, true);
        $new_and_noteworthy = Game::getSales(GameSale::TYPE_NEW_AND_NOTEWORTHY, 30, true);
        $popular_upcoming = Game::getSales(GameSale::TYPE_POPULAR_UPCOMING, 30, true);

        // Deal board (cross-store, free titles excluded) — the price-comparison
        // value prop. The single hero "top deal" is the most dramatic discount.
        $best_deals = Game::getBestDeals(6);
        $biggest_discounts = Game::getBiggestDiscounts(6);
        $most_wishlisted = Game::getMostWishlisted(6);
        $new_releases = Game::getNewReleases(6);
        // Games at their lowest price ever — empty until prices have moved.
        $historical_lows = Game::getHistoricalLows(12);
        $top_deal = $biggest_discounts[0] ?? ($best_deals[0] ?? null);

        // Personalized: wishlisted games on sale + recommendations from the
        // user's most-played game, for signed-in Steam users.
        $wishlist_deals = [];
        $because = null;
        if (!Yii::$app->user->isGuest) {
            $userId = (int)Yii::$app->user->id;
            $wishlist_deals = Game::getWishlistDeals($userId, 12);
            $because = Game::getBecauseYouPlayed($userId, 12);
        }

        $gamesCount = Game::count();
        $genresCount = $this->cache->getOrSet('genre.count', static fn(): int => (int)Genre::find()->count(), 86400);
        // Count only stores that actually have live offers (not every seeded
        // store row), so the trust bar never overstates the comparison.
        $storesCount = $this->cache->getOrSet('store.with-offers.count', static fn(): int => (int)(new \yii\db\Query())
            ->from('{{%game_offer}} o')
            ->innerJoin('{{%store}} s', 's.id = o.store_id AND s.status = :st', [':st' => \common\models\Store::STATUS_ACTIVE])
            ->where(['o.status' => \common\models\GameOffer::STATUS_ACTIVE])
            ->select('COUNT(DISTINCT o.store_id)')
            ->scalar(), 3600);

        return $this->render('index', [
            'genreTiles'         => $genreTiles,
            'bestsellers'        => $bestsellers,
            'new_and_noteworthy' => $new_and_noteworthy,
            'popular_upcoming'   => $popular_upcoming,
            'best_deals'         => $best_deals,
            'biggest_discounts'  => $biggest_discounts,
            'most_wishlisted'    => $most_wishlisted,
            'new_releases'       => $new_releases,
            'historical_lows'    => $historical_lows,
            'wishlist_deals'     => $wishlist_deals,
            'because'            => $because,
            'top_deal'           => $top_deal,
            'gamesCount'         => $gamesCount,
            'genresCount'        => $genresCount,
            'storesCount'        => $storesCount,
        ]);
    }
}