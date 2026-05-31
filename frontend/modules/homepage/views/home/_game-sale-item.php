<?php

use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $game Game */
/* @var $rank int */

$rankStr = isset($rank) ? sprintf('%02d', $rank) : null;
$price = $game->getDisplayPrice();
?>

<a
    href="<?= Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]) ?>"
    class="group flex items-center gap-3 py-3 px-2 rounded-lg transition-colors hover:bg-surface"
>
    <?php if ($rankStr !== null): ?>
        <span class="font-mono text-xs tabular-nums text-fg-subtle w-6 flex-none">
            <?= $rankStr ?>
        </span>
    <?php endif; ?>
    <img
        loading="lazy"
        alt="<?= Html::encode($game->title) ?>"
        src="<?= $game->getHeader() ?>"
        class="h-12 w-[88px] flex-none rounded-md object-cover bg-surface-2 ring-1 ring-line"
    >
    <div class="min-w-0 flex-1">
        <h6 class="font-display text-sm font-semibold text-fg truncate transition-colors group-hover:text-accent">
            <?= Html::encode($game->title) ?>
        </h6>
        <p class="mt-0.5 text-xs text-fg-muted truncate">
            <?= Html::encode($game->getMainGenre()) ?>
        </p>
    </div>
    <?php if ($price !== null): ?>
        <div class="flex-none text-right leading-tight">
            <div class="text-sm font-semibold tabular-nums <?= $price->free ? 'text-accent' : 'text-fg' ?>">
                <?= Html::encode($price->final) ?>
            </div>
            <?php if ($price->isDiscounted()): ?>
                <div class="mt-0.5 flex items-center justify-end gap-1">
                    <span class="text-[10px] font-bold text-accent">−<?= $price->discount ?>%</span>
                    <span class="text-[11px] text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <span aria-hidden="true"
              class="text-fg-subtle text-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition">
            →
        </span>
    <?php endif; ?>
</a>
