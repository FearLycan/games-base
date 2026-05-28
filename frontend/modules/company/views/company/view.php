<?php

use common\models\CompanyProfile;
use common\models\Developer;
use common\models\Publisher;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;

/* @var $this View */
/* @var $name string */
/* @var $slug string */
/* @var $profile CompanyProfile|null */
/* @var $developer Developer|null */
/* @var $publisher Publisher|null */
/* @var $stats array */
/* @var $dataProvider ActiveDataProvider */
/* @var $role string */
/* @var $sort string */

$this->title = $name . ' — games, history & releases · ' . Yii::$app->params['meta-title'];
$this->params['breadcrumbs'][] = $name;
$this->registerCssFile('@web/css/company.css');

$canonicalUrl = Url::to(['/company/company/view', 'slug' => $slug], true);
$this->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl]);

$roleLabels = [];
if ($developer) $roleLabels[] = 'Developer';
if ($publisher) $roleLabels[] = 'Publisher';
$roleHeadline = implode(' & ', $roleLabels);

$descriptionMeta = $profile?->description
    ?: sprintf('%s — %s with %s game%s in our Steam catalog.',
        $name,
        strtolower($roleHeadline ?: 'game studio'),
        number_format($stats['totalCount']),
        $stats['totalCount'] === 1 ? '' : 's');
$this->registerMetaTag(['name' => 'description', 'content' => mb_substr($descriptionMeta, 0, 160)]);

$initials = mb_strtoupper(mb_substr($name, 0, 2));
$location = $profile?->getLocation();
$activeYears = null;
if ($profile?->founded_year) {
    $end = $profile->closed_year ?: (int)date('Y');
    $activeYears = $end - $profile->founded_year;
}

$baseUrl = ['/company/company/view', 'slug' => $slug];

$sortOptions = [
    'reviews' => 'Most reviewed',
    'rating'  => 'Highest rated',
    'newest'  => 'Newest',
    'oldest'  => 'Oldest',
];

$roleTabs = [];
if ($developer && $publisher) {
    $roleTabs['all']       = ['All games', $stats['totalCount']];
    $roleTabs['developed'] = ['Developed', $stats['developedCount']];
    $roleTabs['published'] = ['Published', $stats['publishedCount']];
} elseif ($developer) {
    $roleTabs['developed'] = ['Games developed', $stats['developedCount']];
} elseif ($publisher) {
    $roleTabs['published'] = ['Games published', $stats['publishedCount']];
}

$schema = [
    '@context'  => 'https://schema.org',
    '@type'     => 'Organization',
    'name'      => $name,
    'url'       => $canonicalUrl,
];
if ($profile?->logo_url)     $schema['logo']        = $profile->logo_url;
if ($profile?->description)  $schema['description'] = $profile->description;
if ($profile?->founded_year) $schema['foundingDate'] = (string)$profile->founded_year;
if ($location) {
    $schema['address'] = array_filter([
        '@type'           => 'PostalAddress',
        'addressLocality' => $profile?->city,
        'addressCountry'  => $profile?->country,
    ]);
}
$sameAs = array_filter([
    $profile?->website,
    $profile?->twitter ? 'https://twitter.com/' . ltrim($profile->twitter, '@') : null,
    $profile?->discord,
]);
if ($sameAs) $schema['sameAs'] = array_values($sameAs);

$this->registerJs(Json::encode($schema), View::POS_HEAD, 'org-jsonld');
?>

<script type="application/ld+json"><?= Json::encode($schema) ?></script>

