<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\models\search\TagSearch;
use backend\models\Tag;

class TagController extends CrudController
{
    public string $modelClass = Tag::class;
    public string $searchModelClass = TagSearch::class;
    public string $modelLabel = 'Tag';
    public string $modelLabelPlural = 'Tags';

    /** Purpose-built tag detail screen. */
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
            'slug',
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
