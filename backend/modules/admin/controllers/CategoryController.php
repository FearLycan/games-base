<?php

namespace backend\modules\admin\controllers;

use backend\models\Category;
use backend\modules\admin\models\search\CategorySearch;
use yii\helpers\Html;

class CategoryController extends CrudController
{
    public string $modelClass = Category::class;
    public string $searchModelClass = CategorySearch::class;
    public string $modelLabel = 'Category';
    public string $modelLabelPlural = 'Categories';

    /** Purpose-built category detail screen. */
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
                'attribute'      => 'hasImage',
                'label'          => 'Image',
                'format'         => 'raw',
                'filter'         => [1 => 'With image', 0 => 'Without image'],
                'contentOptions' => ['class' => 'col-narrow'],
                'value'          => static fn(Category $model): string => $model->image
                    ? Html::img($model->image, ['class' => 'grid-thumb', 'alt' => ''])
                    : '<span class="text-muted small"><i class="bi bi-image"></i> none</span>',
            ],
            'name',
            'games_count',
            $this->statusColumn(),
            $this->actionColumn(),
        ];
    }
}
