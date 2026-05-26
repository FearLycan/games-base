<?php

use common\models\GameGenre;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $genres GameGenre[] */
/* @var $bestsellers \common\models\Game[] */
/* @var $popular_upcoming \common\models\Game[] */
/* @var $new_and_noteworthy \common\models\Game[] */

$this->title = Yii::$app->params['meta-title'];

$columns = [
    ['dot' => 'bg-emerald-500', 'eyebrow' => 'Trending now',     'label' => 'Bestsellers',      'tagline' => "What everyone's playing this week.",   'games' => $bestsellers],
    ['dot' => 'bg-sky-500',     'eyebrow' => 'Fresh arrivals',   'label' => 'New & Noteworthy', 'games' => $new_and_noteworthy, 'tagline' => 'Just dropped — worth your evening.'],
    ['dot' => 'bg-indigo-500',  'eyebrow' => 'Soon to ship',     'label' => 'Upcoming',         'games' => $popular_upcoming,   'tagline' => 'On the horizon. Wishlist material.'],
];

$preview_games = array_slice($bestsellers, 0, 4);
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
                Discover great games,<br class="hidden sm:block">
                <span class="text-accent">with confidence.</span>
            </h1>

            <p class="fade-up mt-6 max-w-xl text-lg text-fg-muted leading-relaxed"
               style="animation-delay: 0.3s">
                Thousands of titles across every genre — sorted by what's hot, what's new, and what's worth your time. Built for players who want signal over noise.
            </p>

            <div class="fade-up mt-10 flex flex-wrap items-center gap-3"
                 style="animation-delay: 0.45s">
                <a href="#"
                   class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
                    Browse games <span aria-hidden="true">→</span>
                </a>
                <a href="#"
                   class="inline-flex items-center gap-2 rounded-full bg-canvas text-fg px-6 py-3 text-sm font-medium ring-1 ring-line hover:ring-line-strong hover:bg-surface transition">
                    How it works
                </a>
            </div>

            <div class="fade-up mt-10 flex items-center gap-6 text-xs text-fg-subtle font-mono"
                 style="animation-delay: 0.6s">
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <?= number_format(count($bestsellers) + count($new_and_noteworthy) + count($popular_upcoming) + 200) ?>+ games
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                    <?= count($genres) ?>+ genres
                </div>
                <div class="hidden sm:flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                    Synced from Steam
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
                    <div class="flex gap-1 text-xs font-medium">
                        <span class="rounded-md bg-canvas text-fg px-2.5 py-1 ring-1 ring-line shadow-sm">Trending</span>
                        <span class="rounded-md text-fg-muted px-2.5 py-1 cursor-default">New</span>
                        <span class="rounded-md text-fg-muted px-2.5 py-1 cursor-default">Upcoming</span>
                    </div>
                    <span class="ml-auto font-mono text-[10px] text-fg-subtle">gamentator.app</span>
                </div>

                <!-- Window content: game list -->
                <div class="divide-y divide-line px-2 py-1">
                    <?php foreach ($preview_games as $i => $game): ?>
                        <?= $this->render('_game-sale-item', ['game' => $game, 'rank' => $i + 1]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>

<section class="relative -mt-4 mb-16">
    <form action="<?= Url::to(['/game/search']) ?>" method="get" class="relative max-w-2xl mx-auto">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
             class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-fg-subtle pointer-events-none">
            <path fill-rule="evenodd"
                  d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                  clip-rule="evenodd"/>
        </svg>
        <input
            type="text"
            id="game-name-select"
            name="phrase"
            placeholder="Search by title, genre, or studio…"
            class="w-full rounded-full bg-canvas border border-line pl-12 pr-32 py-3.5 text-base text-fg placeholder:text-fg-subtle shadow-sm focus:border-accent focus:outline-hidden focus:ring-4 focus:ring-accent/15 transition"
            autocomplete="off"
        >
        <button type="submit"
                class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-full bg-fg text-canvas px-5 py-2 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
            Search
        </button>
    </form>

    <div class="mt-6 flex flex-wrap items-center justify-center gap-2 max-w-3xl mx-auto px-4">
        <span class="text-xs text-fg-subtle font-mono mr-1">Browse:</span>
        <?php foreach (array_slice($genres, 0, 8) as $genre): ?>
            <a href="<?= Url::to(['/games/' . $genre->genre->slug]) ?>"
               class="inline-flex items-center text-xs font-medium text-fg-muted bg-canvas border border-line rounded-full px-3 py-1.5 hover:border-line-strong hover:text-fg hover:bg-surface transition">
                <?= Html::encode($genre->genre->name) ?>
            </a>
        <?php endforeach; ?>
        <a href="#"
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
                <a href="#" class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                    See all <?= Html::encode(strtolower($col['label'])) ?>
                    <span aria-hidden="true" class="transition group-hover:translate-x-0.5">→</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
