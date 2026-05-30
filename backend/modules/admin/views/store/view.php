<?php

use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Store */
/* @var $controller backend\modules\admin\controllers\StoreController */

$this->title = (string)$model->name ?: ('Store #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Stores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$offerCount = (int)$model->getGameOffers()->count();
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?> <?= AdminHtml::activePill((int)$model->status) ?></h1>
    <div class="d-flex gap-2">
        <?php if ($model->website): ?>
            <?= Html::a('<i class="bi bi-box-arrow-up-right"></i> Visit', $model->website, ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener']) ?>
        <?php endif; ?>
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this store permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<div class="offer-grid">
    <section class="offer-panel">
        <h2 class="offer-panel__title">Logo</h2>
        <?php if ($model->logo): ?>
            <img src="<?= Html::encode($model->logo) ?>" class="offer-media" alt="<?= Html::encode((string)$model->name) ?>">
        <?php else: ?>
            <div class="image-empty">
                <i class="bi bi-image"></i>
                <span>No logo set</span>
            </div>
        <?php endif; ?>
    </section>

    <section class="offer-panel">
        <h2 class="offer-panel__title">Details</h2>
        <dl class="offer-dl">
            <dt>Name</dt><dd><?= Html::encode((string)$model->name) ?></dd>
            <dt>Website</dt>
            <dd><?= $model->website
                    ? Html::a(Html::encode($model->website) . ' <i class="bi bi-box-arrow-up-right"></i>', $model->website, ['target' => '_blank', 'rel' => 'noopener'])
                    : '—' ?></dd>
            <dt>Status</dt><dd><?= AdminHtml::activePill((int)$model->status) ?></dd>
            <dt>Order</dt><dd><?= (int)$model->order ?></dd>
            <dt>Offers</dt>
            <dd><?= Html::a(number_format($offerCount), ['/admin/game-offer/index', 'GameOfferSearch[storeName]' => $model->name]) ?></dd>
            <dt>Created</dt><dd class="text-muted"><?= Html::encode((string)$model->created_at) ?></dd>
            <dt>Updated</dt><dd class="text-muted"><?= Html::encode((string)($model->updated_at ?? '—')) ?></dd>
        </dl>
    </section>
</div>
