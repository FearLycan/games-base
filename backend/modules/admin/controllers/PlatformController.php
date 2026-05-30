<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\components\AdminHtml;
use backend\modules\admin\models\search\PlatformSearch;
use backend\models\Platform;

class PlatformController extends CrudController
{
    public string $modelClass = Platform::class;
    public string $searchModelClass = PlatformSearch::class;
    public string $modelLabel = 'Platform';
    public string $modelLabelPlural = 'Platforms';

    // Platforms have no "status"; the inline switch drives the `available` flag.
    protected ?string $toggleAttribute = 'available';
    protected int $toggleOn = 1;
    protected int $toggleOff = 0;

    protected function statusFilterOptions(): array
    {
        return [1 => 'Available', 0 => 'Unavailable'];
    }

    /** Purpose-built platform detail screen. */
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
            'name',
            [
                'attribute'         => 'gameTitle',
                'label'             => 'Game',
                'format'            => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Name or App ID'],
                'value'             => static fn(Platform $model): string => AdminHtml::gameLink($model->game, $model->game_id),
            ],
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
