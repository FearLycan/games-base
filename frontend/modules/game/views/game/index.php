<?php

use common\models\Category;
use common\models\Developer;
use common\models\Genre;
use common\models\Publisher;
use common\models\Tag;
use frontend\modules\game\models\searches\GameSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;

/* @var $this View */
/* @var $searchModel GameSearch */
/* @var $dataProvider ActiveDataProvider */

$this->title = 'Browse games · ' . Yii::$app->params['meta-title'];
$this->params['description'] = 'Filter and sort every game in our Steam catalog by price, genre, tag, platform, Steam Deck support and review score.';
$this->params['breadcrumbs'][] = 'Games';
$this->registerCssFile('@web/css/game.css');

$totalCount = $dataProvider->getTotalCount();

$sortOptions = [
    GameSearch::SORT_RELEASE    => 'Newest',
    GameSearch::SORT_OLDEST     => 'Oldest',
    GameSearch::SORT_NAME       => 'A — Z',
    GameSearch::SORT_REVIEWS    => 'Most positive',
    GameSearch::SORT_PRICE_ASC  => 'Price: low → high',
    GameSearch::SORT_PRICE_DESC => 'Price: high → low',
    GameSearch::SORT_META       => 'Metacritic',
];

$activeCount = $searchModel->activeFilterCount();
$baseUrl = ['/games'];

$select2Endpoint = Url::to(['/autocomplete/select2']);
$preloads = [
    'genre'     => $searchModel->preloadOptions(Genre::class,     $searchModel->genre_ids),
    'tag'       => $searchModel->preloadOptions(Tag::class,       $searchModel->tag_ids),
    'category'  => $searchModel->preloadOptions(Category::class,  $searchModel->category_ids),
    'developer' => $searchModel->preloadOptions(Developer::class, $searchModel->developer_ids),
    'publisher' => $searchModel->preloadOptions(Publisher::class, $searchModel->publisher_ids),
];

$ajaxSelect = function (string $name, string $type, string $placeholder) use ($preloads): string {
    $items = $preloads[$type] ?? [];
    $options = '';
    foreach ($items as $opt) {
        $options .= '<option value="' . (int)$opt['id'] . '" selected>' . Html::encode($opt['text']) . '</option>';
    }
    return '<select name="' . $name . '[]" multiple class="select2-ajax w-full"'
        . ' data-select2-type="' . Html::encode($type) . '"'
        . ' data-placeholder="' . Html::encode($placeholder) . '">'
        . $options
        . '</select>';
};

/**
 * Render a single-pick chip group as styled radio buttons.
 * Pass $anyLabel=null to omit the "deselect" chip (e.g. when there is no
 * sensible "no filter" state, like Type which always has a default).
 *
 * @param array<string,string> $options key => label
 */
$chipGroup = function (string $name, array $options, $currentValue, ?string $anyLabel = 'Any'): string {
    $out = '<div class="flex flex-wrap gap-1.5">';
    if ($anyLabel !== null) {
        $isAny = $currentValue === null || $currentValue === '';
        $out .= '<label class="filter-chip" data-active="' . ($isAny ? 'true' : 'false') . '">'
            . '<input type="radio" name="' . $name . '" value="" '
            . ($isAny ? 'checked' : '') . ' class="sr-only">'
            . Html::encode($anyLabel)
            . '</label>';
    }
    foreach ($options as $key => $label) {
        $active = (string)$currentValue === (string)$key;
        $out .= '<label class="filter-chip" data-active="' . ($active ? 'true' : 'false') . '">'
            . '<input type="radio" name="' . $name . '" value="' . Html::encode($key) . '" '
            . ($active ? 'checked' : '') . ' class="sr-only">'
            . Html::encode($label)
            . '</label>';
    }
    $out .= '</div>';
    return $out;
};
?>

<header class="mb-6">
    <div class="flex items-center gap-3 mb-4">
        <span class="font-mono text-[11px] uppercase tracking-[0.22em] text-fg-subtle">Catalog</span>
        <span class="h-px flex-1 bg-line"></span>
        <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-fg-subtle">
            <?= number_format($totalCount) ?> games
        </span>
    </div>
    <h1 class="font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight leading-tight">
        Browse every Steam game
    </h1>
    <p class="mt-3 max-w-3xl text-fg-muted leading-relaxed">
        Our full Steam catalog in one place. Filter by price, genre, platform and reviews to find your next favourite.
    </p>
</header>

