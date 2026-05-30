<?php

namespace backend\modules\admin\controllers;

use backend\models\Category;
use backend\models\Developer;
use backend\models\Game;
use backend\models\GameOffer;
use backend\models\Genre;
use backend\models\Platform;
use backend\models\Publisher;
use backend\models\Review;
use backend\models\Store;
use backend\models\Tag;
use backend\models\User;
use backend\modules\admin\components\AdminHtml;

/**
 * Admin landing page: a tile per managed model with its row count.
 */
class DefaultController extends BaseAdminController
{
    public function actionIndex(): string
    {
        return $this->render('index', [
            'cards' => $this->cards(),
        ]);
    }

    /**
     * @return array<int, array{label: string, count: int, url: string, icon: string, flag: ?string}>
     */
    private function cards(): array
    {
        $models = [
            ['Games', Game::class, 'game/index', 'bi-controller'],
            ['Offers', GameOffer::class, 'game-offer/index', 'bi-cart3'],
            ['Stores', Store::class, 'store/index', 'bi-shop'],
            ['Categories', Category::class, 'category/index', 'bi-tags'],
            ['Genres', Genre::class, 'genre/index', 'bi-collection'],
            ['Tags', Tag::class, 'tag/index', 'bi-hash'],
            ['Developers', Developer::class, 'developer/index', 'bi-code-slash'],
            ['Publishers', Publisher::class, 'publisher/index', 'bi-building'],
            ['Platforms', Platform::class, 'platform/index', 'bi-pc-display'],
            ['Reviews', Review::class, 'review/index', 'bi-star'],
            ['Users', User::class, 'user/index', 'bi-people'],
        ];

        $pendingOffers = AdminHtml::pendingOffersCount();

        $cards = [];
        foreach ($models as [$label, $class, $url, $icon]) {
            $cards[] = [
                'label' => $label,
                'count' => (int)$class::find()->count(),
                'url'   => $url,
                'icon'  => $icon,
                'flag'  => ($class === GameOffer::class && $pendingOffers > 0)
                    ? $pendingOffers . ' to review'
                    : null,
            ];
        }

        return $cards;
    }
}
