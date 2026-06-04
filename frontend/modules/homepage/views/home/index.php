<?php

use common\models\Genre;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $genreTiles Genre[] */
/* @var $bestsellers \common\models\Game[] */
/* @var $popular_upcoming \common\models\Game[] */
/* @var $new_and_noteworthy \common\models\Game[] */
/* @var $best_deals \common\models\Game[] */
/* @var $biggest_discounts \common\models\Game[] */
/* @var $most_wishlisted \common\models\Game[] */
/* @var $new_releases \common\models\Game[] */
/* @var $historical_lows \common\models\Game[] */
/* @var $wishlist_deals \common\models\Game[] */
/* @var $because array{seed: \common\models\Game, games: \common\models\Game[]}|null */
/* @var $top_deal \common\models\Game|null */
/* @var $gamesCount int */
/* @var $genresCount int */
/* @var $storesCount int */

$this->title = Yii::$app->params['meta-title'] . ' — Steam game deals and price comparison';
$this->params['description'] = 'Compare Steam game prices across Steam and the major key shops, and find the best deals. Bestsellers, new releases and the biggest discounts, updated daily.';

$columns = [
        ['dot' => 'bg-emerald-500', 'eyebrow' => 'Trending now',  'label' => 'Bestsellers',       'tagline' => "What everyone's playing this week.",  'games' => $bestsellers,        'slug' => 'bestsellers'],
        ['dot' => 'bg-sky-500',     'eyebrow' => 'Fresh arrivals', 'label' => 'New & Noteworthy', 'tagline' => 'Just dropped — worth your evening.',  'games' => $new_and_noteworthy, 'slug' => 'new-and-noteworthy'],
        ['dot' => 'bg-indigo-500',  'eyebrow' => 'Soon to ship',   'label' => 'Upcoming',         'tagline' => 'On the horizon. Wishlist material.',  'games' => $popular_upcoming,   'slug' => 'upcoming'],
];