<form method="get" action="<?= Url::to($baseUrl) ?>" data-games-form>

    <div class="mb-3 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px] max-w-xl">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-fg-subtle pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text"
                   name="q"
                   value="<?= Html::encode($searchModel->q ?? '') ?>"
                   placeholder="Search by title…"
                   class="w-full h-11 pl-10 pr-3 rounded-xl border border-line bg-canvas text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent">
        </div>

        <button type="button"
                data-filters-toggle
                aria-expanded="false"
                class="h-11 px-4 inline-flex items-center gap-2 rounded-xl border border-line bg-canvas text-sm font-medium text-fg hover:bg-surface hover:border-line-strong transition">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="20" y2="12"/><line x1="10" y1="18" x2="20" y2="18"/>
                <circle cx="6" cy="12" r="1.5" fill="currentColor"/><circle cx="9" cy="6" r="1.5" fill="currentColor"/><circle cx="12" cy="18" r="1.5" fill="currentColor"/>
            </svg>
            <span>Filters</span>
            <?php if ($activeCount): ?>
                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-accent text-canvas text-[10px] font-bold"><?= $activeCount ?></span>
            <?php endif; ?>
            <svg class="h-3 w-3 transition-transform" data-filters-chevron viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>

        <label class="flex items-center gap-2 ml-auto">
            <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle">Sort</span>
            <select name="sort"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-line bg-canvas px-3 py-2 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-accent/30">
                <?php foreach ($sortOptions as $key => $label): ?>
                    <option value="<?= Html::encode($key) ?>" <?= $searchModel->sort === $key ? 'selected' : '' ?>>
                        <?= Html::encode($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div data-filters-panel
         class="filters-panel"
         <?= $activeCount ? 'data-open="true"' : 'data-open="false"' ?>>
        <div class="rounded-2xl border border-line bg-canvas p-5 sm:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-6">

                <div>
                    <h4 class="filter-label">Price</h4>
                    <?= $chipGroup('price', GameSearch::PRICE_BUCKETS, $searchModel->price) ?>
                </div>

                <div>
                    <h4 class="filter-label">Release date</h4>
                    <?= $chipGroup('release', GameSearch::RELEASE_BUCKETS, $searchModel->release) ?>
                </div>

                <div>
                    <h4 class="filter-label">Type</h4>
                    <?= $chipGroup('game_type', GameSearch::TYPE_BUCKETS, $searchModel->game_type ?? 'game') ?>
                </div>

                <div>
                    <h4 class="filter-label">Reviews</h4>
                    <?= $chipGroup('reviews', GameSearch::REVIEW_BUCKETS, $searchModel->reviews) ?>
                </div>

                <div>
                    <h4 class="filter-label">Platform</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach (['windows' => 'Windows', 'mac' => 'Mac', 'linux' => 'Linux'] as $key => $label):
                            $platforms = is_array($searchModel->platform) ? $searchModel->platform : [];
                            $active = in_array($key, $platforms, true);
                        ?>
                            <label class="filter-chip" data-active="<?= $active ? 'true' : 'false' ?>">
                                <input type="checkbox" name="platform[]" value="<?= Html::encode($key) ?>" <?= $active ? 'checked' : '' ?> class="sr-only">
                                <?= Html::encode($label) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <h4 class="filter-label">Steam Deck</h4>
                    <?= $chipGroup('deck', GameSearch::DECK_BUCKETS, $searchModel->deck) ?>
                </div>

                <div>
                    <h4 class="filter-label">Metacritic min</h4>
                    <div class="flex items-center gap-3">
                        <input type="range"
                               min="0" max="100" step="5"
                               name="meta_min"
                               value="<?= (int)($searchModel->meta_min ?: 0) ?>"
                               data-meta-range
                               class="flex-1 accent-emerald-600">
                        <span class="font-mono text-xs text-fg-muted min-w-8 text-right" data-meta-value><?= (int)($searchModel->meta_min ?: 0) ?></span>
                    </div>
                </div>

                <div>
                    <h4 class="filter-label">Age rating</h4>
                    <div class="flex flex-wrap gap-1.5">
                        <label class="filter-chip" data-active="<?= !$searchModel->age_adult ? 'true' : 'false' ?>">
                            <input type="radio" name="age_adult" value="" <?= !$searchModel->age_adult ? 'checked' : '' ?> class="sr-only">
                            All ages
                        </label>
                        <label class="filter-chip" data-active="<?= $searchModel->age_adult === '1' ? 'true' : 'false' ?>">
                            <input type="radio" name="age_adult" value="1" <?= $searchModel->age_adult === '1' ? 'checked' : '' ?> class="sr-only">
                            Adult only
                        </label>
                    </div>
                </div>

            </div>

            <div class="mt-6 pt-6 border-t border-line/70 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">
                <div>
                    <h4 class="filter-label">Genre</h4>
                    <?= $ajaxSelect('genre_ids', 'genre', 'Any genre') ?>
                </div>
                <div>
                    <h4 class="filter-label">Tag</h4>
                    <?= $ajaxSelect('tag_ids', 'tag', 'Any tag') ?>
                </div>
                <div>
                    <h4 class="filter-label">Feature</h4>
                    <?= $ajaxSelect('category_ids', 'category', 'Any feature') ?>
                </div>
                <div>
                    <h4 class="filter-label">Developer</h4>
                    <?= $ajaxSelect('developer_ids', 'developer', 'Any developer') ?>
                </div>
                <div>
                    <h4 class="filter-label">Publisher</h4>
                    <?= $ajaxSelect('publisher_ids', 'publisher', 'Any publisher') ?>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-line/70 flex flex-wrap items-center gap-3">
                <button type="submit" class="h-10 px-5 rounded-xl bg-fg text-canvas text-sm font-semibold hover:bg-fg/90 transition">
                    Apply filters
                </button>
                <a href="<?= Url::to($baseUrl) ?>" class="h-10 px-4 inline-flex items-center rounded-xl border border-line bg-canvas text-sm font-medium text-fg-muted hover:bg-surface hover:text-fg transition">
                    Reset
                </a>
                <span class="ml-auto text-xs text-fg-subtle font-mono">
                    <?= $activeCount ?> active
                </span>
            </div>
        </div>
    </div>
</form>

<?php if ($totalCount === 0): ?>
    <div class="mt-8 rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center">
        <p class="font-display text-lg font-semibold text-fg">No games match your filters</p>
        <p class="mt-2 text-sm text-fg-muted">Try clearing some filters or pick another sort.</p>
        <a href="<?= Url::to($baseUrl) ?>" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">
            Reset filters
        </a>
    </div>
<?php else: ?>
    <div class="mt-8">
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView'     => '_item',
            'summary'      => false,
            'layout'       => "<div class=\"grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-5 gap-y-7\">{items}</div>\n{pager}",
            'itemOptions'  => ['tag' => 'div'],
            'pager'        => ['class' => \common\widgets\Pager::class],
        ]) ?>
    </div>
