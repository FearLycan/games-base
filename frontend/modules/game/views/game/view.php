<?php

use common\components\CurrencyResolver;
use common\schema\builder\GamePageSchemaBuilder;
use common\schema\JsonLdRenderer;
use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Game */
/* @var $related Game[] */
/* @var $dlcs Game[] */

$this->title = $model->title . " - " . Yii::$app->params['meta-title'];
$this->params['description'] = $model->short_description
    ? mb_substr(trim(strip_tags($model->short_description)), 0, 160)
    : ($model->title . ' — reviews, screenshots, system requirements and Steam details.');
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
if (!empty($model->genres)) {
    $mainGenre = $model->genres[0];
    $this->params['breadcrumbs'][] = ['label' => $mainGenre->name, 'url' => ['/games/' . $mainGenre->slug]];
}
$this->params['breadcrumbs'][] = $model->title;
$this->registerCssFile('@web/css/game.css');

$gameUrl = Url::to(['/game/game/view', 'id' => $model->steam_appid, 'slug' => $model->slug], true);
echo JsonLdRenderer::render(GamePageSchemaBuilder::build($model, $gameUrl));

$screenshots = $model->getScreenshots();
$platforms = $model->getAvailablePlatforms();
$reviewPercent = $model->review ? $model->review->getPercentsOfPositive() : 0;

$saleLabel = null;
if ($model->isBestseller()) {
    $saleLabel = ['text' => 'Bestseller', 'class' => 'bg-rose-600 text-white'];
} elseif ($model->isNewAndNoteworthy()) {
    $saleLabel = ['text' => 'New & Noteworthy', 'class' => 'bg-sky-600 text-white'];
} elseif ($model->isPopularUpcoming()) {
    $saleLabel = ['text' => 'Popular Upcoming', 'class' => 'bg-indigo-600 text-white'];
}

$offerCurrency = CurrencyResolver::forVisitor();
$offers = $model->getSortedOffers($offerCurrency);
$bestOffer = $model->getBestOffer($offerCurrency);

// On-this-page jump nav + section numbering: only sections that actually render.
$sections = [];
if (!empty($offers))      { $sections[] = ['id' => 'where-to-buy', 'label' => 'Where to buy']; }
$sections[]               =   ['id' => 'about',        'label' => 'About this game'];
if (!empty($dlcs))        { $sections[] = ['id' => 'dlc',          'label' => 'DLC & add-ons']; }
if (!empty($screenshots)) { $sections[] = ['id' => 'gallery',      'label' => 'Gallery']; }
if (!empty($platforms))   { $sections[] = ['id' => 'requirements', 'label' => 'System requirements']; }
$sections[]               =   ['id' => 'steam',        'label' => 'Get it on Steam'];
$sectionNo = [];
foreach ($sections as $i => $s) {
    $sectionNo[$s['id']] = str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT);
}
?>

