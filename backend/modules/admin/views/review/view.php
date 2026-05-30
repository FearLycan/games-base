<?php

use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Review */
/* @var $controller backend\modules\admin\controllers\ReviewController */

$this->title = 'Review #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Reviews', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$game = $model->game;
$positivePercent = $model->getPercentsOfPositive();
?>
<div class="admin-page-header">
    <h1>Review <span class="text-muted">#<?= $model->id ?></span></h1>
    <div class="d-flex gap-2">
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this review permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<div class="offer-grid">
    <section class="offer-panel">
        <h2 class="offer-panel__title">Matched game</h2>
        <?php if ($game): ?>
            <?= Html::a(
                Html::img($game->getHeader(), ['class' => 'offer-media', 'alt' => Html::encode((string)$game->title)]),
                ['/admin/game/view', 'id' => $game->id],
            ) ?>
            <dl class="offer-dl">
                <dt>Title</dt><dd><?= AdminHtml::gameLink($game, $game->id) ?></dd>
                <dt>Type</dt><dd><?= Html::encode((string)$game->type) ?: '—' ?></dd>
                <dt>Release</dt><dd><?= Html::encode((string)($game->release_date ?? '—')) ?></dd>
            </dl>
        <?php else: ?>
            <p class="text-danger">Linked game #<?= (int)$model->game_id ?> not found.</p>
        <?php endif; ?>
    </section>

    <section class="offer-panel">
        <h2 class="offer-panel__title">Reception</h2>
        <dl class="offer-dl">
            <dt>Sentiment</dt>
            <dd>
                <?php if ((int)$model->total_reviews > 0): ?>
                    <span class="admin-pill admin-pill--<?= $positivePercent >= 50 ? 'active' : 'inactive' ?>"><?= $positivePercent ?>% positive</span>
                <?php else: ?>—<?php endif; ?>
            </dd>
            <dt>Total reviews</dt><dd><?= number_format((int)$model->total_reviews) ?></dd>
            <dt>Positive</dt><dd><?= number_format((int)$model->total_positive) ?></dd>
            <dt>Negative</dt><dd><?= number_format((int)$model->total_negative) ?></dd>
            <dt>Created</dt><dd class="text-muted"><?= Html::encode((string)$model->created_at) ?></dd>
            <dt>Updated</dt><dd class="text-muted"><?= Html::encode((string)($model->updated_at ?? '—')) ?></dd>
        </dl>

        <?php if ($model->description): ?>
            <h3 class="offer-panel__subtitle">Summary</h3>
            <p class="mb-0"><?= Html::encode($model->description) ?></p>
        <?php endif; ?>
    </section>
</div>
