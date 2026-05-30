<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\components\AdminHtml;
use backend\modules\admin\models\search\GameSearch;
use backend\models\Game;

class GameController extends CrudController
{
    public string $modelClass = Game::class;
    public string $searchModelClass = GameSearch::class;
    public string $modelLabel = 'Game';
    public string $modelLabelPlural = 'Games';

    protected ?string $toggleAttribute = 'status';
    protected int $toggleOn = Game::STATUS_ACTIVE;
    protected int $toggleOff = Game::STATUS_INACTIVE;

    protected function statusFilterOptions(): array
    {
        return [
            Game::STATUS_ACTIVE        => 'Active',
            Game::STATUS_WAIT_TO_SYNC  => 'Wait to sync',
            Game::STATUS_INACTIVE      => 'Inactive',
            Game::STATUS_SUCCESS_FALSE => 'Sync failed',
        ];
    }

    /** Purpose-built game detail screen. */
    public function actionView(int $id): string
    {
        return $this->render('view', [
            'model'      => $this->findModel($id),
            'controller' => $this,
        ]);
    }

    protected function gridColumns(): array
    {
        return [
            'id',
            [
                'attribute' => 'title',
                'format'    => 'raw',
                'value'     => static fn(Game $model): string => AdminHtml::gameLink($model, $model->id),
            ],
            [
                'attribute' => 'type',
                'filter'    => [
                    Game::TYPE_GAME  => 'Game',
                    Game::TYPE_DLC   => 'DLC',
                    Game::TYPE_MUSIC => 'Music',
                    Game::TYPE_DEMO  => 'Demo',
                ],
            ],
            [
                'attribute'      => 'steam_appid',
                'contentOptions' => ['class' => 'col-narrow'],
                'headerOptions'  => ['class' => 'col-narrow'],
                'filterOptions'  => ['class' => 'col-narrow'],
            ],
            [
                'attribute' => 'is_free',
                'format'    => 'boolean',
            ],
            'release_date',
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
