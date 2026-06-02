<?php

/* @var $model common\models\UserWishlist */

use yii\helpers\Html;
use yii\helpers\Url;

$w = $model;
$inCatalog = $w->isInCatalog();
$href = $inCatalog
    ? Url::to(['/game/game/view', 'id' => $w->game->steam_appid, 'slug' => $w->game->slug])
    : $w->getSteamStoreUrl();
$price = $inCatalog ? $w->game->getDisplayPrice() : null;
?>
<a href="<?= Html::encode($href) ?>"
   <?= $inCatalog ? '' : 'target="_blank" rel="noopener noreferrer"' ?>
   class="group flex flex-col overflow-hidden rounded-xl border border-line bg-surface/40 shadow-sm transition-transform duration-150 will-change-transform hover:-translate-y-0.5">
    <span class="block aspect-[460/215] overflow-hidden bg-surface-2">
        <img src="<?= Html::encode($w->getHeaderImageUrl()) ?>"
             alt=""
             loading="lazy"
             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
    </span>
    <span class="flex flex-1 flex-col gap-1 p-3">
        <span class="truncate text-sm font-medium text-fg" title="<?= Html::encode($w->getDisplayTitle()) ?>"><?= Html::encode($w->getDisplayTitle()) ?></span>
        <span class="mt-0.5 flex items-center gap-2">
            <?php if ($price !== null && $price->free): ?>
                <span class="text-sm font-semibold text-accent">Free</span>
            <?php elseif ($price !== null): ?>
                <?php if ($price->isDiscounted()): ?>
                    <span class="rounded bg-accent/10 px-1.5 py-0.5 text-[11px] font-semibold text-accent tabular-nums">-<?= $price->discount ?>%</span>
                    <span class="text-xs text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></span>
                <?php endif; ?>
                <span class="text-sm font-semibold text-fg tabular-nums"><?= Html::encode($price->final) ?></span>
            <?php else: ?>
                <span class="text-xs text-fg-subtle">View on Steam</span>
            <?php endif; ?>
        </span>
    </span>
</a>
