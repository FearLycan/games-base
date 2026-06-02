<?php

use common\models\GameAchievement;
use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this \yii\web\View */
/* @var $model Game */
/* @var $achievements GameAchievement[] */
/* @var $shown int */
/* @var $remaining int */
/* @var $lockedCount int */
/* @var $rarest GameAchievement|null */
/* @var $hiddenCount int */
/* @var $tierCounts array{ultra:int,rare:int,uncommon:int,common:int} */

$this->title = $model->title . ' Achievements - ' . Yii::$app->params['meta-title'];
$this->params['description'] = sprintf(
    'All %s achievements for %s — names, descriptions, icons and how rare each one is to unlock on Steam.',
    number_format((int)$model->achievements_total),
    $model->title,
);

$gameUrl = Url::to(['/game/game/view', 'id' => $model->steam_appid, 'slug' => $model->slug]);
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => $gameUrl];
$this->params['breadcrumbs'][] = 'Achievements';
$this->registerCssFile('@web/css/achievements.css');

$total = (int)$model->achievements_total;
?>

<section class="ach-hero relative left-1/2 w-screen -ml-[50vw] mb-10 sm:mb-12"
         style="background-image:url('<?= Html::encode($model->getBackground()) ?>')">
    <div class="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-20 sm:pb-24">
        <a href="<?= Html::encode($gameUrl) ?>" class="ach-back">
            <span aria-hidden="true">←</span> Back to <?= Html::encode($model->title) ?>
        </a>

        <div class="mt-6 flex items-start gap-5 sm:gap-6 ach-hero-text">
            <img src="<?= Html::encode($model->getIcon()) ?>"
                 alt="<?= Html::encode($model->title) ?>"
                 loading="lazy"
                 class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover ring-2 ring-white/15 shadow-2xl shadow-black/40 shrink-0">
            <div class="min-w-0">
                <span class="ach-kicker">
                    <i class="fa-solid fa-trophy text-[10px] leading-none" aria-hidden="true"></i>
                    Achievements
                </span>
                <h1 class="mt-2 font-display text-3xl sm:text-4xl font-bold text-white tracking-tight leading-tight">
                    <?= Html::encode($model->title) ?>
                </h1>
                <p class="mt-2 text-sm sm:text-[15px] text-white/85">
                    <?= number_format($total) ?> achievement<?= $total === 1 ? '' : 's' ?> to unlock on Steam
                </p>
            </div>
        </div>
    </div>
</section>

<section class="relative -mt-16 sm:-mt-20 mb-10">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <div class="ach-stat">
            <span class="ach-stat-value"><?= number_format($total) ?></span>
            <span class="ach-stat-label">Total achievements</span>
        </div>
        <div class="ach-stat">
            <span class="ach-stat-value"><?= $rarest && $rarest->getPercentLabel() ? Html::encode($rarest->getPercentLabel()) : '—' ?></span>
            <span class="ach-stat-label"><?= $rarest ? 'Rarest unlock rate' : 'Rarity' ?></span>
        </div>
        <div class="ach-stat col-span-2 sm:col-span-1">
            <span class="ach-stat-value"><?= $hiddenCount > 0 ? number_format($hiddenCount) : number_format($shown) ?></span>
            <span class="ach-stat-label"><?= $hiddenCount > 0 ? 'Hidden achievements' : 'Listed here' ?></span>
        </div>
    </div>
</section>

