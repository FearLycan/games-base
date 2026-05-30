<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Publisher */

$statuses = [1 => 'Active', 0 => 'Inactive'];
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'profile_id')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'status')->dropDownList($statuses, ['prompt' => '—']) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
