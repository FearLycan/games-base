<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\models\search\PublisherSearch;
use backend\models\Publisher;

class PublisherController extends CrudController
{
    public string $modelClass = Publisher::class;
    public string $searchModelClass = PublisherSearch::class;
    public string $modelLabel = 'Publisher';
    public string $modelLabelPlural = 'Publishers';

    /** Purpose-built publisher detail screen. */
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
            'games_count',
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