<?php if (!empty($achievements)): ?>
    <section class="mb-12">
        <header class="flex flex-wrap items-center gap-3 mb-5">
            <h2 class="font-display text-xl sm:text-2xl font-semibold text-fg">
                <?= $remaining > 0 ? 'Featured achievements' : 'All achievements' ?>
            </h2>
            <span class="hidden sm:block h-px flex-1 bg-line"></span>
            <span class="font-mono text-[11px] text-fg-subtle"><?= number_format($shown) ?> shown</span>
        </header>

        <?php if ($rarest !== null): ?>
            <?php
            $tierMeta = [
                'common'   => 'Common',
                'uncommon' => 'Uncommon',
                'rare'     => 'Rare',
                'ultra'    => 'Ultra rare',
            ];
            ?>
            <div class="ach-toolbar mb-6">
                <div class="ach-filter" role="group" aria-label="Filter by rarity">
                    <button type="button" class="ach-filter-chip" data-ach-filter="all" data-active="true">
                        All <span class="ach-filter-count"><?= number_format($shown) ?></span>
                    </button>
                    <?php foreach ($tierMeta as $key => $label): ?>
                        <?php if (($tierCounts[$key] ?? 0) > 0): ?>
                            <button type="button" class="ach-filter-chip ach-tier-<?= $key ?>" data-ach-filter="<?= $key ?>" data-active="false">
                                <span class="ach-filter-dot" aria-hidden="true"></span>
                                <?= Html::encode($label) ?>
                                <span class="ach-filter-count"><?= number_format($tierCounts[$key]) ?></span>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <label class="ach-search">
                    <i class="fa-solid fa-magnifying-glass ach-search-icon" aria-hidden="true"></i>
                    <input type="search"
                           class="ach-search-input"
                           data-ach-search
                           placeholder="Search achievements…"
                           autocomplete="off"
                           spellcheck="false"
                           aria-label="Search achievements by name">
                </label>

                <div class="ach-sort" role="group" aria-label="Sort by rarity">
                    <button type="button" class="ach-sort-opt" data-ach-sort="common" data-active="true">Most common</button>
                    <button type="button" class="ach-sort-opt" data-ach-sort="rare" data-active="false">Rarest first</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="ach-grid" data-ach-grid>
            <p class="ach-empty" data-ach-empty hidden>No achievements match this filter.</p>
            <?php foreach ($achievements as $i => $achievement): ?>
                <?php
                $tier = $achievement->getRarityTier();
                $percent = $achievement->percent !== null ? (float)$achievement->percent : null;
                $isHidden = (bool)$achievement->hidden;
                $desc = $achievement->description;
                ?>
                <article class="ach-card ach-tier-<?= $tier ?>"
                         style="--i: <?= $i ?>"
                         data-percent="<?= $percent !== null ? $percent : '' ?>"
                         data-tier="<?= $percent !== null ? $tier : '' ?>"
                         data-name="<?= Html::encode(mb_strtolower($achievement->name)) ?>"><?php /* sortable + filterable + searchable */ ?>
                    <div class="ach-card-icon">
                        <?php if ($achievement->icon): ?>
                            <img src="<?= Html::encode($achievement->icon) ?>"
                                 alt="<?= Html::encode($achievement->name) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <i class="fa-solid fa-medal" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>

                    <div class="ach-card-body">
                        <div class="ach-card-head">
                            <h3 class="ach-card-name"><?= Html::encode($achievement->name) ?></h3>
                            <?php if ($isHidden): ?>
                                <span class="ach-hidden-badge" title="Hidden achievement">
                                    <i class="fa-solid fa-eye-slash text-[9px]" aria-hidden="true"></i> Hidden
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($desc !== null || $isHidden): ?>
                            <p class="ach-card-desc<?= $isHidden ? ' is-spoiler' : '' ?>">
                                <?= $desc !== null ? Html::encode($desc) : 'Hidden achievement — unlock it in-game to reveal.' ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($percent !== null): ?>
                            <div class="ach-rarity">
                                <div class="ach-rarity-bar">
                                    <span style="width: <?= max(2, min(100, $percent)) ?>%"></span>
                                </div>
                                <span class="ach-rarity-label">
                                    <?= Html::encode($achievement->getPercentLabel()) ?>
                                    <span class="ach-rarity-tier"><?= Html::encode($achievement->getRarityLabel()) ?></span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $lockedCount; $i++): ?>
                <article class="ach-card ach-card-locked" style="--i: <?= $shown + $i ?>" aria-hidden="true">
                    <div class="ach-card-icon">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    </div>
                    <div class="ach-card-body">
                        <div class="ach-card-head">
                            <h3 class="ach-card-name">Locked</h3>
                        </div>
                        <p class="ach-card-desc">More achievements wait in-game.</p>
                    </div>
                </article>
            <?php endfor; ?>
        </div>

        <?php if ($remaining > 0): ?>
            <p class="mt-5 text-sm text-fg-muted text-center">
                We're showing the achievements Steam features publicly.
                <strong class="text-fg"><?= number_format($remaining) ?> more</strong>
                <?= $remaining === 1 ? 'is' : 'are' ?> waiting to be unlocked in-game.
            </p>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="mb-4">
    <div class="ach-cta">
        <span class="ach-cta-mark" aria-hidden="true">
            <i class="fa-solid fa-trophy"></i>
        </span>
        <div class="ach-cta-body">
            <h2 class="font-display text-lg sm:text-xl font-bold text-fg">Chase every achievement</h2>
            <p class="mt-1 text-sm text-fg-muted">
                Track your unlock progress and the full achievement list on Steam.
            </p>
        </div>
        <a href="<?= Html::encode($model->getSteamUrl()) ?>"
           target="_blank"
           rel="nofollow noopener external"
           class="ach-cta-btn">
            <i class="fa-brands fa-steam text-lg leading-none" aria-hidden="true"></i>
            View on Steam
            <span class="text-white/70" aria-hidden="true">↗</span>
        </a>
    </div>
