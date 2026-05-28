<?php

use common\models\Category;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $categories Category[] */

$this->title = 'Browse by feature — ' . Yii::$app->params['meta-title'];
$this->params['description'] = 'Every Steam feature we track — single-player, co-op, controller support, cloud saves and more. Filter games by what they actually support.';
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
$this->params['breadcrumbs'][] = 'Features';
$this->registerCssFile('@web/css/game.css');

$totalGames = array_sum(array_map(static fn(Category $c): int => (int)$c->games_count, $categories));
?>

<section class="relative left-1/2 w-screen -ml-[50vw] overflow-hidden bg-gradient-to-b from-sky-50/30 to-canvas pt-12 pb-14 sm:pt-16">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 h-[460px] w-[460px] rounded-full bg-gradient-to-br from-sky-200/40 to-indigo-200/40 opacity-50 blur-3xl"></div>
        <div class="absolute -bottom-20 right-0 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-cyan-200/40 to-emerald-200/40 opacity-30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 text-sky-700 ring-1 ring-sky-200 px-3 py-1 text-xs font-medium">
                <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                Directory
            </span>
            <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">
                <?= number_format(count($categories)) ?> features · <?= number_format($totalGames) ?> game links
            </span>
        </div>

        <h1 class="font-display text-4xl sm:text-5xl font-bold text-fg tracking-tight leading-[1.05] max-w-3xl">
            Browse by <span class="text-accent">feature</span>
        </h1>
        <p class="mt-5 max-w-2xl text-fg-muted leading-relaxed">
            How a game plays, not just what it's about. Single-player, online co-op, controller support, cloud saves, achievements — pick the capabilities that matter to you.
        </p>
    </div>
</section>

<section class="mt-10">
    <?php if (empty($categories)): ?>
        <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center max-w-3xl mx-auto">
            <p class="font-display text-lg font-semibold text-fg">No features yet</p>
            <p class="mt-2 text-sm text-fg-muted">Features get populated as games sync. Try again later.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($categories as $category): ?>
                <?php $count = (int)$category->games_count; ?>
                <a href="<?= Url::to(['/games/' . $category->slug]) ?>"
                   class="group relative overflow-hidden rounded-2xl bg-canvas ring-1 ring-line p-5 hover:ring-line-strong hover:shadow-md transition">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-fg shrink-0 group-hover:bg-accent transition-colors">
                        <img src="<?= Html::encode($category->getImage()) ?>"
                             alt=""
                             loading="lazy"
                             class="h-5 w-5 object-contain">
                    </span>

                    <h3 class="relative mt-4 font-display text-lg font-bold text-fg leading-tight transition-colors group-hover:text-accent">
                        <?= Html::encode($category->name) ?>
                    </h3>

                    <div class="relative mt-3 flex items-end justify-between">
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
    <p class="text-sm text-fg-muted mb-4">Prefer to browse by theme?</p>
    <a href="<?= Url::to(['/genres']) ?>"
       class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
        Browse by genre <span aria-hidden="true">→</span>
    </a>
</section>
