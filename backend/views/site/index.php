<?php

/* @var $this yii\web\View */

use common\models\User;
use yii\bootstrap5\Html;

$this->title = 'Backend';

$isAdmin = !Yii::$app->user->isGuest
    && Yii::$app->user->identity instanceof User
    && (int)Yii::$app->user->identity->role === User::ROLE_ADMIN;
?>
<div class="site-index">
    <div class="p-5 mb-4 bg-body-secondary rounded-3">
        <div class="container-fluid py-3">
            <h1 class="display-5 fw-bold">Backend</h1>
            <p class="col-md-8 fs-5">Management back office for the games catalogue.</p>
            <?php if ($isAdmin): ?>
                <?= Html::a('<i class="bi bi-speedometer2"></i> Open admin', ['/admin'], ['class' => 'btn btn-primary btn-lg']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>