</section>

<?php if ($lastSynced = $model->getLastSyncedLabel()): ?>
    <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle/70 text-center">
        Data synced from Steam · <?= Html::encode($lastSynced) ?>
    </p>
<?php endif; ?>

<?php
$js = <<<JS
(function () {
    var grid = document.querySelector('[data-ach-grid]');
    if (!grid) return;

    var sortButtons = document.querySelectorAll('[data-ach-sort]');
    var filterButtons = document.querySelectorAll('[data-ach-filter]');
    var search = document.querySelector('[data-ach-search]');
    var empty = grid.querySelector('[data-ach-empty]');

    // Real achievement cards sort/filter/search; decorative "locked" ghosts
    // always trail and only show under the "all" filter with no active search.
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.ach-card:not(.ach-card-locked)'));
    var locked = Array.prototype.slice.call(grid.querySelectorAll('.ach-card-locked'));

    var state = { sort: 'common', filter: 'all', query: '' };

    function value(card) {
        var raw = card.getAttribute('data-percent');
        if (raw === null || raw === '') return null;
        var n = parseFloat(raw);
        return isNaN(n) ? null : n;
    }

    function render() {
        var ordered = cards.slice().sort(function (a, b) {
            var pa = value(a), pb = value(b);
            if (pa === null && pb === null) return 0;
            if (pa === null) return 1; // unknown rarity sinks to the bottom
            if (pb === null) return -1;
            return state.sort === 'rare' ? pa - pb : pb - pa;
        });

        var visible = 0;
        ordered.forEach(function (card) {
            var tierOk = state.filter === 'all' || card.getAttribute('data-tier') === state.filter;
            var queryOk = state.query === '' || (card.getAttribute('data-name') || '').indexOf(state.query) !== -1;
            var match = tierOk && queryOk;
            card.hidden = !match;
            if (match) visible++;
            // appendChild moves the existing node, so this reorders in place.
            grid.appendChild(card);
        });

        // Ghosts only make sense in the unfiltered, unsearched default view.
        locked.forEach(function (card) {
            card.hidden = state.filter !== 'all' || state.query !== '';
            grid.appendChild(card);
        });

        if (empty) {
            empty.hidden = visible > 0;
            grid.appendChild(empty);
        }
    }

    function wire(buttons, key, attr) {
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.getAttribute('data-active') === 'true') return;
                buttons.forEach(function (b) {
                    b.setAttribute('data-active', b === btn ? 'true' : 'false');
                });
                state[key] = btn.getAttribute(attr);
                render();
            });
        });
    }

    wire(sortButtons, 'sort', 'data-ach-sort');
    wire(filterButtons, 'filter', 'data-ach-filter');

    if (search) {
        search.addEventListener('input', function () {
            state.query = search.value.trim().toLowerCase();
            render();
        });
    }
})();
JS;
$this->registerJs($js, View::POS_END);
?>