$dealColumns = [
        ['dot' => 'bg-emerald-500', 'eyebrow' => 'Best value',   'label' => 'Best deals',      'tagline' => 'Biggest savings across all stores.', 'games' => $best_deals],
        ['dot' => 'bg-rose-500',    'eyebrow' => 'Biggest cuts',  'label' => 'Top discounts',   'tagline' => 'Highest % off, every shop compared.', 'games' => $biggest_discounts],
        ['dot' => 'bg-amber-500',   'eyebrow' => 'In demand',     'label' => 'Most wishlisted', 'tagline' => 'What players are waiting to buy.',    'games' => $most_wishlisted],
        ['dot' => 'bg-sky-500',     'eyebrow' => 'Just out',      'label' => 'New releases',    'tagline' => 'Fresh launches worth a look.',       'games' => $new_releases],
];
?>

    <section class="relative left-1/2 w-screen -ml-[50vw] -mt-10 sm:-mt-14 overflow-hidden bg-gradient-to-b from-cyan-50/40 via-slate-50/20 to-canvas pt-12 pb-20 sm:pt-20 sm:pb-24">
        <!-- Pastel decorative blobs — now spread across the full viewport -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-40 -left-32 h-[560px] w-[560px] rounded-full bg-gradient-to-br from-cyan-200 to-sky-300 opacity-30 blur-3xl"></div>
            <div class="absolute top-0 -right-32 h-[640px] w-[640px] rounded-full bg-gradient-to-br from-indigo-200 to-rose-200 opacity-30 blur-3xl"></div>
            <div class="absolute -bottom-20 left-1/4 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 opacity-40 blur-3xl"></div>
            <div class="absolute top-1/3 left-1/2 -translate-x-1/2 h-[300px] w-[300px] rounded-full bg-gradient-to-br from-amber-100 to-rose-100 opacity-25 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left: copy + search -->
                <div>
                    <p class="fade-up inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted"
                       style="animation-delay: 0.05s">
                        <span class="h-1.5 w-1.5 rounded-full bg-accent soft-pulse"></span>
                        Fresh picks and prices, every morning
                    </p>

                    <h1 class="fade-up mt-6 font-display text-4xl sm:text-5xl lg:text-6xl font-bold text-fg tracking-tight leading-[1.05] text-balance"
                        style="animation-delay: 0.15s">
                        Find great Steam games,<br class="hidden sm:block">
                        <span class="text-accent">at the best price.</span>
                    </h1>

                    <p class="fade-up mt-6 max-w-xl text-lg text-fg-muted leading-relaxed text-pretty"
                       style="animation-delay: 0.3s">
                        Thousands of games ranked by what's worth playing, with prices checked across Steam and every major store. Find the right game, then the cheapest place to buy it.
                    </p>

                    <!-- Search: opens the global search modal (Ctrl/⌘ K) -->
                    <button type="button"
                            data-search-trigger
                            aria-label="Search games"
                            class="fade-up mt-8 group flex w-full max-w-xl items-center gap-3 rounded-2xl border border-line bg-canvas/90 px-5 h-14 text-left shadow-sm backdrop-blur transition hover:border-line-strong hover:shadow-md"
                            style="animation-delay: 0.4s">
                        <svg class="h-5 w-5 shrink-0 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span class="flex-1 text-fg-subtle">Search <?= number_format($gamesCount) ?>+ games, genres, studios…</span>
                        <span class="hidden sm:inline-flex items-center gap-1">
                            <span class="search-kbd">Ctrl</span>
                            <span class="search-kbd">K</span>
                        </span>
                    </button>

                    <div class="fade-up mt-6 flex flex-wrap items-center gap-3"
                         style="animation-delay: 0.5s">
                        <a href="<?= Url::to(['/games']) ?>"
                           class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
                            Browse games <span aria-hidden="true">→</span>
                        </a>
                        <a href="<?= Url::to(['/how-it-works']) ?>"
                           class="inline-flex items-center gap-2 rounded-full bg-canvas text-fg px-6 py-3 text-sm font-medium ring-1 ring-line hover:ring-line-strong hover:bg-surface transition">
                            How it works
                        </a>
                    </div>

                    <div class="fade-up mt-8 flex items-center gap-6 text-xs text-fg-subtle font-mono"
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
                            Prices compared daily
                        </div>
                    </div>
                </div>

                <!-- Right: top deal -->
                <div class="relative fade-up" style="animation-delay: 0.35s">
                    <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-cyan-200/40 via-sky-200/30 to-indigo-200/40 blur-2xl -z-10" aria-hidden="true"></div>

                    <?php if ($top_deal !== null): ?>
                        <?= $this->render('_top-deal', ['game' => $top_deal]) ?>
                    <?php else: ?>
                        <div class="rounded-2xl bg-canvas ring-1 ring-line p-8 text-center shadow-sm">
                            <p class="font-display text-lg font-semibold text-fg">Deals refresh every morning</p>
                            <p class="mt-2 text-sm text-fg-muted">Browse the catalogue to compare prices across stores.</p>
                            <a href="<?= Url::to(['/games']) ?>" class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                                Browse games <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($wishlist_deals)): ?>
        <section class="mt-16 sm:mt-20" data-carousel>
            <div class="flex items-end justify-between gap-4 mb-6">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-full bg-accent/10 ring-1 ring-accent/15 px-3 py-1 text-xs font-medium text-accent">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 21s-6.7-4.35-9.33-8.07C1.1 10.7 1.6 7.6 4 6.2c1.9-1.1 4.2-.5 5.4 1.1L12 10l2.6-2.7c1.2-1.6 3.5-2.2 5.4-1.1 2.4 1.4 2.9 4.5 1.33 6.73C18.7 16.65 12 21 12 21Z"></path></svg>
                        From your wishlist
                    </p>
                    <h2 class="mt-4 font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight text-balance">
                        Deals on your wishlist
                    </h2>
                    <p class="mt-3 text-lg text-fg-muted text-pretty">
                        Games you're waiting on that just went on sale.
                    </p>
                </div>
                <div class="hidden sm:flex items-center gap-2 shrink-0">
                    <a href="<?= Url::to(['/user/profile/wishlist']) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                        Your wishlist <span aria-hidden="true">→</span>
                    </a>
                    <button type="button" data-carousel-prev aria-label="Scroll left" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>
                    <button type="button" data-carousel-next aria-label="Scroll right" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                    </button>
                </div>
            </div>
            <div data-carousel-track class="flex gap-4 overflow-x-auto scroll-smooth snap-x pb-2 -mx-1 px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <?php foreach ($wishlist_deals as $game): ?>
                    <?= $this->render('_game-card', ['game' => $game]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($because !== null): ?>
        <section class="mt-16 sm:mt-20" data-carousel>
            <div class="flex items-end justify-between gap-4 mb-6">
                <div class="max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="6" y1="11" x2="10" y2="11"></line><line x1="8" y1="9" x2="8" y2="13"></line><line x1="15" y1="12" x2="15.01" y2="12"></line><line x1="18" y1="10" x2="18.01" y2="10"></line><path d="M17.32 5H6.68a4 4 0 0 0-3.98 3.59c-.06.6-.27 2.01-.49 3.41A20.4 20.4 0 0 0 2 16a3 3 0 0 0 5.4 1.8l.6-.8h8l.6.8A3 3 0 0 0 22 16c0-1-.2-2.4-.21-2.5l-.49-4.91A4 4 0 0 0 17.32 5Z"></path></svg>
                        For you
                    </p>
                    <h2 class="mt-4 font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight text-balance">
                        Because you played <span class="text-accent"><?= Html::encode($because['seed']->title) ?></span>
                    </h2>
                    <p class="mt-3 text-lg text-fg-muted text-pretty">More from the genres you play most.</p>
                </div>
                <div class="hidden sm:flex items-center gap-2 shrink-0">
                    <button type="button" data-carousel-prev aria-label="Scroll left" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>
                    <button type="button" data-carousel-next aria-label="Scroll right" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                    </button>
                </div>
            </div>
            <div data-carousel-track class="flex gap-4 overflow-x-auto scroll-smooth snap-x pb-2 -mx-1 px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <?php foreach ($because['games'] as $game): ?>
                    <?= $this->render('_game-card', ['game' => $game]) ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="mt-16 sm:mt-20">
        <div class="max-w-2xl mb-10">
            <p class="inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted">
                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                Cross-store price comparison
            </p>
            <h2 class="mt-4 font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight text-balance">
                The best deals right now
            </h2>
            <p class="mt-4 text-lg text-fg-muted text-pretty">
                We check every price across Steam and the major key shops. Free games are left out, so everything here is worth paying for.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($dealColumns as $col): ?>
                <div class="group rounded-2xl bg-canvas ring-1 ring-line p-5 hover:ring-line-strong hover:shadow-md transition">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="h-1.5 w-1.5 rounded-full <?= $col['dot'] ?>"></span>
                        <span class="font-mono text-[11px] uppercase tracking-wider text-fg-muted">
                            <?= Html::encode($col['eyebrow']) ?>
                        </span>
                    </div>
                    <h3 class="font-display text-lg font-bold text-fg">
                        <?= Html::encode($col['label']) ?>
                    </h3>
                    <p class="mt-1 text-sm text-fg-muted pb-3 border-b border-line">
                        <?= Html::encode($col['tagline']) ?>
                    </p>
                    <?php if (empty($col['games'])): ?>
                        <p class="py-8 text-center text-sm text-fg-subtle">Nothing here yet.</p>
                    <?php else: ?>
                        <div class="divide-y divide-line">
                            <?php foreach ($col['games'] as $game): ?>
                                <?= $this->render('_game-sale-item', ['game' => $game]) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($historical_lows)): ?>
        <section class="mt-20 sm:mt-28">
            <div class="max-w-2xl mb-10">
                <p class="inline-flex items-center gap-2 rounded-full bg-accent/10 ring-1 ring-accent/15 px-3 py-1 text-xs font-medium text-accent">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12"></path><path d="m6 12 6 6 6-6"></path><path d="M5 21h14"></path></svg>
                    Lowest ever
                </p>
                <h2 class="mt-4 font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight text-balance">
                    At their lowest price ever
                </h2>
                <p class="mt-4 text-lg text-fg-muted text-pretty">
                    Cheaper right now than at any point since we started tracking.
                </p>
            </div>

            <div class="rounded-2xl bg-canvas ring-1 ring-line p-2 sm:p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 md:gap-x-6">
                    <?php foreach ($historical_lows as $game): ?>
                        <?= $this->render('_game-sale-item', ['game' => $game]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

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
                Three hand-picked lists, updated daily.
            </p>
        </div>

        <div class="space-y-12 sm:space-y-14">
            <?php foreach ($columns as $col): ?>
                <div data-carousel>
                    <div class="flex items-end justify-between gap-4 mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full <?= $col['dot'] ?>"></span>
                                <span class="font-mono text-[11px] uppercase tracking-wider text-fg-muted"><?= Html::encode($col['eyebrow']) ?></span>
                            </div>
                            <h3 class="mt-1 font-display text-xl font-bold text-fg"><?= Html::encode($col['label']) ?></h3>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="<?= Url::to(['/games/' . $col['slug']]) ?>" class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-accent hover:underline">
                                See all <span aria-hidden="true">→</span>
                            </a>
                            <button type="button" data-carousel-prev aria-label="Scroll left" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                            </button>
                            <button type="button" data-carousel-next aria-label="Scroll right" class="grid h-9 w-9 place-items-center rounded-full bg-canvas ring-1 ring-line text-fg-muted transition hover:ring-line-strong hover:text-fg hover:bg-surface active:scale-[0.96] disabled:opacity-40 disabled:pointer-events-none">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                            </button>
                        </div>
                    </div>
                    <?php if (empty($col['games'])): ?>
                        <p class="text-sm text-fg-subtle">Nothing here yet.</p>
                    <?php else: ?>
                        <div data-carousel-track class="flex gap-4 overflow-x-auto scroll-smooth snap-x pb-2 -mx-1 px-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            <?php foreach ($col['games'] as $game): ?>
                                <?= $this->render('_game-card', ['game' => $game]) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mt-20 sm:mt-28">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight">Browse by genre</h2>
            <p class="mt-4 text-lg text-fg-muted">Jump straight into what you're into.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($genreTiles as $g): ?>
                <a href="<?= Url::to(['/games/' . $g->slug]) ?>"
                   class="group flex items-center justify-between gap-2 rounded-xl bg-canvas ring-1 ring-line px-4 py-3.5 transition hover:ring-line-strong hover:bg-surface">
                    <span class="font-display text-sm font-semibold text-fg truncate transition-colors group-hover:text-accent"><?= Html::encode($g->name) ?></span>
                    <span class="shrink-0 font-mono text-xs tabular-nums text-fg-subtle"><?= number_format($g->games_count) ?></span>
                </a>
            <?php endforeach; ?>
            <a href="<?= Url::to(['/genres']) ?>"
               class="group flex items-center justify-center gap-1.5 rounded-xl bg-fg/5 ring-1 ring-line px-4 py-3.5 text-sm font-medium text-accent transition hover:bg-surface">
                All genres <span aria-hidden="true" class="transition group-hover:translate-x-0.5">→</span>
            </a>
        </div>
    </section>

    <section class="mt-20 sm:mt-28">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 rounded-2xl bg-surface/60 ring-1 ring-line p-8 sm:p-10">
            <?php
            $compareIcon = '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"></path>';
            // The comparison claim only holds with 2+ live stores; otherwise lead
            // with fresh prices so we never overstate.
            $comparePillar = $storesCount >= 2
                    ? [
                            'title' => 'Prices compared across ' . $storesCount . ' stores',
                            'body'  => 'Every store we track, side by side, so the cheapest is easy to spot.',
                            'icon'  => $compareIcon,
                    ]
                    : [
                            'title' => 'Live prices, updated daily',
                            'body'  => 'Up-to-date prices on every game, refreshed every morning.',
                            'icon'  => $compareIcon,
                    ];

            $pillars = [
                    [
                            'title' => 'Synced from Steam daily',
                            'body'  => 'We pull the catalogue, new releases and prices fresh every morning, straight from Steam.',
                            'icon'  => '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path><path d="M21 3v5h-5"></path><path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path><path d="M3 21v-5h5"></path>',
                    ],
                    $comparePillar,
                    [
                            'title' => 'No paid placements',
                            'body'  => 'We rank on data and price, never on who paid us.',
                            'icon'  => '<path d="M20 13c0 5-3.5 7.5-7.7 8.9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.7a1 1 0 0 1 1.5 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1Z"></path><path d="m9 12 2 2 4-4"></path>',
                    ],
            ];
            ?>
            <?php foreach ($pillars as $i => $pillar): ?>
                <div class="flex flex-col items-center text-center gap-3 <?= $i > 0 ? 'sm:border-l sm:border-line sm:pl-6' : '' ?>">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-canvas text-accent ring-1 ring-line shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $pillar['icon'] ?></svg>
                    </span>
                    <h3 class="font-display text-base font-semibold text-fg text-balance"><?= Html::encode($pillar['title']) ?></h3>
                    <p class="max-w-xs text-sm text-fg-muted leading-relaxed text-pretty"><?= Html::encode($pillar['body']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

<?php
$js = <<<'JS'
(function () {
    document.querySelectorAll('[data-carousel]').forEach(function (carousel) {
        var track = carousel.querySelector('[data-carousel-track]');
        if (!track) return;
        var prev = carousel.querySelector('[data-carousel-prev]');
        var next = carousel.querySelector('[data-carousel-next]');

        function scroll(dir) {
            track.scrollBy({ left: dir * Math.round(track.clientWidth * 0.85), behavior: 'smooth' });
        }
        function sync() {
            var max = track.scrollWidth - track.clientWidth - 1;
            if (prev) prev.disabled = track.scrollLeft <= 0;
            if (next) next.disabled = track.scrollLeft >= max;
        }

        if (prev) prev.addEventListener('click', function () { scroll(-1); });
        if (next) next.addEventListener('click', function () { scroll(1); });
        track.addEventListener('scroll', sync, { passive: true });
        sync();
    });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_END);
