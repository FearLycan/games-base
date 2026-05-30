<?php

use backend\modules\admin\controllers\CrudController;
use backend\modules\admin\models\UserForm;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $form UserForm */
/* @var $controller CrudController */

$isNew = $form->isNewRecord();
$this->title = ($isNew ? 'Create ' : 'Update ') . $controller->modelLabel;
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin']];
$this->params['breadcrumbs'][] = ['label' => $controller->modelLabelPlural, 'url' => ['index']];
$this->params['breadcrumbs'][] = $isNew ? 'Create' : 'Update';
?>
<div class="admin-page-header">
    <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <?php $activeForm = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $activeForm->field($form, 'username')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $activeForm->field($form, 'email')->textInput(['maxlength' => true]) ?></div>
        </div>
        <div class="row">
            <div class="col-md-4"><?= $activeForm->field($form, 'role')->dropDownList(UserForm::roleOptions()) ?></div>
            <div class="col-md-4"><?= $activeForm->field($form, 'status')->dropDownList(UserForm::statusOptions()) ?></div>
            <div class="col-md-4"><?= $activeForm->field($form, 'password')->passwordInput(['autocomplete' => 'new-password']) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
