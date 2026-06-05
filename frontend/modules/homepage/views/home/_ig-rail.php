<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $eyebrow string */
/* @var $label string */
/* @var $tagline string */
/* @var $games \common\models\Game[] */

/**
 * A ranked Instant Gaming listing rail (trending, pre-orders, …).
 *
 * Same carousel mechanics as the other homepage rails (data-carousel +
 * _game-card); the only difference is that each card is passed its chart rank, so
 * the shelf reads as a "what's hot" list while staying visually consistent.
 *
 * Self-guarding: with nothing to show we render nothing at all (no empty heading
 * or stray carousel), so callers can hand us a list without pre-checking it.
 */
if (empty($games)) {
    return;
}
?>
<section class="mt-16 sm:mt-20" data-carousel>
    <div class="flex items-end justify-between gap-4 mb-6">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 rounded-full bg-accent/10 ring-1 ring-accent/15 px-3 py-1 text-xs font-medium text-accent">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4.5 13.2a.7.7 0 0 0 .56 1.12H11l-1 7.68 8.5-11.2a.7.7 0 0 0-.56-1.12H12l1-7.68Z"/></svg>
                <?= Html::encode($eyebrow) ?>
            </p>
            <h2 class="mt-4 font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight text-balance">
                <?= Html::encode($label) ?>
            </h2>
            <?php if ($tagline !== ''): ?>
                <p class="mt-3 text-lg text-fg-muted text-pretty"><?= Html::encode($tagline) ?></p>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex items-center gap-2 shrink-0">
            <button type="button" data-carousel-prev aria-label="Scroll left" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
            </button>
            <button type="button" data-carousel-next aria-label="Scroll right" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
            </button>
        </div>
    </div>

    <div data-carousel-track class="flex gap-4 overflow-x-auto scroll-smooth snap-x pb-2 -mx-1 px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <?php foreach ($games as $i => $game): ?>
            <?= $this->render('_game-card', ['game' => $game, 'rank' => $i + 1]) ?>
        <?php endforeach; ?>
    </div>
</section>
