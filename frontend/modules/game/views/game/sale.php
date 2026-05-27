<?php

use common\models\Game;
use common\models\GameSale;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $slug string */
/* @var $type int */
/* @var $games Game[] */

$meta = [
    GameSale::TYPE_BESTSELLERS => [
        'eyebrow'   => 'Trending now',
        'h1Lead'    => 'Steam',
        'h1Accent'  => 'bestsellers',
        'h1Tail'    => 'right now',
        'subtitle'  => 'The most-bought games on Steam this week. Curated list, refreshed every six hours — no paid placements.',
        'dot'       => 'bg-emerald-500',
        'pillBg'    => 'bg-emerald-50',
        'pillText'  => 'text-emerald-700',
        'pillRing'  => 'ring-emerald-200',
        'badge'     => ['text' => 'Bestseller', 'class' => 'bg-rose-600/95'],
        'glow'      => 'from-emerald-200/40 via-teal-200/30 to-sky-200/30',
    ],
    GameSale::TYPE_NEW_AND_NOTEWORTHY => [
        'eyebrow'   => 'Fresh arrivals',
        'h1Lead'    => 'New on Steam,',
        'h1Accent'  => 'worth your time',
        'h1Tail'    => '',
        'subtitle'  => 'Recent releases that have hit their stride in their first weeks on Steam. Updated every six hours.',
        'dot'       => 'bg-sky-500',
        'pillBg'    => 'bg-sky-50',
        'pillText'  => 'text-sky-700',
        'pillRing'  => 'ring-sky-200',
        'badge'     => ['text' => 'New', 'class' => 'bg-sky-600/95'],
        'glow'      => 'from-sky-200/40 via-cyan-200/30 to-indigo-200/30',
    ],
    GameSale::TYPE_POPULAR_UPCOMING => [
        'eyebrow'   => 'On the horizon',
        'h1Lead'    => 'Coming to Steam,',
        'h1Accent'  => 'on your radar',
        'h1Tail'    => '',
        'subtitle'  => 'Anticipated launches gathering wishlists. Lock in the ones you want before they ship.',
        'dot'       => 'bg-indigo-500',
        'pillBg'    => 'bg-indigo-50',
        'pillText'  => 'text-indigo-700',
        'pillRing'  => 'ring-indigo-200',
        'badge'     => ['text' => 'Upcoming', 'class' => 'bg-indigo-600/95'],
        'glow'      => 'from-indigo-200/40 via-violet-200/30 to-rose-200/30',
    ],
];

$m = $meta[$type];
$titleLabels = [
    GameSale::TYPE_BESTSELLERS        => 'Steam bestsellers',
    GameSale::TYPE_NEW_AND_NOTEWORTHY => 'New on Steam',
    GameSale::TYPE_POPULAR_UPCOMING   => 'Upcoming on Steam',
];
$this->title = $titleLabels[$type] . ' — ' . Yii::$app->params['meta-title'];
$this->params['description'] = $m['subtitle'];
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
$this->params['breadcrumbs'][] = $m['eyebrow'];
$this->registerCssFile('@web/css/game.css');

$totalCount = count($games);
?>

<section class="relative left-1/2 w-screen -ml-[50vw] -mt-10 sm:-mt-14 overflow-hidden bg-gradient-to-b from-slate-50/30 to-canvas pt-12 pb-14 sm:pt-16">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 h-[460px] w-[460px] rounded-full bg-gradient-to-br <?= $m['glow'] ?> opacity-50 blur-3xl"></div>
        <div class="absolute -bottom-20 right-0 h-[400px] w-[400px] rounded-full bg-gradient-to-br <?= $m['glow'] ?> opacity-30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <span class="inline-flex items-center gap-2 rounded-full <?= $m['pillBg'] ?> <?= $m['pillText'] ?> ring-1 <?= $m['pillRing'] ?> px-3 py-1 text-xs font-medium">
                <span class="h-1.5 w-1.5 rounded-full <?= $m['dot'] ?>"></span>
                <?= Html::encode($m['eyebrow']) ?>
            </span>
            <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">
                <?= number_format($totalCount) ?> games · refreshed every 6 hours
            </span>
        </div>

        <h1 class="font-display text-4xl sm:text-5xl font-bold text-fg tracking-tight leading-[1.05] max-w-3xl">
            <?= Html::encode($m['h1Lead']) ?>
            <span class="text-accent"><?= Html::encode($m['h1Accent']) ?></span>
            <?php if ($m['h1Tail']): ?> <?= Html::encode($m['h1Tail']) ?><?php endif; ?>
        </h1>
        <p class="mt-5 max-w-2xl text-fg-muted leading-relaxed">
            <?= Html::encode($m['subtitle']) ?>
        </p>
    </div>
</section>

<section class="mt-10">
    <?php if (empty($games)): ?>
        <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center max-w-3xl mx-auto">
            <p class="font-display text-lg font-semibold text-fg">List is being built</p>
            <p class="mt-2 text-sm text-fg-muted">Sale data is synced every six hours. Check back shortly.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
            <?php foreach ($games as $i => $game): ?>
                <?php
                $rank = $i + 1;
                $rankStr = sprintf('%02d', $rank);
                $year = '';
                if ($game->release_date) {
                    $ts = strtotime($game->release_date);
                    if ($ts) {
                        $year = date('Y', $ts);
                    }
                }
                ?>
                <a href="<?= Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]) ?>"
                   class="group block">
                    <div class="relative aspect-[460/215] overflow-hidden rounded-xl bg-surface-2 ring-1 ring-line">
                        <img src="<?= Html::encode($game->getHeader()) ?>"
                             alt="<?= Html::encode($game->title) ?>"
                             loading="lazy"
                             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">

                        <span class="absolute top-2 left-2 inline-flex items-center justify-center min-w-[34px] h-7 px-2 rounded-md bg-canvas/95 backdrop-blur text-[11px] font-mono font-bold tabular-nums text-fg ring-1 ring-line shadow-sm">
                            #<?= $rankStr ?>
                        </span>

                        <span class="absolute top-2 right-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-[0.14em] text-white backdrop-blur <?= $m['badge']['class'] ?>">
                            <span class="h-1 w-1 rounded-full bg-white/80"></span>
                            <?= Html::encode($m['badge']['text']) ?>
                        </span>
                    </div>

                    <div class="mt-3 px-0.5">
                        <h3 class="font-display text-[15px] font-semibold text-fg truncate transition-colors group-hover:text-accent">
                            <?= Html::encode($game->title) ?>
                        </h3>
                        <div class="mt-1 flex items-center gap-2 text-[11px] font-mono uppercase tracking-wider text-fg-subtle">
                            <?php if ($game->getMainGenre()): ?>
                                <span class="truncate"><?= Html::encode($game->getMainGenre()) ?></span>
                            <?php endif; ?>
                            <?php if ($year && $game->getMainGenre()): ?>
                                <span class="h-1 w-1 rounded-full bg-fg-subtle/40 shrink-0"></span>
                            <?php endif; ?>
                            <?php if ($year): ?>
                                <span class="shrink-0"><?= Html::encode($year) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="mt-20 mb-8 text-center">
    <p class="text-sm text-fg-muted mb-4">Want a wider net?</p>
    <a href="<?= Url::to(['/games']) ?>"
       class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
        Browse the full catalog <span aria-hidden="true">→</span>
    </a>
</section>
