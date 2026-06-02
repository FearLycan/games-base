<?php

/* @var $model common\models\Game */
/* @var $reasons string[] tags this game shares with the user's taste */

use yii\helpers\Html;
use yii\helpers\Url;

$g = $model;
$reasons = $reasons ?? [];
$price = $g->getDisplayPrice();
$header = 'https://cdn.cloudflare.steamstatic.com/steam/apps/' . $g->steam_appid . '/header.jpg';
$href = Url::to(['/game/game/view', 'id' => $g->steam_appid, 'slug' => $g->slug]);
?>
<a href="<?= Html::encode($href) ?>"
   class="group flex flex-col overflow-hidden rounded-xl border border-line bg-surface/40 shadow-sm transition-transform duration-150 will-change-transform hover:-translate-y-0.5">
    <span class="block aspect-[460/215] overflow-hidden bg-surface-2">
        <img src="<?= Html::encode($header) ?>" alt="" loading="lazy"
             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
    </span>
    <span class="flex flex-1 flex-col gap-1 p-3">
        <span class="truncate text-sm font-medium text-fg" title="<?= Html::encode((string)$g->title) ?>"><?= Html::encode((string)$g->title) ?></span>
        <span class="mt-0.5 flex items-center gap-2">
            <?php if ($price !== null && $price->free): ?>
                <span class="text-sm font-semibold text-accent">Free</span>
            <?php elseif ($price !== null): ?>
                <?php if ($price->isDiscounted()): ?>
                    <span class="rounded bg-accent/10 px-1.5 py-0.5 text-[11px] font-semibold text-accent tabular-nums">-<?= $price->discount ?>%</span>
                    <span class="text-xs text-fg-subtle line-through tabular-nums"><?= Html::encode($price->initial) ?></span>
                <?php endif; ?>
                <span class="text-sm font-semibold text-fg tabular-nums"><?= Html::encode($price->final) ?></span>
            <?php endif; ?>
        </span>
        <?php if ($reasons !== []): ?>
            <span class="mt-1.5 flex flex-wrap items-center gap-1" title="Matches tags you play: <?= Html::encode(implode(', ', $reasons)) ?>">
                <svg class="h-3 w-3 shrink-0 text-accent" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.4L12 17l-6.3 4.4L8 14 2 9.4h7.6z"/></svg>
                <?php foreach ($reasons as $tag): ?>
                    <span class="rounded bg-accent/10 px-1.5 py-0.5 text-[10px] font-medium text-accent"><?= Html::encode($tag) ?></span>
                <?php endforeach; ?>
            </span>
        <?php endif; ?>
    </span>
</a>
