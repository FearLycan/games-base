<?php

use backend\models\Game;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Game */

$statuses = [
    Game::STATUS_ACTIVE        => 'Active',
    Game::STATUS_WAIT_TO_SYNC  => 'Wait to sync',
    Game::STATUS_INACTIVE      => 'Inactive',
    Game::STATUS_SUCCESS_FALSE => 'Sync failed',
];
$types = [
    Game::TYPE_GAME  => 'Game',
    Game::TYPE_DLC   => 'DLC',
    Game::TYPE_MUSIC => 'Music',
    Game::TYPE_DEMO  => 'Demo',
];
?>
<div class="card">
    <div class="card-body">
        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'type')->dropDownList($types, ['prompt' => '—']) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'status')->dropDownList($statuses) ?></div>
        </div>

        <div class="row">
            <div class="col-md-3"><?= $form->field($model, 'steam_appid')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'required_age')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'steam_price_initial')->textInput() ?></div>
            <div class="col-md-3"><?= $form->field($model, 'steam_price_final')->textInput() ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'website')->textInput(['maxlength' => true]) ?></div>
            <div class="col-md-3"><?= $form->field($model, 'release_date')->textInput() ?></div>
            <div class="col-md-3 d-flex align-items-end"><?= $form->field($model, 'is_free')->checkbox() ?></div>
        </div>

        <?= $form->field($model, 'short_description')->textarea(['rows' => 3]) ?>
        <?= $form->field($model, 'about_the_game')->textarea(['rows' => 4]) ?>

        <div class="mt-3">
            <?= Html::submitButton('<i class="bi bi-save"></i> Save', ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-link']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
