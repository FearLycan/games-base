<?php

namespace backend\modules\admin\controllers;

use backend\modules\admin\components\AdminHtml;
use backend\modules\admin\models\search\ReviewSearch;
use backend\models\Review;

class ReviewController extends CrudController
{
    public string $modelClass = Review::class;
    public string $searchModelClass = ReviewSearch::class;
    public string $modelLabel = 'Review';
    public string $modelLabelPlural = 'Reviews';

    // Reviews carry no toggleable flag.
    protected ?string $toggleAttribute = null;

    /** Purpose-built review detail screen. */
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
                'attribute'         => 'gameTitle',
                'label'             => 'Game',
                'format'            => 'raw',
                'filterInputOptions' => ['class' => 'form-control', 'placeholder' => 'Name or App ID'],
                'value'             => static fn(Review $model): string => AdminHtml::gameLink($model->game, $model->game_id),
            ],
            'total_positive',
            'total_negative',
            'total_reviews',
            $this->actionColumn(),
        ];
    }
}
