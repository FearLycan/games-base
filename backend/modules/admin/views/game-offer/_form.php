<?php

use backend\models\GameOffer;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\GameOffer */

$statuses = [
    GameOffer::STATUS_REVIEW   => 'Review',
    GameOffer::STATUS_ACTIVE   => 'Active',
    GameOffer::STATUS_INACTIVE => 'Inactive',
];
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-3"><?= $form->field($model, 'game_id')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'store_id')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'status')->dropDownList($statuses) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'order')->textInput() ?></div>
        </div>
        <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>
        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'region')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-6"><?= $form->field($model, 'edition')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
