<?php

namespace frontend\modules\api\controllers;

use common\models\Game;
use common\models\GameOffer;
use common\models\Store;
use frontend\modules\api\components\GameSerializer;
use Yii;
use yii\db\Query;

/**
 * Price delta feed: `/api/prices`.
 *
 * Exists because prices move independently of the game row — they live in
 * `game_offer_price`, and a price change does not touch `game.updated_at`. An
 * importer that only polls `/api/games?updated_since=` would therefore serve
 * stale prices; this endpoint gives it the cheap "what changed" stream instead.
 */
class PriceController extends BaseController
{
    /**
     * Query parameters:
     *   updated_since   only prices written at/after this timestamp (default:
     *                   the last 24 hours — a full dump is rarely what you want)
     *   currency        comma-separated filter (EUR,USD,PLN)
     *   store           comma-separated store slugs (instant-gaming, gamivo, …)
     *   include_adult   1 = include 18+ titles (excluded by default)
     *   page, per_page  pagination
     *
     * Ordered by price-row id so offset pagination is stable while prices are
     * being written underneath it.
     */
    public function actionIndex(): array
    {
        $request = Yii::$app->request;
        $serializer = new GameSerializer([]);

        $since = $this->dateParam('updated_since') ?? date('Y-m-d H:i:s', strtotime('-1 day'));

        $query = (new Query())
            ->from('{{%game_offer_price}} p')
            ->innerJoin('{{%game_offer}} o', 'o.id = p.offer_id')
            ->innerJoin('{{%game}} g', 'g.id = o.game_id')
            ->innerJoin('{{%store}} s', 's.id = o.store_id')
            ->where([
                'o.status' => GameOffer::STATUS_ACTIVE,
                'g.status' => Game::STATUS_ACTIVE,
                's.status' => Store::STATUS_ACTIVE,
            ])
            ->andWhere(['>=', 'p.updated_at', $since]);

        if (!$request->get('include_adult')) {
            $query->andWhere(['g.is_adult' => 0]);
        }

        $currencies = $this->currencies();
        if ($currencies !== null) {
            $query->andWhere(['p.currency' => $currencies]);
        }

        $stores = $this->storeSlugs();
        if ($stores !== null) {
            $query->andWhere(['s.slug' => $stores]);
        }

        $total = (int)(clone $query)->count();

        $rows = $query
            ->select([
                'p.id',
                'p.offer_id',
                'p.currency',
                'p.price_initial',
                'p.price_final',
                'p.lowest_final',
                'p.highest_final',
                'p.updated_at',
                'g.steam_appid',
                'store_slug' => 's.slug',
                'store_name' => 's.name',
                'offer_url'  => 'o.url',
                'o.region',
                'o.edition',
                'o.external_id',
            ])
            ->orderBy(['p.id' => SORT_ASC])
            ->offset(($this->page() - 1) * $this->perPage())
            ->limit($this->perPage())
            ->all();

        $items = array_map(static function (array $row) use ($serializer): array {
            $initial = $row['price_initial'] !== null ? (int)$row['price_initial'] : null;
            $final = $row['price_final'] !== null ? (int)$row['price_final'] : null;
            $updatedAt = strtotime((string)$row['updated_at']);

            return [
                'steam_appid'      => (int)$row['steam_appid'],
                'offer_id'         => (int)$row['offer_id'],
                'store'            => $row['store_slug'],
                'store_name'       => $row['store_name'],
                'url'              => $row['offer_url'],
                'external_id'      => $row['external_id'],
                'region'           => $row['region'],
                'edition'          => $row['edition'],
                'currency'         => strtoupper((string)$row['currency']),
                'price_initial'    => $initial,
                'price_final'      => $final,
                'discount_percent' => $serializer->discountPercent($initial, $final),
                'lowest_final'     => $row['lowest_final'] !== null ? (int)$row['lowest_final'] : null,
                'highest_final'    => $row['highest_final'] !== null ? (int)$row['highest_final'] : null,
                'updated_at'       => $updatedAt ? date('c', $updatedAt) : null,
            ];
        }, $rows);

        return $this->paginated($items, $total, [
            'price_units'   => 'minor', // cents / grosze
            'updated_since' => date('c', strtotime($since)),
        ]);
    }

    /**
     * @return string[]|null
     */
    private function storeSlugs(): ?array
    {
        $raw = trim((string)Yii::$app->request->get('store', ''));
        if ($raw === '') {
            return null;
        }

        $slugs = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return $slugs ?: null;
    }
}
