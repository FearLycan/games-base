<?php

use backend\models\IpAddress;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\IpAddress */

$blockedOptions = [
    IpAddress::NOT_BLOCKED => 'Allowed',
    IpAddress::BLOCKED     => 'Blocked',
];
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'ip')->textInput([
                    'maxlength' => true,
                    // The IP is the unique key and is set when first observed — keep
                    // it editable only while adding a new (manual) entry.
                    'readonly'  => !$model->isNewRecord,
                ]) ?>
            </div>
            <div class="col-md-3"><?= $form->field($model, 'country')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'is_blocked')->dropDownList($blockedOptions) ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'note')->textInput(['maxlength' => true])->hint('Who is this IP — a known crawler, a partner, an abuser…') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'block_reason')->textInput(['maxlength' => true]) ?></div>
        </div>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
