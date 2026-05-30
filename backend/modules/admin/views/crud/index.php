<?php

use backend\modules\admin\controllers\CrudController;
use yii\bootstrap5\LinkPager;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel yii\base\Model */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $columns array */
/* @var $controller CrudController */

$this->title = $controller->modelLabelPlural;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= Html::a('<i class="bi bi-plus-lg"></i> New ' . Html::encode($controller->modelLabel), ['create'], ['class' => 'btn btn-success']) ?>
</div>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'tableOptions' => ['class' => 'table align-middle mb-0'],
    'layout'       => "{summary}\n{items}\n{pager}",
    'pager'        => ['class' => LinkPager::class, 'options' => ['class' => 'pagination justify-content-center']],
    'columns'      => $columns,
]) ?>
