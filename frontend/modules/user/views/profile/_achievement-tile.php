<?php

/* @var $model common\models\UserAchievement */

use yii\helpers\Html;
use yii\helpers\Url;

$ua = $model;
$a = $ua->achievement;
if ($a === null) {
    return;
}

$game = $ua->game;
$gameUrl = $game ? Url::to(['/game/game/achievements', 'id' => $game->steam_appid, 'slug' => $game->slug]) : null;

$tierClass = [
    'ultra'    => 'border-violet-200 bg-violet-50 text-violet-700',
    'rare'     => 'border-sky-200 bg-sky-50 text-sky-700',
    'uncommon' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'common'   => 'border-line bg-surface-2 text-fg-subtle',
][$a->getRarityTier()];
?>
<div class="flex gap-3 rounded-xl border border-line bg-surface/40 p-4">
    <?php if ($a->icon): ?>
        <img src="<?= Html::encode($a->icon) ?>" alt="" width="48" height="48"
             class="h-12 w-12 shrink-0 rounded-lg object-cover outline outline-1 -outline-offset-1 outline-black/10">
    <?php endif; ?>
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-fg" title="<?= Html::encode($a->name) ?>"><?= Html::encode($a->name) ?></p>
        <?php if ($game): ?>
            <a href="<?= Html::encode($gameUrl) ?>" class="block truncate text-xs text-fg-subtle transition-colors hover:text-accent"><?= Html::encode((string)$game->title) ?></a>
        <?php endif; ?>
        <div class="mt-1.5 flex flex-wrap items-center gap-2">
            <?php if ($a->getPercentLabel()): ?>
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium <?= $tierClass ?>"><?= Html::encode($a->getPercentLabel()) ?></span>
            <?php endif; ?>
            <?php if ($ua->unlocked_at): ?>
                <span class="text-[11px] text-fg-subtle"><?= Html::encode(Yii::$app->formatter->asDate($ua->unlocked_at, 'medium')) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>
