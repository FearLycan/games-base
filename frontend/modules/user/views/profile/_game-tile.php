<?php

/* @var $model common\models\UserGame */

use yii\helpers\Html;
use yii\helpers\Url;

$g = $model;
$inCatalog = $g->isInCatalog();
$href = $inCatalog
    ? Url::to(['/game/game/view', 'id' => $g->game->steam_appid, 'slug' => $g->game->slug])
    : $g->getSteamStoreUrl();
?>
<a href="<?= Html::encode($href) ?>"
   <?= $inCatalog ? '' : 'target="_blank" rel="noopener noreferrer"' ?>
   class="group flex flex-col overflow-hidden rounded-xl border border-line bg-surface/40 shadow-sm transition-transform duration-150 will-change-transform hover:-translate-y-0.5">
    <span class="block aspect-[460/215] overflow-hidden bg-surface-2">
        <img src="<?= Html::encode($g->getHeaderImageUrl()) ?>"
             alt=""
             loading="lazy"
             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
    </span>
    <span class="flex flex-1 flex-col gap-1 p-3">
        <span class="truncate text-sm font-medium text-fg" title="<?= Html::encode($g->getDisplayTitle()) ?>"><?= Html::encode($g->getDisplayTitle()) ?></span>
        <span class="text-xs text-fg-subtle tabular-nums"><?= Html::encode($g->getPlaytimeLabel()) ?></span>
        <?php $pct = $g->getCompletionPercent(); ?>
        <?php if ($pct !== null): ?>
            <?php $perfect = $g->isPerfect(); ?>
            <span class="mt-1 flex items-center gap-2" title="<?= $g->ach_unlocked ?>/<?= $g->ach_total ?> achievements">
                <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-line">
                    <span class="block h-full rounded-full <?= $perfect ? 'bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500' : 'bg-accent' ?>" style="width:<?= $pct ?>%"></span>
                </span>
                <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-medium tabular-nums <?= $perfect ? 'text-amber-500' : 'text-fg-subtle' ?>">
                    <?php if ($perfect): ?><i class="fa-solid fa-trophy text-[10px]" aria-hidden="true"></i><?php endif; ?>
                    <?= $pct ?>%
                </span>
            </span>
        <?php endif; ?>
    </span>
</a>
