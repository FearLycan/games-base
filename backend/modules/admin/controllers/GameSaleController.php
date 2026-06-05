<?php

namespace backend\modules\admin\controllers;

use backend\models\GameSale;
use backend\modules\admin\components\AdminHtml;
use backend\modules\admin\models\search\GameSaleSearch;

/**
 * Read/curate the ranked lists in {{%game_sale}} (Steam charts + Instant Gaming
 * rails). This is just the ranking: whether an IG entry actually shows on the
 * homepage is decided by its IG offer, so the match review lives in the Offers
 * queue ({@see GameOfferController}), not here.
 */
class GameSaleController extends CrudController
{
    public string $modelClass = GameSale::class;
    public string $searchModelClass = GameSaleSearch::class;
    public string $modelLabel = 'List entry';
    public string $modelLabelPlural = 'List entries';

    // No on/off here: a list entry's visibility on the homepage is governed by its
    // matched IG offer (reviewed in the Offers queue), not by this ranking row.
    protected ?string $toggleAttribute = null;

    protected function gridColumns(): array
    {
        return [
            'id',
            [
                'attribute'          => 'gameTitle',
                'label'              => 'Game',
                'format'             => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Name or App ID'],
                'value'              => static fn(GameSale $m): string => AdminHtml::gameLink($m->game, $m->game_id),
            ],
            [
                'attribute' => 'type',
                'label'     => 'List',
                'filter'    => GameSale::typeLabels(),
                'value'     => static fn(GameSale $m): string => GameSale::typeLabel((int)$m->type),
            ],
            [
                'attribute'      => 'order',
                'label'          => 'Rank',
                'contentOptions' => ['class' => 'text-center'],
                'headerOptions'  => ['class' => 'text-center'],
            ],
            $this->actionColumn(),
        ];
    }
}
