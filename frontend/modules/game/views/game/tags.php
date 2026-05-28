<?php

use common\models\Tag;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $tags Tag[] */
/* @var $hub array */

$this->title = 'Browse by tag — ' . Yii::$app->params['meta-title'];
$this->params['description'] = 'Every Steam tag with at least one active game — Souls-like, Cozy, Roguelite, you name it. Filter to find your niche.';
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
$this->params['breadcrumbs'][] = 'Tags';
$this->registerCssFile('@web/css/game.css');
?>

<section class="relative left-1/2 w-screen -ml-[50vw] overflow-hidden bg-gradient-to-b from-rose-50/20 via-amber-50/10 to-canvas pt-12 pb-14 sm:pt-16">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 h-[460px] w-[460px] rounded-full bg-gradient-to-br from-rose-200/40 to-amber-200/40 opacity-40 blur-3xl"></div>
        <div class="absolute -bottom-20 right-0 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-violet-200/40 to-pink-200/40 opacity-30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-3 py-1 text-xs font-medium">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                Directory
            </span>
            <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">
                <?= number_format($hub['count']) ?> tags · <?= number_format($hub['games']) ?> games
            </span>
        </div>

        <h1 class="font-display text-4xl sm:text-5xl font-bold text-fg tracking-tight leading-[1.05] max-w-3xl">
            Browse by <span class="text-accent">tag</span>
        </h1>
        <p class="mt-5 max-w-2xl text-fg-muted leading-relaxed">
            <?= Html::encode($hub['intro']) ?>
        </p>

        <?php if (!empty($tags)): ?>
            <div class="mt-8 max-w-md">
                <label for="tagFilter" class="sr-only">Filter tags</label>
                <div class="relative">
                    <span aria-hidden="true" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-fg-subtle">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path></svg>
                    </span>
                    <input id="tagFilter"
                           type="text"
                           autocomplete="off"
                           placeholder="Filter tags…"
                           class="w-full rounded-full border border-line bg-canvas py-2.5 pl-10 pr-4 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent transition">
                </div>
                <p class="mt-2 ml-2 text-[11px] font-mono text-fg-subtle" data-tag-count></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="mt-10">
    <?php if (empty($tags)): ?>
        <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center max-w-3xl mx-auto">
            <p class="font-display text-lg font-semibold text-fg">No tags with games yet</p>
            <p class="mt-2 text-sm text-fg-muted">Tags get linked as games sync. Try again after the next refresh.</p>
        </div>
    <?php else: ?>
        <div id="tagGrid" class="flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
                <a href="<?= Url::to(['/game/game/list-by-tag', 'slug' => $tag->slug]) ?>"
                   class="tag-pill group inline-flex items-center gap-2 rounded-full bg-canvas ring-1 ring-line px-3.5 py-1.5 text-sm font-medium text-fg-muted hover:ring-line-strong hover:bg-surface hover:text-fg transition"
                   data-tag-name="<?= Html::encode(mb_strtolower($tag->name)) ?>">
                    <span class="truncate"><?= Html::encode($tag->name) ?></span>
                    <span class="font-mono text-[10px] tabular-nums text-fg-subtle group-hover:text-fg-muted transition">
                        <?= number_format((int)$tag->games_count) ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <p id="tagEmpty" hidden class="mt-8 text-center text-sm text-fg-subtle">
            No tags match — try a different word.
        </p>
    <?php endif; ?>
</section>

<section class="mt-20 mb-8 text-center">
    <p class="text-sm text-fg-muted mb-4">Prefer broader strokes?</p>
    <a href="<?= Url::to(['/genres']) ?>"
       class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
        Browse by genre <span aria-hidden="true">→</span>
    </a>
</section>

<?php
$js = <<<'JS'
(function () {
    var input  = document.getElementById('tagFilter');
    var grid   = document.getElementById('tagGrid');
    var empty  = document.getElementById('tagEmpty');
    var count  = document.querySelector('[data-tag-count]');
    if (!input || !grid) return;

    var pills = Array.prototype.slice.call(grid.querySelectorAll('.tag-pill'));
    var total = pills.length;
    if (count) count.textContent = total + ' tags';

    function update(q) {
        q = (q || '').trim().toLowerCase();
        var shown = 0;
        pills.forEach(function (pill) {
            var name = pill.getAttribute('data-tag-name') || '';
            var match = !q || name.indexOf(q) !== -1;
            pill.hidden = !match;
            if (match) shown++;
        });
        if (empty) empty.hidden = shown !== 0;
        if (count) count.textContent = q ? shown + ' of ' + total + ' tags' : total + ' tags';
    }

    var t;
    input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { update(input.value); }, 80);
    });
})();
JS;
$this->registerJs($js, View::POS_END);
?>
