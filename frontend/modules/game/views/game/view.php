<?php

use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $model Game */

$this->title = $model->title . " - " . Yii::$app->params['meta-title'];
$this->registerCssFile('@web/css/game.css');

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
?>

<section class="game-hero relative left-1/2 w-screen -ml-[50vw] -mt-10 sm:-mt-14 mb-10 sm:mb-14"
         style="background-image:url('<?= Html::encode($model->getBackground()) ?>')">
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-20 pb-32 sm:pt-28 sm:pb-40">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            <div class="lg:col-span-8 flex items-start gap-5 sm:gap-7 game-hero-text">
                <img src="<?= Html::encode($model->getIcon()) ?>"
                     alt="<?= Html::encode($model->title) ?>"
                     loading="lazy"
                     class="h-20 w-20 sm:h-28 sm:w-28 rounded-2xl object-cover ring-2 ring-white/15 shadow-2xl shadow-black/40 shrink-0">

                <div class="min-w-0 flex-1">
                    <?php if ($saleLabel): ?>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-mono font-semibold uppercase tracking-[0.18em] <?= $saleLabel['class'] ?>">
                            <span class="h-1 w-1 rounded-full bg-current"></span>
                            <?= Html::encode($saleLabel['text']) ?>
                        </span>
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

            <div class="lg:col-span-4 flex lg:justify-end">
                <a href="<?= Html::encode($model->getSteamUrl()) ?>"
                   target="_blank"
                   rel="nofollow noopener external"
                   class="inline-flex items-center gap-2.5 rounded-xl bg-black/40 hover:bg-black/55 border border-white/20 backdrop-blur px-5 py-3 text-sm font-semibold text-white transition shadow-lg shadow-black/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 4.62 3.16 8.5 7.43 9.66l-.06-.93c0-.34.07-.66.2-.96l-2.93-1.16a3.34 3.34 0 1 0 4.97-3.6 3.34 3.34 0 0 0-1.7-.46L7.6 11.34a4.07 4.07 0 1 1 6.99 3.99l-2.99 2.97c.18.05.36.08.55.08a3.34 3.34 0 1 0-3.34-3.34l-3.97-1.58a2.05 2.05 0 1 1 1.13-2.74L8.4 11.5a3.34 3.34 0 0 1 5.86-2.36 4.07 4.07 0 1 1-6.5-4.7A9.97 9.97 0 0 1 12 2z"></path>
                    </svg>
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

            <article>
                <header class="flex items-center gap-3 mb-5">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">01</span>
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

            <?php if (!empty($screenshots)): ?>
                <article data-gallery>
                    <header class="flex items-center gap-3 mb-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">02</span>
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
                <article data-requirements>
                    <header class="flex items-center gap-3 mb-5">
                        <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">03</span>
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
        </div>

        <aside class="lg:col-span-4 lg:-mt-48 relative z-10">
            <div class="lg:sticky lg:top-24">
                <?= $this->render('_right-bar', ['model' => $model, 'gameViewButton' => false]) ?>
            </div>
        </aside>
    </div>
</section>

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
})();
JS;
$this->registerJs($js, View::POS_END);
?>
