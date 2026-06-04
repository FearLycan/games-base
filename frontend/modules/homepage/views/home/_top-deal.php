<?php

use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $game Game */

$price = $game->getDisplayPrice();
?>

<a href="<?= Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]) ?>"
   class="group block relative overflow-hidden rounded-2xl bg-canvas ring-1 ring-line shadow-sm transition hover:ring-line-strong hover:shadow-xl">

    <div class="relative aspect-[460/215] overflow-hidden bg-surface-2">
        <img src="<?= Html::encode($game->getHeader()) ?>"
             alt="<?= Html::encode($game->title) ?>"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/45 via-transparent to-transparent" aria-hidden="true"></div>

        <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-canvas/90 px-3 py-1 text-[11px] font-mono font-semibold uppercase tracking-[0.14em] text-accent backdrop-blur ring-1 ring-accent/15">
            <span class="h-1.5 w-1.5 rounded-full bg-accent soft-pulse"></span>
            Top deal
        </span>

        <?php if ($price !== null && $price->isDiscounted()): ?>
            <span class="absolute top-3 right-3 rounded-full bg-accent px-3 py-1 text-sm font-bold tabular-nums text-white shadow-sm">
                −<?= $price->discount ?>%
            </span>
        <?php endif; ?>
    </div>

    <div class="p-5">
        <h3 class="font-display text-lg font-bold text-fg truncate transition-colors group-hover:text-accent">
            <?= Html::encode($game->title) ?>
        </h3>
        <p class="mt-0.5 text-xs font-mono uppercase tracking-wider text-fg-subtle truncate">
            <?= Html::encode($game->getMainGenre()) ?>
        </p>
        <?php if ($price !== null && $price->historicalLow): ?>
            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent" title="Lowest price ever recorded">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12"></path><path d="m6 12 6 6 6-6"></path><path d="M5 21h14"></path></svg>
                Lowest price ever
            </span>
        <?php endif; ?>

        <?php if ($price !== null): ?>
            <div class="mt-4 flex items-end justify-between gap-3">
                <div class="leading-tight">
                    <?php if ($price->isDiscounted()): ?>
                        <span class="text-sm text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></span>
                    <?php endif; ?>
                    <div class="text-2xl font-bold tabular-nums text-fg"><?= Html::encode($price->final) ?></div>
                    <?php if ($price->store !== null): ?>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px] font-mono uppercase tracking-wider text-fg-subtle">
                            <span>at <?= Html::encode($price->store) ?></span>
                            <?php if ($price->storeOfficial): ?>
                                <span class="inline-flex items-center rounded-sm bg-accent/10 px-1 py-px font-semibold text-accent">Official</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-fg px-4 py-2 text-sm font-semibold text-canvas transition group-hover:bg-fg/90">
                    View deal <span aria-hidden="true" class="transition group-hover:translate-x-0.5">→</span>
                </span>
            </div>
        <?php endif; ?>
    </div>
</a>