<?php endif; ?>

<?php
$select2Url = Json::encode($select2Endpoint);
$js = <<<JS
(function () {
    var toggle = document.querySelector('[data-filters-toggle]');
    var panel = document.querySelector('[data-filters-panel]');
    var chevron = document.querySelector('[data-filters-chevron]');
    if (toggle && panel) {
        function setOpen(open) {
            panel.setAttribute('data-open', open ? 'true' : 'false');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
        }
        setOpen(panel.getAttribute('data-open') === 'true');
        toggle.addEventListener('click', function () {
            setOpen(panel.getAttribute('data-open') !== 'true');
        });
    }

    document.querySelectorAll('.filter-chip input').forEach(function (input) {
        input.addEventListener('change', function () {
            var group = input.closest('.flex.flex-wrap');
            if (!group) return;
            if (input.type === 'radio') {
                group.querySelectorAll('.filter-chip').forEach(function (chip) {
                    chip.setAttribute('data-active', 'false');
                });
            }
            input.closest('.filter-chip').setAttribute('data-active', input.checked ? 'true' : 'false');
        });
    });

    var range = document.querySelector('[data-meta-range]');
    var rangeVal = document.querySelector('[data-meta-value]');
    if (range && rangeVal) {
        range.addEventListener('input', function () { rangeVal.textContent = range.value; });
    }

    jQuery(function (\$) {
        \$('.select2-ajax').each(function () {
            var \$el = \$(this);
            var type = \$el.data('select2-type');
            \$el.select2({
                placeholder: \$el.data('placeholder') || 'Select…',
                allowClear: true,
                width: '100%',
                minimumInputLength: 0,
                ajax: {
                    url: {$select2Url},
                    dataType: 'json',
                    delay: 220,
                    data: function (params) {
                        return { type: type, q: params.term || '', page: params.page || 1 };
                    },
                    processResults: function (data) { return data; },
                    cache: true
                }
            });
        });
    });
})();
JS;
$this->registerJs($js, View::POS_END);
?>
