<?php

use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $game Game */
/* @var $rank int|null Optional 1-based chart position; renders a rank numeral on the cover. */

$rank = $rank ?? null;
$price = $game->getDisplayPrice();
?>

<a href="<?= Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]) ?>"
   class="group block w-56 sm:w-60 shrink-0 snap-start">
    <div class="relative aspect-[460/215] overflow-hidden rounded-xl bg-surface-2 ring-1 ring-line">
        <img src="<?= Html::encode($game->getHeader()) ?>"
             alt="<?= Html::encode($game->title) ?>"
             loading="lazy"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
        <?php if ($rank !== null): ?>
            <!-- Chart rank: a bottom scrim keeps the numeral legible on any cover art. -->
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-black/55 to-transparent"></div>
            <span class="absolute bottom-1.5 left-2 font-display text-xl font-bold leading-none tabular-nums text-white [text-shadow:0_1px_4px_rgba(0,0,0,0.55)]">
                <?= str_pad((string)$rank, 2, '0', STR_PAD_LEFT) ?>
            </span>
        <?php endif; ?>
        <?php if ($price !== null && $price->isDiscounted()): ?>
            <span class="absolute top-2 right-2 rounded-full bg-accent px-2 py-0.5 text-xs font-bold tabular-nums text-white shadow-sm">−<?= $price->discount ?>%</span>
        <?php endif; ?>
        <?php if ($game->is_preorder): ?>
            <span class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-indigo-500 px-2 py-0.5 text-[10px] font-bold text-white shadow-sm">
                <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                Pre-order
            </span>
        <?php endif; ?>
    </div>

    <div class="mt-2.5">
        <h3 class="font-display text-sm font-semibold text-fg truncate transition-colors group-hover:text-accent">
            <?= Html::encode($game->title) ?>
        </h3>
        <p class="mt-0.5 text-[11px] font-mono uppercase tracking-wider text-fg-subtle truncate">
            <?= Html::encode($game->getMainGenre()) ?>
        </p>

        <?php if ($price !== null): ?>
            <div class="mt-2 flex items-baseline gap-1.5">
                <?php if ($price->isDiscounted()): ?>
                    <span class="text-[11px] text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></span>
                <?php endif; ?>
                <span class="text-sm font-semibold tabular-nums <?= $price->free ? 'text-accent' : 'text-fg' ?>"><?= Html::encode($price->final) ?></span>
            </div>
            <?php if ($price->historicalLow): ?>
                <span class="mt-1.5 inline-flex items-center gap-1 rounded-sm bg-accent/10 px-1.5 py-px text-[10px] font-semibold text-accent" title="Lowest price ever recorded">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12"></path><path d="m6 12 6 6 6-6"></path><path d="M5 21h14"></path></svg>
                    Lowest ever
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</a>
