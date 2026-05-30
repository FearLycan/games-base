<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\models\search\GenreSearch;
use backend\models\Genre;

class GenreController extends CrudController
{
    public string $modelClass = Genre::class;
    public string $searchModelClass = GenreSearch::class;
    public string $modelLabel = 'Genre';
    public string $modelLabelPlural = 'Genres';

    /** Purpose-built genre detail screen. */
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
