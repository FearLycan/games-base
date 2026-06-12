<?php

use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model Game */

$year = '';
if ($model->release_date) {
    $ts = strtotime($model->release_date);
    if ($ts) {
        $year = date('Y', $ts);
    }
}

$saleLabel = null;
if ($model->isBestseller()) {
    $saleLabel = ['text' => 'Bestseller', 'class' => 'bg-rose-600/95'];
} elseif ($model->isNewAndNoteworthy()) {
    $saleLabel = ['text' => 'New', 'class' => 'bg-sky-600/95'];
} elseif ($model->isPopularUpcoming()) {
    $saleLabel = ['text' => 'Upcoming', 'class' => 'bg-indigo-600/95'];
} elseif ($model->isIgTrending()) {
    $saleLabel = ['text' => 'Trending', 'class' => 'bg-emerald-600/95'];
}

$price = $model->getDisplayPrice();
?>

<a href="<?= Url::to(['/game/game/view', 'id' => $model->steam_appid, 'slug' => $model->slug]) ?>"
   class="game-card group block">
    <div class="game-card-image relative aspect-[460/215] overflow-hidden rounded-xl bg-surface-2 ring-1 ring-line"
         data-shots
         data-shots-id="<?= (int)$model->steam_appid ?>">
        <img src="<?= Html::encode($model->getHeader()) ?>"
             alt="<?= Html::encode($model->title) ?>"
             loading="lazy"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">

        <?php if ($saleLabel): ?>
            <span class="absolute top-2 left-2 z-20 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-[0.14em] text-white backdrop-blur <?= $saleLabel['class'] ?>">
                <span class="h-1 w-1 rounded-full bg-white/80"></span>
                <?= Html::encode($saleLabel['text']) ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="mt-3 px-0.5">
        <h3 class="font-display text-[15px] font-semibold text-fg truncate transition-colors group-hover:text-accent">
            <?= Html::encode($model->title) ?>
        </h3>
        <div class="mt-1 flex items-center gap-2 text-[11px] font-mono uppercase tracking-wider text-fg-subtle">
            <?php if ($model->getMainGenre()): ?>
                <span class="truncate"><?= Html::encode($model->getMainGenre()) ?></span>
            <?php endif; ?>
            <?php if ($year && $model->getMainGenre()): ?>
                <span class="h-1 w-1 rounded-full bg-fg-subtle/40 shrink-0"></span>
            <?php endif; ?>
            <?php if ($year): ?>
                <span class="shrink-0"><?= Html::encode($year) ?></span>
            <?php endif; ?>

            <?php if ($price !== null): ?>
                <span class="ml-auto shrink-0 inline-flex items-baseline gap-1.5 normal-case tracking-normal">
                    <?php if ($price->isDiscounted()): ?>
                        <span class="rounded bg-accent/10 px-1.5 py-px text-[10px] font-bold text-accent">−<?= $price->discount ?>%</span>
                        <span class="text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></span>
                    <?php endif; ?>
                    <span class="text-[13px] font-semibold tabular-nums <?= $price->free ? 'text-accent' : 'text-fg' ?>">
                        <?= Html::encode($price->final) ?>
                    </span>
                </span>
            <?php endif; ?>
        </div>
    </div>
</a>