<section class="company-hero relative left-1/2 w-screen -ml-[50vw] mb-10 sm:mb-14">
    <div class="company-hero-glow"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-20 pb-28 sm:pt-28 sm:pb-36">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-8 flex items-start gap-5 sm:gap-7">
                <?php if ($profile?->logo_url): ?>
                    <img src="<?= Html::encode($profile->logo_url) ?>"
                         alt="<?= Html::encode($name) ?> logo"
                         loading="lazy"
                         class="company-logo h-24 w-24 sm:h-32 sm:w-32 rounded-2xl object-cover ring-2 ring-white/15 shadow-2xl shadow-black/40 shrink-0 bg-white/5">
                <?php else: ?>
                    <div class="company-logo h-24 w-24 sm:h-32 sm:w-32 rounded-2xl ring-2 ring-white/15 shadow-2xl shadow-black/40 shrink-0 grid place-items-center bg-gradient-to-br from-emerald-400/30 to-emerald-700/30 font-display text-3xl sm:text-4xl font-bold text-white tracking-tight">
                        <?= Html::encode($initials) ?>
                    </div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2 text-[11px] font-mono uppercase tracking-[0.2em] text-white/70">
                        <?php foreach ($roleLabels as $label): ?>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 ring-1 ring-white/15"><?= Html::encode($label) ?></span>
                        <?php endforeach; ?>
                        <?php if ($profile?->isClosed()): ?>
                            <span class="rounded-full bg-rose-500/20 px-2.5 py-1 ring-1 ring-rose-300/30 text-rose-100">Inactive since <?= (int)$profile->closed_year ?></span>
                        <?php endif; ?>
                    </div>

                    <h1 class="mt-3 font-display text-3xl sm:text-5xl font-bold text-white tracking-tight leading-tight">
                        <?= Html::encode($name) ?>
                    </h1>

                    <?php if ($profile?->description): ?>
                        <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/85">
                            <?= Html::encode($profile->description) ?>
                        </p>
                    <?php endif; ?>

                    <dl class="mt-6 flex flex-wrap gap-x-7 gap-y-3 text-sm text-white/80">
                        <?php if ($location): ?>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-white/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-7.5-7-12a7 7 0 1 1 14 0c0 4.5-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
                                <span><?= Html::encode($location) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($profile?->founded_year): ?>
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-white/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/></svg>
                                <span>Founded <?= (int)$profile->founded_year ?><?= $activeYears !== null ? ' · ' . $activeYears . ' years' : '' ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($profile?->website): ?>
                            <a href="<?= Html::encode($profile->website) ?>" target="_blank" rel="nofollow noopener external"
                               class="flex items-center gap-2 text-white hover:text-emerald-300 transition">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg>
                                Website <span class="text-white/50">↗</span>
                            </a>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="relative">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 lg:-mt-44 relative z-10 rounded-3xl bg-canvas p-6 sm:p-10 space-y-14">

            <article>
                <header class="flex items-center gap-3 mb-6">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">01</span>
                    <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">At a glance</h2>
                </header>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php if ($developer): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?= number_format($stats['developedCount']) ?></div>
                            <div class="stat-label">Games developed</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($publisher): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?= number_format($stats['publishedCount']) ?></div>
                            <div class="stat-label">Games published</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($stats['topGenre']): ?>
                        <div class="stat-card">
                            <div class="stat-value text-accent"><?= Html::encode($stats['topGenre']) ?></div>
                            <div class="stat-label">Most common genre</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($stats['avgRating'] !== null): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?= (int)$stats['avgRating'] ?>%</div>
                            <div class="stat-label">Avg. Steam rating</div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($stats['genreBreakdown'])): ?>
                    <div class="mt-8">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-fg-subtle">Genre fingerprint</span>
                            <span class="h-px flex-1 bg-line"></span>
                        </div>
                        <ul class="space-y-2.5">
                            <?php foreach ($stats['genreBreakdown'] as $g): ?>
                                <li class="genre-row">
                                    <span class="genre-name"><?= Html::encode($g['name']) ?></span>
                                    <span class="genre-bar-track" aria-hidden="true">
                                        <span class="genre-bar-fill" style="width: <?= max(2, (int)$g['percent']) ?>%"></span>
                                    </span>
                                    <span class="genre-percent"><?= (int)$g['percent'] ?>%</span>
                                    <span class="genre-count"><?= (int)$g['count'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </article>

            <article>
                <header class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">02</span>
                    <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">Games</h2>
                </header>

                <?php if (count($roleTabs) > 1): ?>
                    <div class="mb-5 flex flex-wrap items-center gap-1.5">
                        <?php foreach ($roleTabs as $key => [$label, $count]): ?>
                            <a href="<?= Url::to(array_merge($baseUrl, ['role' => $key, 'sort' => $sort])) ?>"
                               class="sort-chip"
                               data-active="<?= $role === $key ? 'true' : 'false' ?>">
                                <?= Html::encode($label) ?>
                                <span class="ml-1 text-[10px] opacity-70"><?= number_format($count) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mb-6 flex flex-wrap items-center gap-1.5">
                    <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle mr-1.5">Sort</span>
                    <?php foreach ($sortOptions as $key => $label): ?>
                        <a href="<?= Url::to(array_merge($baseUrl, ['role' => $role, 'sort' => $key])) ?>"
                           class="sort-chip"
                           data-active="<?= $sort === $key ? 'true' : 'false' ?>">
                            <?= Html::encode($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($dataProvider->getTotalCount() === 0): ?>
                    <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-14 text-center">
                        <p class="font-display text-lg font-semibold text-fg">No games in this view</p>
                        <p class="mt-2 text-sm text-fg-muted">Try switching the role tab above.</p>
                    </div>
                <?php else: ?>
                    <?= ListView::widget([
                        'dataProvider' => $dataProvider,
                        'itemView'     => '@frontend/modules/game/views/game/_item',
                        'summary'      => false,
                        'layout'       => "<div class=\"grid grid-cols-2 sm:grid-cols-3 gap-x-5 gap-y-7\">{items}</div>\n{pager}",
                        'itemOptions'  => ['tag' => 'div'],
                        'pager'        => [
                            'options'              => ['class' => 'mt-12 flex flex-wrap items-center justify-center gap-1.5 list-none p-0'],
                            'linkContainerOptions' => ['class' => 'pager-item'],
                            'linkOptions'          => ['class' => 'inline-flex items-center justify-center min-w-10 h-10 px-3.5 rounded-lg border border-line text-sm font-medium text-fg-muted hover:bg-surface hover:border-line-strong transition'],
                            'activePageCssClass'   => 'pager-active',
                            'disabledPageCssClass' => 'pager-disabled',
                            'firstPageLabel'       => false,
                            'lastPageLabel'        => false,
                            'prevPageLabel'        => '←',
                            'nextPageLabel'        => '→',
                            'maxButtonCount'       => 7,
                        ],
                    ]) ?>
                <?php endif; ?>
            </article>

            <?php if ($profile?->history): ?>
                <article>
                    <header class="flex items-center gap-3 mb-6">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">03</span>
                        <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">History</h2>
                    </header>

                    <?php if ($profile->founded_year): ?>
                        <ol class="timeline">
                            <li class="timeline-item">
                                <span class="timeline-year"><?= (int)$profile->founded_year ?></span>
                                <span class="timeline-dot"></span>
                                <p class="timeline-text">Founded<?= $location ? ' in ' . Html::encode($profile->city ?: $profile->country) : '' ?>.</p>
                            </li>
                            <?php
                            $founded = (int)$profile->founded_year;
                            $totalGames = $stats['totalCount'];
                            $milestones = [];
                            if ($totalGames >= 1) $milestones[] = [$founded + 2, 'First commercial release lands.'];
                            if ($totalGames >= 5) $milestones[] = [$founded + 6, 'Catalog grows past five titles.'];
                            if ($totalGames >= 15) $milestones[] = [$founded + 12, 'Established as a recognisable name in the catalog.'];
                            if ($profile->isClosed()) {
                                $milestones[] = [(int)$profile->closed_year, 'Operations wind down.'];
                            } else {
                                $milestones[] = [(int)date('Y'), 'Still active — ' . number_format($totalGames) . ' games tracked.'];
                            }
                            ?>
                            <?php foreach ($milestones as $m): ?>
                                <li class="timeline-item">
                                    <span class="timeline-year"><?= (int)$m[0] ?></span>
                                    <span class="timeline-dot"></span>
                                    <p class="timeline-text"><?= Html::encode($m[1]) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>

                    <div class="prose-history mt-8 max-w-3xl text-fg-muted leading-relaxed">
                        <?= nl2br(Html::encode($profile->history)) ?>
                    </div>
                </article>
            <?php endif; ?>
        </div>

        <aside class="lg:col-span-4 lg:-mt-44 relative z-10">
            <div class="lg:sticky lg:top-24 space-y-5">

                <div class="rounded-2xl border border-line bg-canvas p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Quick facts</span>
                        <span class="h-px flex-1 bg-line"></span>
                    </div>
                    <dl class="text-sm divide-y divide-line/70">
                        <?php if ($roleHeadline): ?>
                            <div class="flex items-start gap-4 py-2.5">
                                <dt class="w-24 shrink-0 text-fg-subtle">Role</dt>
                                <dd class="flex-1 text-right text-fg"><?= Html::encode($roleHeadline) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($location): ?>
                            <div class="flex items-start gap-4 py-2.5">
                                <dt class="w-24 shrink-0 text-fg-subtle">Based in</dt>
                                <dd class="flex-1 text-right text-fg"><?= Html::encode($location) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile?->founded_year): ?>
                            <div class="flex items-start gap-4 py-2.5">
                                <dt class="w-24 shrink-0 text-fg-subtle">Founded</dt>
                                <dd class="flex-1 text-right text-fg font-mono text-xs"><?= (int)$profile->founded_year ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($profile?->closed_year): ?>
                            <div class="flex items-start gap-4 py-2.5">
                                <dt class="w-24 shrink-0 text-fg-subtle">Closed</dt>
                                <dd class="flex-1 text-right text-fg font-mono text-xs"><?= (int)$profile->closed_year ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <?php if ($profile && ($profile->twitter || $profile->discord || $profile->website)): ?>
                    <div class="rounded-2xl border border-line bg-canvas p-5 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Connect</span>
                            <span class="h-px flex-1 bg-line"></span>
                        </div>
                        <ul class="space-y-2">
                            <?php if ($profile->website): ?>
                                <li>
                                    <a href="<?= Html::encode($profile->website) ?>" target="_blank" rel="nofollow noopener external" class="social-link">
                                        <span class="social-icon">🌐</span>
                                        <span class="social-label">Official website</span>
                                        <span class="social-arrow">↗</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($profile->twitter): ?>
                                <li>
                                    <a href="https://twitter.com/<?= Html::encode(ltrim($profile->twitter, '@')) ?>" target="_blank" rel="nofollow noopener external" class="social-link">
                                        <span class="social-icon">𝕏</span>
                                        <span class="social-label"><?= Html::encode($profile->twitter) ?></span>
                                        <span class="social-arrow">↗</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($profile->discord): ?>
                                <li>
                                    <a href="<?= Html::encode($profile->discord) ?>" target="_blank" rel="nofollow noopener external" class="social-link">
                                        <span class="social-icon">💬</span>
                                        <span class="social-label">Discord</span>
                                        <span class="social-arrow">↗</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            </div>
        </aside>
    </div>
</section>
