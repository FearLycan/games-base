<?php

use backend\models\User;
use backend\modules\admin\models\UserForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $controller backend\modules\admin\controllers\UserController */

$this->title = (string)$model->username ?: ('User #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$roleLabel = UserForm::roleOptions()[$model->role] ?? (string)$model->role;
$statusLabel = UserForm::statusOptions()[$model->status] ?? (string)$model->status;
$statusModifier = (int)$model->status === User::STATUS_ACTIVE ? 'active' : 'inactive';
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?> <span class="admin-pill admin-pill--<?= $statusModifier ?>"><?= Html::encode($statusLabel) ?></span></h1>
    <div class="d-flex gap-2">
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this user permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<section class="offer-panel">
    <h2 class="offer-panel__title">Account</h2>
    <dl class="offer-dl offer-dl--wide">
        <dt>Username</dt><dd><?= Html::encode((string)$model->username) ?></dd>
        <dt>Email</dt><dd><?= Html::a(Html::encode((string)$model->email), 'mailto:' . Html::encode((string)$model->email)) ?></dd>
        <dt>Role</dt><dd><?= Html::encode($roleLabel) ?></dd>
        <dt>Status</dt><dd><span class="admin-pill admin-pill--<?= $statusModifier ?>"><?= Html::encode($statusLabel) ?></span></dd>
        <dt>Created</dt><dd class="text-muted"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></dd>
        <dt>Updated</dt><dd class="text-muted"><?= Yii::$app->formatter->asDatetime($model->updated_at) ?></dd>
    </dl>
</section>
