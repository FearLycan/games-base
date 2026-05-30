<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\models\search\DeveloperSearch;
use backend\models\Developer;

class DeveloperController extends CrudController
{
    public string $modelClass = Developer::class;
    public string $searchModelClass = DeveloperSearch::class;
    public string $modelLabel = 'Developer';
    public string $modelLabelPlural = 'Developers';

    /** Purpose-built developer detail screen. */
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
