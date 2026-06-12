<?php

use common\enums\AfterDarkLayout;
use yii\data\ActiveDataProvider;

/**
 * @var \yii\web\View                                   $this
 * @var AfterDarkLayout                                 $layout   current theme
 * @var \frontend\modules\game\models\searches\GameSearch $searchModel
 * @var ActiveDataProvider                              $dataProvider
 * @var \common\models\Game[]                           $featured hero spotlight
 * @var \common\models\Game[]                           $fresh    "just arrived" rail
 * @var int                                             $total
 * @var bool                                            $filtering whether a search/facet is active
 * @var array{genres:array,tags:array}                  $facets   top filter facets among adult games
 */

$this->title = 'After Dark · ' . Yii::$app->name;
?>

<?php if (!$filtering): ?>
    <?= $this->render('_hero-' . $layout->value, ['featured' => $featured, 'total' => $total]) ?>
<?php endif; ?>

<div class="mx-auto max-w-7xl px-6 lg:px-8">
    <?php if (!$filtering && $fresh): ?>
        <?= $this->render('_rail', ['fresh' => $fresh]) ?>
    <?php endif; ?>

    <?= $this->render('_catalog', [
        'searchModel'  => $searchModel,
        'dataProvider' => $dataProvider,
        'total'        => $total,
        'facets'       => $facets,
    ]) ?>
</div>

<?php
/**
 * Shared quick-view modal. One per page; opened by the script below from any
 * game tile in the area and filled over AJAX with the _quick-view partial.
 */
?>
<div data-qv-modal data-qv-url="<?= \yii\helpers\Url::to(['/after-dark/quick-view']) ?>" hidden
     class="fixed inset-0 z-[100] overflow-y-auto bg-black/75 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-label="Game quick view">
    <div class="flex min-h-full items-start justify-center p-4 sm:items-center sm:p-6">
        <div data-qv-panel class="relative w-full max-w-5xl overflow-hidden rounded-2xl border border-line bg-surface shadow-[0_30px_90px_-28px_rgba(0,0,0,0.78)]">
            <span class="pointer-events-none absolute inset-x-12 top-0 h-px bg-gradient-to-r from-transparent via-accent/70 to-transparent"></span>
            <span class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-accent/[0.07] to-transparent"></span>
            <button type="button" data-qv-close aria-label="Close"
                    class="absolute right-3 top-3 z-10 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-surface-2/80 text-fg ring-1 ring-line backdrop-blur transition hover:text-accent hover:ring-line-strong">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
            <div data-qv-content class="relative"></div>
        </div>
    </div>
</div>

<?php
$this->registerCss(<<<'CSS'
.qv-track{scrollbar-width:none;scroll-behavior:smooth}
.qv-track::-webkit-scrollbar{display:none}
.qv-dot{width:.375rem}
.qv-dot.is-on{width:1.25rem;background:var(--color-accent)}
.qv-cta{box-shadow:0 12px 30px -12px color-mix(in srgb, var(--color-accent) 70%, transparent)}
.qv-cta:hover{box-shadow:0 14px 36px -12px color-mix(in srgb, var(--color-accent) 88%, transparent)}
@keyframes qv-pop{from{opacity:0;transform:translateY(10px) scale(.985)}to{opacity:1;transform:none}}
@keyframes qv-veil{from{opacity:0}to{opacity:1}}
[data-qv-modal]:not([hidden]){animation:qv-veil .22s ease both}
[data-qv-modal]:not([hidden]) [data-qv-panel]{animation:qv-pop .5s cubic-bezier(.16,1,.3,1) both}
@media (prefers-reduced-motion:reduce){[data-qv-modal]:not([hidden]),[data-qv-modal]:not([hidden]) [data-qv-panel]{animation:none}}
CSS);

