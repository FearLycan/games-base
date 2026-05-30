<?php

use backend\modules\admin\controllers\CrudController;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model yii\db\ActiveRecord */
/* @var $controller CrudController */

$this->title = $controller->modelLabel . ' #' . $model->getPrimaryKey();
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => $controller->modelLabelPlural, 'url' => ['index']];
$this->params['breadcrumbs'][] = (string)$model->getPrimaryKey();

$detail = ['model' => $model, 'options' => ['class' => 'table table-bordered table-striped detail-view']];
if ($controller->viewAttributes() !== null) {
    $detail['attributes'] = $controller->viewAttributes();
}
?>
<div class="admin-page-header d-flex justify-content-between align-items-center">
    <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
    <div class="d-flex gap-2">
        <?= Html::a('<i class="bi bi-arrow-left"></i> Back', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->getPrimaryKey()], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->getPrimaryKey()], [
            'class' => 'btn btn-danger',
            'data'  => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method'  => 'post',
            ],
        ]) ?>
    </div>
</div>

<?= DetailView::widget($detail) ?>
