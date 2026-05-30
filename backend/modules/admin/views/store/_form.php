<?php

use backend\models\Store;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Store */

$statuses = [
    Store::STATUS_ACTIVE   => 'Active',
    Store::STATUS_INACTIVE => 'Inactive',
];
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'website')->textInput(['maxlength' => true]) ?></div>
        </div>
        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'logo')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'order')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'status')->dropDownList($statuses) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
