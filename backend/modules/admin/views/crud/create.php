<?php

use backend\modules\admin\controllers\CrudController;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model yii\db\ActiveRecord */
/* @var $controller CrudController */

$this->title = 'Create ' . $controller->modelLabel;
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => $controller->modelLabelPlural, 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Create';
?>
<div class="admin-page-header">
    <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
</div>

<?php // Pass $controller as context so '_form' resolves to the per-model views/<id>/_form.php ?>
<?= $this->render('_form', ['model' => $model], $controller) ?>
