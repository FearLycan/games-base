<?php

use backend\models\GameSale;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\GameSale */
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-4"><?= $form->field($model, 'game_id')->textInput() ?></div>
            <div class="col-md-4"><?= $form->field($model, 'type')->dropDownList(GameSale::typeLabels()) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'order')->textInput() ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
