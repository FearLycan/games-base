<?php

use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Developer */
/* @var $controller backend\modules\admin\controllers\DeveloperController */

$this->title = (string)$model->name ?: ('Developer #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Developers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$games = $model->getGames()->orderBy(['title' => SORT_ASC])->limit(24)->all();
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?> <?= AdminHtml::activePill((int)$model->status) ?></h1>
    <div class="d-flex gap-2">
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this developer permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<section class="offer-panel mb-3">
    <h2 class="offer-panel__title">Details</h2>
    <dl class="offer-dl offer-dl--wide">
        <dt>Name</dt><dd><?= Html::encode((string)$model->name) ?></dd>
        <dt>Slug</dt><dd class="text-muted"><?= Html::encode((string)$model->slug) ?: '—' ?></dd>
        <dt>Status</dt><dd><?= AdminHtml::activePill((int)$model->status) ?></dd>
        <dt>Profile ID</dt><dd><?= $model->profile_id ? Html::encode((string)$model->profile_id) : '—' ?></dd>
        <dt>Games</dt><dd><?= number_format((int)$model->games_count) ?></dd>
        <dt>Created</dt><dd class="text-muted"><?= Html::encode((string)$model->created_at) ?></dd>
        <dt>Updated</dt><dd class="text-muted"><?= Html::encode((string)($model->updated_at ?? '—')) ?></dd>
    </dl>
</section>

<section class="offer-panel">
    <h2 class="offer-panel__title">Games <span class="text-muted">(<?= number_format((int)$model->games_count) ?>)</span></h2>
    <?php if ($games): ?>
        <div class="pill-group">
            <?php foreach ($games as $game): ?>
                <?= Html::a(Html::encode((string)$game->title), ['/admin/game/view', 'id' => $game->id], ['class' => 'pill pill--link']) ?>
            <?php endforeach; ?>
        </div>
        <?php if ((int)$model->games_count > count($games)): ?>
            <p class="text-muted small mt-2 mb-0">Showing first <?= count($games) ?>.</p>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-muted mb-0">No games linked to this developer.</p>
    <?php endif; ?>
</section>