$this->registerJs(<<<'JS'
(function () {
    var modal = document.querySelector('[data-qv-modal]');
    if (!modal) return;
    var content = modal.querySelector('[data-qv-content]');
    var main = document.querySelector('main');
    var endpoint = modal.getAttribute('data-qv-url');
    var SPINNER = '<div class="grid place-items-center py-28"><div class="h-8 w-8 animate-spin rounded-full border-2 border-line border-t-accent"></div></div>';
    var lastFocus = null;

    function open(id) {
        lastFocus = document.activeElement;
        content.innerHTML = SPINNER;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        var url = endpoint + (endpoint.indexOf('?') > -1 ? '&' : '?') + 'id=' + encodeURIComponent(id);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
            .then(function (html) { content.innerHTML = html; initSlider(content); modal.scrollTop = 0; })
            .catch(function () {
                content.innerHTML = '<div class="px-6 py-16 text-center text-sm text-fg-muted">Couldn’t load this title. <button type="button" data-qv-close class="text-accent underline">Close</button></div>';
            });
    }

    function close() {
        modal.hidden = true;
        content.innerHTML = '';
        document.body.style.overflow = '';
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    // Open from any game tile in the area: catalogue/rail tiles carry the appid
    // on [data-shots-id]; the hero cards on [data-quick-view].
    if (main) {
        main.addEventListener('click', function (e) {
            var a = e.target.closest('a');
            if (!a || !main.contains(a)) return;
            var id = a.getAttribute('data-quick-view');
            if (!id && a.classList.contains('game-card')) {
                var s = a.querySelector('[data-shots-id]');
                id = s && s.getAttribute('data-shots-id');
            }
            if (!id) return;
            e.preventDefault();
            open(id);
        });
    }

    modal.addEventListener('click', function (e) {
        if (e.target.closest('[data-qv-close]') || !e.target.closest('[data-qv-panel]')) {
            e.preventDefault();
            close();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) close();
    });

    function initSlider(root) {
        var slider = root.querySelector('[data-qv-media]');
        if (!slider) return;
        var track = slider.querySelector('[data-qv-track]');
        var prev = slider.querySelector('[data-qv-prev]');
        var next = slider.querySelector('[data-qv-next]');
        var dots = slider.querySelectorAll('[data-qv-dot]');

        function sync() {
            if (!track.clientWidth) return;
            var i = Math.round(track.scrollLeft / track.clientWidth);
            var max = track.scrollWidth - track.clientWidth - 1;
            if (prev) prev.disabled = track.scrollLeft <= 2;
            if (next) next.disabled = track.scrollLeft >= max;
            dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
        }
        function go(dir) { track.scrollBy({ left: dir * track.clientWidth, behavior: 'smooth' }); }

        if (prev) prev.addEventListener('click', function () { go(-1); });
        if (next) next.addEventListener('click', function () { go(1); });
        dots.forEach(function (d, k) {
            d.addEventListener('click', function () { track.scrollTo({ left: k * track.clientWidth, behavior: 'smooth' }); });
        });
        track.addEventListener('scroll', sync, { passive: true });

        // Tailwind's browser runtime applies the slide layout a tick *after* this
        // markup is injected (and screenshots decode later still), so a single
        // synchronous sync() on the very first open runs before the track has its
        // real scrollWidth — leaving the "next" arrow wrongly disabled/hidden.
        // Re-sync whenever the track's box actually changes (styles land, images
        // decode), plus a couple of frames out, so the arrows settle correctly.
        if (window.ResizeObserver) {
            new ResizeObserver(sync).observe(track);
        }
        slider.querySelectorAll('img').forEach(function (img) {
            if (!img.complete) img.addEventListener('load', sync, { once: true });
        });
        requestAnimationFrame(function () { requestAnimationFrame(sync); });
        sync();

        // Swap the trailer poster for the autoplaying embed in place.
        var play = slider.querySelector('[data-qv-play]');
        if (play) {
            play.addEventListener('click', function () {
                var slide = play.closest('.qv-slide');
                var embed = slide.getAttribute('data-embed');
                if (!embed) return;
                slide.innerHTML = '<iframe class="absolute inset-0 h-full w-full" src="' + embed + '" title="Trailer" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
            });
        }
    }
})();
JS, \yii\web\View::POS_END);
?>
