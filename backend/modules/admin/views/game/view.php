<?php

use backend\modules\admin\components\AdminHtml;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Game */
/* @var $controller backend\modules\admin\controllers\GameController */

$this->title = (string)$model->title ?: ('Game #' . $model->id);
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/**
 * Renders a row of pills for a related collection, linking each item to its
 * admin view when a route is given.
 *
 * @param object[] $items
 */
$pills = static function (array $items, ?string $route = null): string {
    if (!$items) {
        return '<span class="text-muted">—</span>';
    }
    $out = [];
    foreach ($items as $item) {
        $name = Html::encode((string)($item->name ?? $item->title ?? $item->id));
        $out[] = $route
            ? Html::a($name, [$route, 'id' => $item->id], ['class' => 'pill pill--link'])
            : Html::tag('span', $name, ['class' => 'pill']);
    }
    return '<div class="pill-group">' . implode('', $out) . '</div>';
};

$discount = $model->getDiscountPercent();
$review = $model->review;
$metacritic = $model->metacritic;
$offers = $model->gameOffers;
$screenshots = $model->getScreenshots();
?>
<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?> <?= AdminHtml::gameStatusPill((int)$model->status) ?></h1>
    <div class="d-flex gap-2">
        <?php if ($model->steam_appid): ?>
            <?= Html::a('<i class="bi bi-steam"></i> Steam', $model->getSteamUrl(), ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener']) ?>
        <?php endif; ?>
        <?= Html::a('<i class="bi bi-pencil-square"></i> Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-trash"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-outline-danger',
            'data'  => ['confirm' => 'Delete this game permanently?', 'method' => 'post'],
        ]) ?>
    </div>
</div>

<div class="offer-grid">
    <section class="offer-panel">
        <h2 class="offer-panel__title">Overview</h2>
        <img src="<?= Html::encode($model->getHeader()) ?>" class="offer-media" alt="<?= Html::encode((string)$model->title) ?>">
        <dl class="offer-dl">
            <dt>Type</dt>
            <dd><?= Html::encode((string)$model->type) ?: '—' ?></dd>
            <dt>Release</dt>
            <dd><?= Html::encode((string)($model->release_date ?? '—')) ?: '—' ?></dd>
            <dt>Price</dt>
            <dd>
                <?php if ((int)$model->is_free === 1): ?>
                    <span class="admin-pill admin-pill--active">Free to Play</span>
                <?php else: ?>
                    <strong><?= Html::encode((string)($model->getPriceLabel() ?? '—')) ?></strong>
                    <?php if ($discount > 0): ?>
                        <span class="text-muted text-decoration-line-through ms-1"><?= Html::encode($model->getInitialPrice()) ?></span>
                        <span class="admin-pill admin-pill--active ms-1">-<?= $discount ?>%</span>
                    <?php endif; ?>
                <?php endif; ?>
            </dd>
            <dt>Steam App ID</dt>
            <dd><?= $model->steam_appid
                    ? Html::a(Html::encode((string)$model->steam_appid) . ' <i class="bi bi-box-arrow-up-right"></i>', $model->getSteamUrl(), ['target' => '_blank', 'rel' => 'noopener'])
                    : '—' ?></dd>
            <dt>Steam Deck</dt>
            <dd><?= Html::encode((string)($model->getSteamDecksStatusName() ?: '—')) ?></dd>
            <dt>Required age</dt>
            <dd><?= (int)$model->required_age ?: '—' ?></dd>
            <dt>Slug</dt>
            <dd class="text-muted"><?= Html::encode((string)$model->slug) ?: '—' ?></dd>
            <dt>Synced</dt>
            <dd class="text-muted"><?= Html::encode((string)($model->synchronized_at ?? '—')) ?></dd>
        </dl>
    </section>

    <section class="offer-panel">
        <h2 class="offer-panel__title">Classification</h2>
        <dl class="offer-dl offer-dl--wide">
            <dt>Genres</dt><dd><?= $pills($model->genres) ?></dd>
            <dt>Categories</dt><dd><?= $pills($model->categories) ?></dd>
            <dt>Developers</dt><dd><?= $pills($model->developers, '/admin/developer/view') ?></dd>
            <dt>Publishers</dt><dd><?= $pills($model->publishers, '/admin/publisher/view') ?></dd>
            <dt>Platforms</dt><dd><?= $pills($model->availablePlatforms) ?></dd>
            <dt>Tags</dt><dd><?= $pills($model->tags) ?></dd>
        </dl>

        <h3 class="offer-panel__subtitle">Reception</h3>
        <dl class="offer-dl">
            <dt>Metacritic</dt>
            <dd><?= $metacritic && $metacritic->score ? (int)$metacritic->score . ' / 100' : '—' ?></dd>
            <dt>Reviews</dt>
            <dd><?php if ($review && $review->total_reviews): ?>
                <span class="admin-pill admin-pill--active"><?= $review->getPercentsOfPositive() ?>% positive</span>
                <span class="text-muted ms-1"><?= number_format((int)$review->total_reviews) ?> reviews</span>
            <?php else: ?>—<?php endif; ?></dd>
        </dl>
    </section>
</div>

<?php if ($model->short_description || $model->about_the_game): ?>
    <section class="offer-panel mb-3">
        <h2 class="offer-panel__title">Description</h2>
        <?php if ($model->short_description): ?>
            <p class="lead fs-6"><?= Html::encode($model->short_description) ?></p>
        <?php endif; ?>
        <?php if ($model->about_the_game): ?>
            <div class="game-richtext"><?= $model->about_the_game ?></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="offer-panel mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="offer-panel__title mb-0">Offers <span class="text-muted">(<?= count($offers) ?>)</span></h2>
        <?= Html::a('Manage offers <i class="bi bi-arrow-right"></i>', ['/admin/game-offer/index', 'GameOfferSearch[gameTitle]' => $model->steam_appid ?: $model->title], ['class' => 'small text-decoration-none']) ?>
    </div>
    <?php if (count($offers)): ?>
        <table class="offer-prices">
            <thead>
            <tr><th>Store</th><th>Status</th><th>Region</th><th>Edition</th><th>Prices</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $offer): ?>
                <tr>
                    <td><?= Html::a(Html::encode($offer->store->name ?? ('#' . $offer->store_id)), ['/admin/game-offer/view', 'id' => $offer->id]) ?></td>
                    <td><?= AdminHtml::offerStatusPill((int)$offer->status) ?></td>
                    <td><?= Html::encode((string)($offer->region ?? '—')) ?: '—' ?></td>
                    <td><?= Html::encode((string)($offer->edition ?? '—')) ?: '—' ?></td>
                    <td><?php
                        $parts = [];
                        foreach ($offer->prices as $price) {
                            $parts[] = Html::encode(strtoupper((string)$price->currency) . ' ' . ($price->getFinalPriceLabel() ?? '—'));
                        }
                        echo $parts ? implode(' · ', $parts) : '—';
                        ?></td>
                    <td><?= Html::a('<i class="bi bi-box-arrow-up-right"></i>', $offer->url, ['target' => '_blank', 'rel' => 'noopener', 'title' => 'Open offer']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mb-0">No offers recorded for this game.</p>
    <?php endif; ?>
</section>

<?php if (count($screenshots)): ?>
    <section class="offer-panel">
        <h2 class="offer-panel__title">Screenshots</h2>
        <div class="shot-grid">
            <?php foreach (array_slice($screenshots, 0, 8) as $shot): ?>
                <a href="<?= Html::encode($shot->url) ?>" target="_blank" rel="noopener" class="shot">
                    <img src="<?= Html::encode($shot->url) ?>" alt="" loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
