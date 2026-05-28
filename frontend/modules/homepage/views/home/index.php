<?php

use common\models\Genre;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $genres Genre[] */
/* @var $bestsellers \common\models\Game[] */
/* @var $popular_upcoming \common\models\Game[] */
/* @var $new_and_noteworthy \common\models\Game[] */
/* @var $gamesCount int */
/* @var $genresCount int */

$this->title = Yii::$app->params['meta-title'] . ' — Discover great Steam games, with confidence';
$this->params['description'] = 'Curated Steam game discovery — bestsellers, new releases, and upcoming titles, refreshed daily. Signal over noise.';

$columns = [
        ['dot' => 'bg-emerald-500', 'eyebrow' => 'Trending now',  'label' => 'Bestsellers',       'tagline' => "What everyone's playing this week.",  'games' => $bestsellers,        'slug' => 'bestsellers'],
        ['dot' => 'bg-sky-500',     'eyebrow' => 'Fresh arrivals', 'label' => 'New & Noteworthy', 'tagline' => 'Just dropped — worth your evening.',  'games' => $new_and_noteworthy, 'slug' => 'new-and-noteworthy'],
        ['dot' => 'bg-indigo-500',  'eyebrow' => 'Soon to ship',   'label' => 'Upcoming',         'tagline' => 'On the horizon. Wishlist material.',  'games' => $popular_upcoming,   'slug' => 'upcoming'],
];

