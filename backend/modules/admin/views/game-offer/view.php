<?php

use backend\models\GameOffer;
use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\GameOffer */
/* @var $controller backend\modules\admin\controllers\GameOfferController */

$this->title = 'Offer #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Offers', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Review';

$game = $model->game;
$store = $model->store;
$isReview = (int)$model->status === GameOffer::STATUS_REVIEW;
?>
<div class="admin-page-header">
    <h1>Review offer <span class="text-muted">#<?= $model->id ?></span> <?= AdminHtml::offerStatusPill((int)$model->status) ?></h1>
    <?= Html::a('<i class="bi bi-arrow-left"></i> Queue', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
</div>

<div class="offer-review">
    <!-- The offer link, front and centre -->
    <div class="offer-cta">
        <?= Html::a('<i class="bi bi-box-arrow-up-right"></i> Open offer page', $model->url, [
            'class'  => 'btn btn-success btn-lg',
            'target' => '_blank',
            'rel'    => 'noopener',
        ]) ?>
        <?= Html::a(Html::encode($model->url), $model->url, [
            'class'  => 'offer-cta__url',
            'target' => '_blank',
            'rel'    => 'noopener',
        ]) ?>
    </div>

    <div class="offer-grid">
        <!-- Game it was matched to -->
        <section class="offer-panel">
            <h2 class="offer-panel__title">Matched game</h2>
            <?php if ($game): ?>
                <?= Html::a(
                    Html::img($game->getHeader(), ['class' => 'offer-media', 'alt' => Html::encode((string)$game->title)]),
                    ['/admin/game/view', 'id' => $game->id],
                ) ?>
                <dl class="offer-dl">
                    <dt>Title</dt>
                    <dd><?= AdminHtml::gameLink($game, $game->id) ?></dd>
                    <dt>Type</dt>
                    <dd><?= Html::encode((string)$game->type) ?: '—' ?></dd>
                    <dt>Steam price</dt>
                    <dd><?= Html::encode((string)($game->getPriceLabel() ?? '—')) ?></dd>
                    <dt>Release</dt>
                    <dd><?= Html::encode((string)($game->release_date ?? '—')) ?></dd>
                    <dt>Steam</dt>
                    <dd><?php if ($game->steam_appid): ?>
                        <?= Html::a('Store page <i class="bi bi-box-arrow-up-right"></i>', $game->getSteamUrl(), ['target' => '_blank', 'rel' => 'noopener']) ?>
                    <?php else: ?>—<?php endif; ?></dd>
                </dl>
            <?php else: ?>
                <p class="text-danger">Linked game #<?= (int)$model->game_id ?> not found.</p>
            <?php endif; ?>
        </section>

        <!-- Offer details -->
        <section class="offer-panel">
            <h2 class="offer-panel__title">Offer details</h2>
            <dl class="offer-dl">
                <dt>Store</dt>
                <dd><?= $store
                        ? Html::a(Html::encode($store->name), ['/admin/store/view', 'id' => $store->id])
                        : '#' . (int)$model->store_id ?></dd>
                <dt>Region</dt>
                <dd><?= Html::encode((string)($model->region ?? '—')) ?: '—' ?></dd>
                <dt>Edition</dt>
                <dd><?= Html::encode((string)($model->edition ?? '—')) ?: '—' ?></dd>
                <dt>External ID</dt>
                <dd><?= Html::encode((string)($model->external_id ?? '—')) ?: '—' ?></dd>
                <dt>Added</dt>
                <dd><?= Html::encode((string)$model->created_at) ?></dd>
            </dl>

            <h3 class="offer-panel__subtitle">Prices</h3>
            <?php if (count($model->prices)): ?>
                <table class="offer-prices">
                    <thead>
                    <tr><th>Currency</th><th>Price</th><th>Was</th><th>Disc.</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($model->prices as $price): ?>
                        <tr>
                            <td><strong><?= Html::encode(strtoupper((string)$price->currency)) ?></strong></td>
                            <td><?= Html::encode((string)($price->getFinalPriceLabel() ?? '—')) ?></td>
                            <td class="text-muted"><?= Html::encode((string)($price->getInitialPriceLabel() ?? '—')) ?></td>
                            <td><?php $d = $price->getDiscountPercent(); ?>
                                <?= $d > 0 ? '<span class="admin-pill admin-pill--active">-' . $d . '%</span>' : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted mb-0">No prices recorded for this offer.</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- Decision bar -->
    <div class="offer-actions">
        <?php if ($isReview): ?>
            <?= Html::beginForm(['accept', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('<i class="bi bi-check2-circle"></i> Accept', ['class' => 'btn btn-success btn-lg']) ?>
            <?= Html::endForm() ?>
            <?= Html::beginForm(['reject', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('<i class="bi bi-x-circle"></i> Reject', [
                'class' => 'btn btn-outline-danger btn-lg',
                'data'  => ['confirm' => 'Reject this offer? It will be set to inactive.'],
            ]) ?>
            <?= Html::endForm() ?>
        <?php elseif ((int)$model->status === GameOffer::STATUS_ACTIVE): ?>
            <?= Html::beginForm(['reject', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('<i class="bi bi-x-circle"></i> Deactivate', [
                'class' => 'btn btn-outline-danger btn-lg',
                'data'  => ['confirm' => 'Deactivate this offer?'],
            ]) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>

        <span class="offer-actions__spacer"></span>

        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this offer permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>
