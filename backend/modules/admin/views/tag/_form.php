<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Tag */

$statuses = [1 => 'Active', 0 => 'Inactive'];
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-8"><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-4"><?= $form->field($model, 'status')->dropDownList($statuses, ['prompt' => '—']) ?></div>
        </div>
        <?= $form->field($model, 'image')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
