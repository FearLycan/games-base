<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\models\search\StoreSearch;
use backend\models\Store;

class StoreController extends CrudController
{
    public string $modelClass = Store::class;
    public string $searchModelClass = StoreSearch::class;
    public string $modelLabel = 'Store';
    public string $modelLabelPlural = 'Stores';

    protected ?string $toggleAttribute = 'status';
    protected int $toggleOn = Store::STATUS_ACTIVE;
    protected int $toggleOff = Store::STATUS_INACTIVE;

    /** Purpose-built store detail screen. */
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
            'website',
            'order',
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
