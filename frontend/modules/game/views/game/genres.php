<?php

use common\models\Genre;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $genres Genre[] */
/* @var $hub array */

$this->title = 'Browse by genre — ' . Yii::$app->params['meta-title'];
$this->params['description'] = 'Every Steam genre we track, sorted by how much there is to explore. Pick a corner that calls to you.';
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
$this->params['breadcrumbs'][] = 'Genres';
$this->registerCssFile('@web/css/game.css');

$accentSeed = ['emerald', 'sky', 'indigo', 'rose', 'amber', 'teal', 'violet', 'cyan'];
?>

<section class="relative left-1/2 w-screen -ml-[50vw] overflow-hidden bg-gradient-to-b from-emerald-50/30 to-canvas pt-12 pb-14 sm:pt-16">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 h-[460px] w-[460px] rounded-full bg-gradient-to-br from-emerald-200/40 to-teal-200/40 opacity-50 blur-3xl"></div>
        <div class="absolute -bottom-20 right-0 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-cyan-200/40 to-sky-200/40 opacity-30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 px-3 py-1 text-xs font-medium">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Directory
            </span>
            <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">
                <?= number_format($hub['count']) ?> genres · <?= number_format($hub['games']) ?> games
            </span>
        </div>

        <h1 class="font-display text-4xl sm:text-5xl font-bold text-fg tracking-tight leading-[1.05] max-w-3xl">
            Browse by <span class="text-accent">genre</span>
        </h1>
        <p class="mt-5 max-w-2xl text-fg-muted leading-relaxed">
            <?= Html::encode($hub['intro']) ?>
        </p>
    </div>
</section>

<section class="mt-10">
    <?php if (empty($genres)): ?>
        <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center max-w-3xl mx-auto">
            <p class="font-display text-lg font-semibold text-fg">No genres yet</p>
            <p class="mt-2 text-sm text-fg-muted">Genres get populated as games sync. Try again later.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($genres as $i => $genre): ?>
                <?php
                $accent = $accentSeed[$i % count($accentSeed)];
                $count = (int)$genre->games_count;
                ?>
                <a href="<?= Url::to(['/games/' . $genre->slug]) ?>"
                   class="group relative overflow-hidden rounded-2xl bg-canvas ring-1 ring-line p-5 hover:ring-line-strong hover:shadow-md transition">
                    <span class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-<?= $accent ?>-100 opacity-50 group-hover:opacity-80 group-hover:scale-110 transition-all duration-500"></span>

                    <span class="relative inline-flex items-center gap-1.5 text-[10px] font-mono uppercase tracking-[0.2em] text-fg-subtle">
                        <span class="h-1 w-1 rounded-full bg-<?= $accent ?>-500"></span>
                        Genre
                    </span>

                    <h3 class="relative mt-3 font-display text-lg font-bold text-fg leading-tight transition-colors group-hover:text-accent">
                        <?= Html::encode($genre->name) ?>
                    </h3>

                    <div class="relative mt-4 flex items-end justify-between">
                        <span class="font-mono text-xs tabular-nums text-fg-muted">
                            <?= number_format($count) ?> <?= $count === 1 ? 'game' : 'games' ?>
                        </span>
                        <span aria-hidden="true" class="text-fg-subtle text-sm opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition">→</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="mt-20 mb-8 text-center">
    <p class="text-sm text-fg-muted mb-4">Looking for something specific?</p>
    <a href="<?= Url::to(['/games']) ?>"
       class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
        Browse all games with filters <span aria-hidden="true">→</span>
    </a>
</section>