<section class="game-hero relative left-1/2 w-screen -ml-[50vw] mb-10 sm:mb-14"
         style="background-image:url('<?= Html::encode($model->getBackground()) ?>')">
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-20 pb-32 sm:pt-28 sm:pb-40">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-8 flex items-start gap-5 sm:gap-7 game-hero-text">
                <img src="<?= Html::encode($model->getIcon()) ?>"
                     alt="<?= Html::encode($model->title) ?>"
                     loading="lazy"
                     class="h-20 w-20 sm:h-28 sm:w-28 rounded-2xl object-cover ring-2 ring-white/15 shadow-2xl shadow-black/40 shrink-0">

                <div class="min-w-0 flex-1">
                    <?php if ($model->isDlc() || $saleLabel): ?>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if ($model->isDlc()): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[10px] font-mono font-semibold uppercase tracking-[0.18em] text-white ring-1 ring-inset ring-white/25 backdrop-blur">
                                    <i class="fa-solid fa-puzzle-piece text-[9px] leading-none" aria-hidden="true"></i>
                                    DLC
                                </span>
                            <?php endif; ?>
                            <?php if ($saleLabel): ?>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-mono font-semibold uppercase tracking-[0.18em] <?= $saleLabel['class'] ?>">
                                    <span class="h-1 w-1 rounded-full bg-current"></span>
                                    <?= Html::encode($saleLabel['text']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h1 class="mt-3 font-display text-3xl sm:text-5xl font-bold text-white tracking-tight leading-tight">
                        <?= Html::encode($model->title) ?>
                    </h1>

                    <?php if ($model->review && $model->review->total_reviews): ?>
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/85">
                            <div class="rating" aria-label="<?= $reviewPercent ?>% positive">
                                <div class="rating-upper" style="width: <?= $reviewPercent ?>%">
                                    ★★★★★
                                </div>
                                <div class="rating-lower">★★★★★</div>
                            </div>
                            <span class="font-mono text-xs uppercase tracking-wider text-white/70">
                                <?= number_format($model->review->total_reviews) ?> reviews
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if ($model->short_description): ?>
                        <p class="mt-5 max-w-2xl text-[15px] leading-relaxed text-white/90">
                            <?= Html::encode($model->short_description) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="lg:col-span-4 flex flex-col items-start lg:items-end gap-3">
                <?php $priceLabel = $model->getPriceLabel(); $discount = $model->getDiscountPercent(); ?>
                <?php if ($priceLabel !== null): ?>
                    <div class="flex items-center gap-2.5">
                        <?php if ($discount > 0): ?>
                            <span class="rounded-md bg-emerald-500 px-2 py-1 text-sm font-bold text-white">-<?= $discount ?>%</span>
                            <span class="text-white/50 line-through text-sm"><?= Html::encode($model->getInitialPrice()) ?></span>
                        <?php endif; ?>
                        <span class="font-display text-2xl font-bold text-white"><?= Html::encode($priceLabel) ?></span>
                    </div>
                <?php endif; ?>
                <a href="<?= Html::encode($model->getSteamUrl()) ?>"
                   target="_blank"
                   rel="nofollow noopener external"
                   class="inline-flex items-center gap-2.5 rounded-xl bg-accent hover:bg-accent/90 px-6 py-3.5 text-sm font-semibold text-white transition shadow-lg shadow-emerald-900/30">
                    <i class="fa-brands fa-steam text-lg leading-none" aria-hidden="true"></i>
                    View on Steam
                    <span class="text-white/70" aria-hidden="true">↗</span>
                </a>
            </div>
        </div>
    </div>
</section>

<section class="relative">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        <div class="lg:col-span-8 lg:-mt-48 relative z-10 rounded-3xl bg-canvas p-6 sm:p-10 space-y-12">

            <?php if ($model->isDlc() && $model->fullGame): ?>
                <?php $base = $model->fullGame; ?>
                <a href="<?= Url::to(['/game/game/view', 'id' => $base->steam_appid, 'slug' => $base->slug]) ?>"
                   class="dlc-parent-banner">
                    <img src="<?= Html::encode($base->getIcon()) ?>"
                         alt="<?= Html::encode($base->title) ?>"
                         loading="lazy"
                         class="dlc-parent-icon">
                    <span class="dlc-parent-body">
                        <span class="dlc-parent-kicker">Downloadable content</span>
                        <span class="dlc-parent-title">Part of <strong><?= Html::encode($base->title) ?></strong></span>
                    </span>
                    <span class="dlc-parent-cta">
                        View base game
                        <span class="arrow" aria-hidden="true">→</span>
                    </span>
                </a>
            <?php endif; ?>

            <nav class="section-nav" aria-label="Jump to section">
                <?php foreach ($sections as $s): ?>
                    <a href="#<?= $s['id'] ?>" class="section-nav-link">
                        <span class="section-nav-num"><?= $sectionNo[$s['id']] ?></span>
                        <?= Html::encode($s['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (!empty($offers)): ?>
                <article id="where-to-buy" class="scroll-mt-24">
                    <header class="flex flex-wrap items-center gap-3 mb-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle"><?= $sectionNo['where-to-buy'] ?></span>
                        <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">Where to buy</h2>
                        <div class="currency-switch ml-auto" role="group" aria-label="Display currency">
                            <?php foreach (CurrencyResolver::SUPPORTED as $currencyOption): ?>
                                <button type="button"
                                        class="currency-opt"
                                        data-currency="<?= Html::encode($currencyOption) ?>"
                                        data-active="<?= $currencyOption === $offerCurrency ? 'true' : 'false' ?>">
                                    <?= Html::encode($currencyOption) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </header>

                    <?= $this->render('_offers', ['offers' => $offers, 'bestOffer' => $bestOffer, 'offerCurrency' => $offerCurrency]) ?>
                </article>
            <?php endif; ?>

            <article id="about" class="scroll-mt-24">
                <header class="flex items-center gap-3 mb-5">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle"><?= $sectionNo['about'] ?></span>
                    <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">About this game</h2>
                </header>

                <?php if ($model->detailed_description): ?>
                    <div class="about-text is-collapsed" data-about-text>
                        <?= $model->detailed_description ?>
                    </div>
                    <button type="button"
                            data-about-toggle
                            class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">
                        <span data-about-label-more>Read more</span>
                        <span data-about-label-less hidden>Show less</span>
                        <span aria-hidden="true" class="transition-transform" data-about-icon>↓</span>
                    </button>
                <?php else: ?>
                    <p class="text-fg-subtle italic">No description available.</p>
                <?php endif; ?>
            </article>

            <?php if (!empty($dlcs)): ?>
                <article id="dlc" class="scroll-mt-24">
                    <header class="flex items-center gap-3 mb-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle"><?= $sectionNo['dlc'] ?></span>
                        <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">DLC &amp; add-ons</h2>
                        <span class="ml-auto font-mono text-[11px] text-fg-subtle"><?= count($dlcs) ?> item<?= count($dlcs) === 1 ? '' : 's' ?></span>
                    </header>

                    <?= $this->render('_dlc', ['dlcs' => $dlcs]) ?>
                </article>
            <?php endif; ?>

            <?php if (!empty($screenshots)): ?>
                <article id="gallery" data-gallery class="scroll-mt-24">
                    <header class="flex items-center gap-3 mb-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle"><?= $sectionNo['gallery'] ?></span>
                        <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">Gallery</h2>
                        <span class="ml-auto font-mono text-[11px] text-fg-subtle"><?= count($screenshots) ?> shots</span>
                    </header>

                    <div class="gallery-main shadow-xl shadow-fg/10">
                        <img data-gallery-main
                             src="<?= Html::encode($screenshots[0]->url) ?>"
                             alt="<?= Html::encode($model->title) ?> screenshot"
                             loading="lazy">
                    </div>

                    <div class="gallery-strip mt-4 flex gap-3 overflow-x-auto pb-2 -mx-1 px-1">
                        <?php foreach ($screenshots as $idx => $screenshot): ?>
                            <button type="button"
                                    class="gallery-thumb"
                                    data-gallery-thumb
                                    data-active="<?= $idx === 0 ? 'true' : 'false' ?>"
                                    data-url="<?= Html::encode($screenshot->url) ?>"
                                    aria-label="View screenshot <?= $idx + 1 ?>">
                                <img src="<?= Html::encode($screenshot->url) ?>"
                                     alt="<?= Html::encode($model->title) ?> #<?= $idx + 1 ?>"
                                     loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endif; ?>

            <?php if (!empty($platforms)): ?>
                <article id="requirements" data-requirements class="scroll-mt-24">
                    <header class="flex items-center gap-3 mb-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle"><?= $sectionNo['requirements'] ?></span>
                        <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">System requirements</h2>
                    </header>

                    <div class="flex flex-wrap gap-1.5 mb-6">
                        <?php foreach ($platforms as $idx => $platform): ?>
                            <button type="button"
                                    class="platform-tab inline-flex items-center gap-2 rounded-lg border border-line px-3.5 py-1.5 text-sm font-medium text-fg-muted hover:text-fg"
                                    data-requirements-tab
                                    data-target="<?= Html::encode($platform->slug) ?>"
                                    data-active="<?= $idx === 0 ? 'true' : 'false' ?>">
                                <?= ucwords(Html::encode($platform->name)) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($platforms as $idx => $platform): ?>
                        <div class="requirements-panel grid-cols-1 sm:grid-cols-2 gap-6"
                             data-requirements-panel="<?= Html::encode($platform->slug) ?>"
                             data-active="<?= $idx === 0 ? 'true' : 'false' ?>">
                            <div class="rounded-xl border border-line bg-surface/40 p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Minimum</span>
                                    <span class="h-px flex-1 bg-line"></span>
                                </div>
                                <div class="requirements-body">
                                    <?= $platform->requirements_minimum ?: '<p class="text-fg-subtle italic">Not specified.</p>' ?>
                                </div>
                            </div>
                            <div class="rounded-xl border border-line bg-surface/40 p-5">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-accent">Recommended</span>
                                    <span class="h-px flex-1 bg-line"></span>
                                </div>
                                <div class="requirements-body">
                                    <?= $platform->requirements_recommended ?: '<p class="text-fg-subtle italic">Not specified.</p>' ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </article>
            <?php endif; ?>

            <article id="steam" class="scroll-mt-24">
                <header class="flex items-center gap-3 mb-5">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle"><?= $sectionNo['steam'] ?></span>
                    <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">Get it on Steam</h2>
                </header>

                <div class="steam-widget overflow-hidden rounded-xl">
                    <iframe src="https://store.steampowered.com/widget/<?= (int)$model->steam_appid ?>/?utm_source=<?= Yii::$app->name ?>&utm_campaign=<?= Yii::$app->name ?>"
                            title="<?= Html::encode($model->title) ?> on Steam"
                            loading="lazy"
                            width="100%"
                            height="190"
                            frameborder="0"></iframe>
                </div>
            </article>

            <?php if ($lastSynced = $model->getLastSyncedLabel()): ?>
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle/70">
                    Data synced from Steam · <?= Html::encode($lastSynced) ?>
                </p>
            <?php endif; ?>
        </div>

        <aside class="lg:col-span-4 lg:-mt-48 relative z-10">
            <div class="lg:sticky lg:top-24">
                <?= $this->render('_right-bar', ['model' => $model, 'gameViewButton' => false]) ?>
            </div>
        </aside>
    </div>
</section>

<?php if (!empty($related)): ?>
<section class="mt-16">
    <header class="flex items-center gap-3 mb-6">
        <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">More games like this</h2>
        <span class="h-px flex-1 bg-line"></span>
    </header>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-5 gap-y-7">
        <?php foreach ($related as $relatedGame): ?>
            <?= $this->render('_item', ['model' => $relatedGame]) ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php $closingPrice = $model->getPriceLabel(); $closingDiscount = $model->getDiscountPercent(); ?>
<section class="mt-14">
    <div class="rounded-3xl bg-fg text-canvas px-6 sm:px-10 py-9 flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="text-center sm:text-left">
            <h2 class="font-display text-xl sm:text-2xl font-bold">Ready to play <?= Html::encode($model->title) ?>?</h2>
            <p class="mt-1 text-sm text-canvas/70">
                <?php if ($closingPrice !== null && $closingDiscount > 0): ?>
                    On sale now — <?= $closingDiscount ?>% off, <?= Html::encode($closingPrice) ?> on Steam.
                <?php elseif ($closingPrice !== null): ?>
                    <?= Html::encode($closingPrice) ?> on Steam.
                <?php else: ?>
                    Head to Steam for the latest price and availability.
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= Html::encode($model->getSteamUrl()) ?>"
           target="_blank"
           rel="nofollow noopener external"
           class="inline-flex items-center gap-2.5 rounded-xl bg-accent text-white px-7 py-3.5 text-sm font-semibold hover:bg-accent/90 transition shadow-lg shadow-emerald-900/20 shrink-0">
            <i class="fa-brands fa-steam text-lg leading-none" aria-hidden="true"></i>
            View on Steam
            <span class="text-white/70" aria-hidden="true">↗</span>
        </a>
    </div>
</section>

<button type="button" class="back-to-top" data-back-to-top aria-label="Back to top">
    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
</button>

<?php
$js = <<<JS
(function () {
    // About: read more / less
    var aboutText = document.querySelector('[data-about-text]');
    var aboutToggle = document.querySelector('[data-about-toggle]');
    if (aboutText && aboutToggle) {
        var labelMore = aboutToggle.querySelector('[data-about-label-more]');
        var labelLess = aboutToggle.querySelector('[data-about-label-less]');
        var icon = aboutToggle.querySelector('[data-about-icon]');
        aboutToggle.addEventListener('click', function () {
            var collapsed = aboutText.classList.toggle('is-collapsed');
            labelMore.hidden = !collapsed;
            labelLess.hidden = collapsed;
            if (icon) icon.style.transform = collapsed ? '' : 'rotate(180deg)';
        });
    }

    // Gallery: thumb click swaps main image
    var gallery = document.querySelector('[data-gallery]');
    if (gallery) {
        var main = gallery.querySelector('[data-gallery-main]');
        gallery.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var url = thumb.getAttribute('data-url');
                if (!url || !main) return;
                main.style.opacity = '0';
                setTimeout(function () {
                    main.src = url;
                    main.style.opacity = '1';
                }, 140);
                gallery.querySelectorAll('[data-gallery-thumb]').forEach(function (t) {
                    t.setAttribute('data-active', t === thumb ? 'true' : 'false');
                });
            });
        });
    }

    // System requirements: platform tabs
    var requirements = document.querySelector('[data-requirements]');
    if (requirements) {
        var tabs = requirements.querySelectorAll('[data-requirements-tab]');
        var panels = requirements.querySelectorAll('[data-requirements-panel]');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var target = tab.getAttribute('data-target');
                tabs.forEach(function (t) {
                    t.setAttribute('data-active', t === tab ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    p.setAttribute('data-active', p.getAttribute('data-requirements-panel') === target ? 'true' : 'false');
                });
            });
        });
    }

    // Tag chips: show more / less
    var tagToggle = document.querySelector('[data-tag-toggle]');
    if (tagToggle) {
        var hiddenChips = document.querySelectorAll('[data-tag-hidden]');
        if (!hiddenChips.length) {
            tagToggle.hidden = true;
        } else {
            tagToggle.addEventListener('click', function () {
                var expanded = tagToggle.getAttribute('data-expanded') === 'true';
                hiddenChips.forEach(function (chip) { chip.hidden = expanded; });
                tagToggle.setAttribute('data-expanded', expanded ? 'false' : 'true');
                tagToggle.querySelector('[data-tag-more]').hidden = !expanded;
                tagToggle.querySelector('[data-tag-less]').hidden = expanded;
            });
        }
    }

    // Section jump nav: smooth-scroll + press feedback.
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var behavior = reduce ? 'auto' : 'smooth';

    function pulse(el) {
        el.classList.remove('is-pulsing');
        void el.offsetWidth;
        el.classList.add('is-pulsing');
    }

    document.querySelectorAll('.section-nav-link').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var target = document.getElementById(a.getAttribute('href').slice(1));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: behavior, block: 'start' }); }
            pulse(a);
        });
    });

    // Back to top.
    var toTop = document.querySelector('[data-back-to-top]');
    if (toTop) {
        var toggleTop = function () { toTop.classList.toggle('is-visible', window.scrollY >= 600); };
        window.addEventListener('scroll', toggleTop, { passive: true });
        toggleTop();
        toTop.addEventListener('click', function () {
            pulse(toTop);
            window.scrollTo({ top: 0, behavior: behavior });
        });
    }

    // Currency switcher: store the choice and reload with prices in that currency.
    document.querySelectorAll('.currency-switch [data-currency]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.getAttribute('data-active') === 'true') return;
            document.cookie = 'currency=' + btn.dataset.currency + ';path=/;max-age=31536000;samesite=lax';
            location.reload();
        });
    });
})();
JS;
$this->registerJs($js, View::POS_END);
?>
