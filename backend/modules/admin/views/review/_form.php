<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Review */
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-3"><?= $form->field($model, 'game_id')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'total_positive')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'total_negative')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'total_reviews')->textInput() ?></div>
        </div>
        <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
