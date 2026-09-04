<?php

namespace frontend\modules\api\controllers;

use common\models\Store;
use frontend\modules\api\components\GameSerializer;

/**
 * Store directory: `/api/stores`.
 *
 * Offers reference stores by slug; a consumer that wants to show a store's name,
 * logo or official/keyshop badge resolves them here once instead of repeating
 * that data on every offer.
 */
class StoreController extends BaseController
{
    public function actionIndex(): array
    {
        $serializer = new GameSerializer([]);

        $stores = Store::find()
            ->where(['status' => Store::STATUS_ACTIVE])
            ->orderBy(['order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        return [
            'items' => array_map(
                static fn(Store $store): ?array => $serializer->store($store),
                $stores,
            ),
            'meta'  => ['generated_at' => date('c')],
        ];
    }
}
