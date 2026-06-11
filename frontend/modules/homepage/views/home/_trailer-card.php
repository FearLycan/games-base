<?php

use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $game Game */

/**
 * A trailer card for the homepage "In motion" strip. The whole card is a button
 * that opens the shared lightbox (data-trailer-open + data-embed); the title
 * links through to the game page separately so the card stays useful either way.
 */
$trailer = $game->getTrailer();
if ($trailer === null) {
    return;
}
$price = $game->getDisplayPrice();
$gameUrl = Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]);
?>

<div class="group w-72 sm:w-80 shrink-0 snap-start">
    <button type="button"
            data-trailer-open
            data-embed="<?= Html::encode($trailer->getEmbedUrl()) ?>"
            data-title="<?= Html::encode($game->title) ?>"
            data-href="<?= Html::encode($gameUrl) ?>"
            aria-label="Play trailer for <?= Html::encode($game->title) ?>"
            class="relative block w-full aspect-video overflow-hidden rounded-xl bg-surface-2 ring-1 ring-line transition hover:ring-line-strong hover:shadow-lg">
        <img src="<?= Html::encode($trailer->getThumbnailUrl()) ?>"
             alt=""
             loading="lazy"
             class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.05]">
        <!-- Legibility scrim + play affordance -->
        <span class="absolute inset-0 bg-gradient-to-t from-black/45 via-transparent to-black/10"></span>
        <span class="absolute inset-0 grid place-items-center">
            <span class="grid h-14 w-14 place-items-center rounded-full bg-white/90 text-fg shadow-lg ring-1 ring-black/5 backdrop-blur transition duration-300 group-hover:scale-110 group-hover:bg-accent group-hover:text-white">
                <svg class="h-5 w-5 translate-x-[1px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>
            </span>
        </span>

        <?php if ($game->is_preorder): ?>
            <span class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-indigo-500 px-2 py-0.5 text-[11px] font-bold text-white shadow-sm">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                Pre-order
            </span>
        <?php endif; ?>
        <?php if ($price !== null && $price->isDiscounted()): ?>
            <span class="absolute top-2 right-2 rounded-full bg-accent px-2 py-0.5 text-xs font-bold tabular-nums text-white shadow-sm">−<?= $price->discount ?>%</span>
        <?php endif; ?>
    </button>

    <div class="mt-2.5 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-display text-sm font-semibold text-fg truncate">
                <a href="<?= $gameUrl ?>" class="transition-colors hover:text-accent"><?= Html::encode($game->title) ?></a>
            </h3>
            <p class="mt-0.5 text-[11px] font-mono uppercase tracking-wider text-fg-subtle truncate">
                <?= Html::encode($game->getMainGenre()) ?>
            </p>
        </div>
        <?php if ($price !== null): ?>
            <div class="shrink-0 text-right leading-tight">
                <div class="text-sm font-semibold tabular-nums <?= $price->free ? 'text-accent' : 'text-fg' ?>"><?= Html::encode($price->final) ?></div>
                <?php if ($price->isDiscounted()): ?>
                    <div class="text-[11px] text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
