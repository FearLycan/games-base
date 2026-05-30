<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Platform */
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'game_id')->textInput() ?></div>
            <div class="col-md-4 d-flex align-items-end"><?= $form->field($model, 'available')->checkbox() ?></div>
        </div>
        <?= $form->field($model, 'requirements_minimum')->textarea(['rows' => 3]) ?>
        <?= $form->field($model, 'requirements_recommended')->textarea(['rows' => 3]) ?>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