$preview_tabs = [
        'trending' => ['label' => 'Trending', 'games' => array_slice($bestsellers, 0, 4)],
        'new'      => ['label' => 'New', 'games' => array_slice($new_and_noteworthy, 0, 4)],
        'upcoming' => ['label' => 'Upcoming', 'games' => array_slice($popular_upcoming, 0, 4)],
];
?>

    <section class="relative left-1/2 w-screen -ml-[50vw] -mt-10 sm:-mt-14 overflow-hidden bg-gradient-to-b from-cyan-50/40 via-slate-50/20 to-canvas pt-12 pb-20 sm:pt-20 sm:pb-28">
        <!-- Pastel decorative blobs — now spread across the full viewport -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -left-32 h-[560px] w-[560px] rounded-full bg-gradient-to-br from-cyan-200 to-sky-300 opacity-30 blur-3xl"></div>
            <div class="absolute top-0 -right-32 h-[640px] w-[640px] rounded-full bg-gradient-to-br from-indigo-200 to-rose-200 opacity-30 blur-3xl"></div>
            <div class="absolute -bottom-20 left-1/4 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 opacity-40 blur-3xl"></div>
            <div class="absolute top-1/3 left-1/2 -translate-x-1/2 h-[300px] w-[300px] rounded-full bg-gradient-to-br from-amber-100 to-rose-100 opacity-25 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left: copy -->
                <div>
                    <p class="fade-up inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted"
                       style="animation-delay: 0.05s">
                        <span class="h-1.5 w-1.5 rounded-full bg-accent soft-pulse"></span>
                        Updated daily — fresh picks every morning
                    </p>

                    <h1 class="fade-up mt-6 font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-fg tracking-tight leading-[1.05]"
                        style="animation-delay: 0.15s">
                        Discover great Steam games,<br class="hidden sm:block">
                        <span class="text-accent">without the noise.</span>
                    </h1>

                    <p class="fade-up mt-6 max-w-xl text-lg text-fg-muted leading-relaxed"
                       style="animation-delay: 0.3s">
                        Thousands of Steam games, ranked by what's hot, what's new, and what's actually worth playing — minus the asset flips and paid placements.
                    </p>

                    <div class="fade-up mt-10 flex flex-wrap items-center gap-3"
                         style="animation-delay: 0.45s">
                        <a href="<?= Url::to(['/games']) ?>"
                           class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
                            Browse games <span aria-hidden="true">→</span>
                        </a>
                        <a href="<?= Url::to(['/how-it-works']) ?>"
                           class="inline-flex items-center gap-2 rounded-full bg-canvas text-fg px-6 py-3 text-sm font-medium ring-1 ring-line hover:ring-line-strong hover:bg-surface transition">
                            How it works
                        </a>
                    </div>

                    <div class="fade-up mt-10 flex items-center gap-6 text-xs text-fg-subtle font-mono"
                         style="animation-delay: 0.6s">
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            <?= number_format($gamesCount) ?> games
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                            <?= number_format($genresCount) ?> genres
                        </div>
                        <div class="hidden sm:flex items-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                            Synced from Steam daily
                        </div>
                    </div>
                </div>

                <!-- Right: window mockup with games preview -->
                <div class="relative fade-up" style="animation-delay: 0.35s">
                    <!-- Glow behind window -->
                    <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-cyan-200/40 via-sky-200/30 to-indigo-200/40 blur-2xl -z-10" aria-hidden="true"></div>

                    <div class="relative rounded-2xl bg-canvas shadow-2xl ring-1 ring-slate-900/5 overflow-hidden">
                        <!-- Window chrome -->
                        <div class="flex items-center gap-4 border-b border-line bg-surface/80 backdrop-blur px-4 py-3">
                            <div class="flex gap-1.5">
                                <span class="h-3 w-3 rounded-full bg-rose-400"></span>
                                <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                                <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                            </div>
                            <div class="flex gap-1 text-xs font-medium" role="tablist">
                                <?php foreach ($preview_tabs as $key => $tab): ?>
                                    <button type="button"
                                            role="tab"
                                            data-preview-tab="<?= $key ?>"
                                            data-active="<?= $key === 'trending' ? 'true' : 'false' ?>"
                                            aria-selected="<?= $key === 'trending' ? 'true' : 'false' ?>"
                                            aria-controls="preview-panel-<?= $key ?>"
                                            class="rounded-md px-2.5 py-1 transition cursor-pointer
                                           text-fg-muted hover:text-fg hover:bg-canvas/60
                                           data-[active=true]:bg-canvas
                                           data-[active=true]:text-fg
                                           data-[active=true]:ring-1
                                           data-[active=true]:ring-line
                                           data-[active=true]:shadow-sm
                                           data-[active=true]:hover:bg-canvas">
                                        <?= Html::encode($tab['label']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <span class="ml-auto font-mono text-[10px] text-fg-subtle">gamentator.app</span>
                        </div>

                        <!-- Window content: game lists, one panel per tab -->
                        <?php foreach ($preview_tabs as $key => $tab): ?>
                            <div data-preview-panel="<?= $key ?>"
                                 id="preview-panel-<?= $key ?>"
                                 role="tabpanel"
                                 class="divide-y divide-line px-2 py-1"
                                    <?= $key === 'trending' ? '' : 'hidden' ?>>
                                <?php if (empty($tab['games'])): ?>
                                    <p class="px-2 py-8 text-center text-sm text-fg-subtle">
                                        Nothing in this list yet.
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($tab['games'] as $i => $game): ?>
                                        <?= $this->render('_game-sale-item', ['game' => $game, 'rank' => $i + 1]) ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-4 mb-16">
        <div class="mt-6 flex flex-wrap items-center justify-center gap-2 max-w-3xl mx-auto px-4">
            <span class="text-xs text-fg-subtle font-mono mr-1">Browse:</span>
            <?php foreach ($genres as $genre): ?>
                <a href="<?= Url::to(['/games/' . $genre->slug]) ?>"
                   class="inline-flex items-center text-xs font-medium text-fg-muted bg-canvas border border-line rounded-full px-3 py-1.5 hover:border-line-strong hover:text-fg hover:bg-surface transition">
                    <?= Html::encode($genre->name) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= Url::to(['/genres']) ?>"
               class="inline-flex items-center gap-1 text-xs font-medium text-accent rounded-full px-3 py-1.5 hover:underline">
                All genres <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <section class="mt-20 sm:mt-28">
        <div class="text-center max-w-2xl mx-auto mb-14">
            <p class="inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted">
                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                Curated picks
            </p>
            <h2 class="mt-4 font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight">
                What's worth your time
            </h2>
            <p class="mt-4 text-lg text-fg-muted">
                Three lists. Updated regularly. No filler.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <?php foreach ($columns as $col): ?>
                <div class="group rounded-2xl bg-canvas ring-1 ring-line p-6 hover:ring-line-strong hover:shadow-md transition">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="h-1.5 w-1.5 rounded-full <?= $col['dot'] ?>"></span>
                        <span class="font-mono text-[11px] uppercase tracking-wider text-fg-muted">
                        <?= Html::encode($col['eyebrow']) ?>
                    </span>
                    </div>
                    <h3 class="font-display text-xl font-bold text-fg">
                        <?= Html::encode($col['label']) ?>
                    </h3>
                    <p class="mt-1 text-sm text-fg-muted pb-4 border-b border-line">
                        <?= Html::encode($col['tagline']) ?>
                    </p>
                    <div class="divide-y divide-line">
                        <?php foreach ($col['games'] as $i => $game): ?>
                            <?= $this->render('_game-sale-item', ['game' => $game, 'rank' => $i + 1]) ?>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= Url::to(['/games/' . $col['slug']]) ?>"
                       class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                        See all <?= Html::encode(strtolower($col['label'])) ?>
                        <span aria-hidden="true" class="transition group-hover:translate-x-0.5">→</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

<?php
$js = <<<'JS'
(function () {
    var tabs = document.querySelectorAll('[data-preview-tab]');
    var panels = document.querySelectorAll('[data-preview-panel]');
    if (!tabs.length || !panels.length) return;

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-preview-tab');
            tabs.forEach(function (t) {
                var active = t === tab;
                t.setAttribute('data-active', active ? 'true' : 'false');
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panels.forEach(function (p) {
                p.hidden = (p.getAttribute('data-preview-panel') !== target);
            });
        });
    });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
