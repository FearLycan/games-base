<?php

use frontend\modules\game\models\searches\GameSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;

/**
 * Shared catalogue section for the After Dark area: a search + sort bar and a
 * collapsible facet filter (genres / tags / features), over a tiled, paginated
 * grid of adult-only games. Token-driven, so it re-skins with the active theme.
 *
 * @var \yii\web\View      $this
 * @var GameSearch         $searchModel
 * @var ActiveDataProvider $dataProvider
 * @var int                $total
 * @var array{genres:array,tags:array} $facets
 */

$sortOptions = [
    GameSearch::SORT_RELEASE    => 'Newest',
    GameSearch::SORT_REVIEWS    => 'Most positive',
    GameSearch::SORT_NAME       => 'A — Z',
    GameSearch::SORT_PRICE_ASC  => 'Price: low → high',
    GameSearch::SORT_PRICE_DESC => 'Price: high → low',
];

// Current selections, read straight from the query (GameSearch has no form name,
// so the params sit at the top level: genre_ids[], tag_ids[], category_ids[]).
$q = Yii::$app->request->queryParams;
$selected = [
    'genre_ids'    => array_values(array_filter(array_map('intval', (array)($q['genre_ids'] ?? [])))),
    'tag_ids'      => array_values(array_filter(array_map('intval', (array)($q['tag_ids'] ?? [])))),
    'category_ids' => array_values(array_filter(array_map('intval', (array)($q['category_ids'] ?? [])))),
];
$activeFilters = count($selected['genre_ids']) + count($selected['tag_ids']) + count($selected['category_ids']);

// "Clear" drops every facet but keeps the search term and sort.
$clearParams = ['/after-dark/index', 'sort' => $searchModel->sort];
if ($searchModel->q) {
    $clearParams['q'] = $searchModel->q;
}
$clearUrl = Url::to($clearParams);

// Link that adds/removes one id from a facet, keeping every other param and
// dropping back to page 1.
$toggleUrl = static function (string $param, int $id) use ($q, $selected): string {
    $cur = $selected[$param];
    $next = in_array($id, $cur, true) ? array_values(array_diff($cur, [$id])) : [...$cur, $id];
    $params = array_merge($q, [$param => $next]);
    unset($params['page']);
    return Url::to(array_merge(['/after-dark/index'], $params));
};

$facetRow = static function (string $title, string $param, array $items) use ($selected, $toggleUrl): void {
    if ($items === []) {
        return;
    }
    echo '<div class="mt-4 first:mt-0">';
    echo '<h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-2">' . Html::encode($title) . '</h4>';
    echo '<div class="flex flex-wrap gap-1.5">';
    foreach ($items as $item) {
        $id = (int)$item['id'];
        $on = in_array($id, $selected[$param], true);
        echo Html::a(
            Html::encode($item['name']),
            $toggleUrl($param, $id),
            ['class' => 'rounded-full border px-3 py-1.5 text-xs font-medium transition ' . ($on
                ? 'border-transparent bg-accent text-white'
                : 'border-line bg-surface/60 text-fg-muted hover:border-line-strong hover:text-fg')]
        );
    }
    echo '</div></div>';
};
?>
<section id="catalog" class="pt-20 scroll-mt-20">
    <div class="flex items-center gap-3 mb-4">
        <span class="font-mono text-[11px] uppercase tracking-[0.22em] text-fg-subtle">The catalog</span>
        <span class="h-px flex-1 bg-line"></span>
        <span class="font-mono text-[11px] uppercase tracking-wider text-fg-subtle"><?= number_format($total) ?> titles</span>
    </div>
    <h2 class="font-display text-4xl text-fg mb-6">Browse every adult title</h2>

    <form method="get" action="<?= Url::to(['/after-dark/index']) ?>" class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-fg-subtle pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" name="q" value="<?= Html::encode($searchModel->q ?? '') ?>" placeholder="Search adult titles…"
                   class="w-full h-11 pl-10 pr-3 rounded-xl border border-line bg-surface/60 text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
        </div>
        <label class="flex items-center gap-2 ml-auto">
            <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle">Sort</span>
            <select name="sort" onchange="this.form.submit()"
                    class="rounded-lg border border-line bg-surface/60 px-3 py-2 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-accent/40">
                <?php foreach ($sortOptions as $key => $label): ?>
                    <option value="<?= Html::encode($key) ?>" <?= $searchModel->sort === $key ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php // keep active facet filters when searching/sorting ?>
        <?php foreach ($selected as $param => $ids): ?>
            <?php foreach ($ids as $id): ?>
                <input type="hidden" name="<?= $param ?>[]" value="<?= (int)$id ?>">
            <?php endforeach; ?>
        <?php endforeach; ?>
        <noscript><button type="submit" class="h-11 px-4 rounded-xl border border-line bg-surface/60 text-sm text-fg">Go</button></noscript>
    </form>

    <details class="mb-8 rounded-2xl border border-line bg-surface/30" <?= $activeFilters ? 'open' : '' ?>>
        <summary class="flex cursor-pointer list-none items-center gap-2 px-5 py-3.5 text-sm font-medium text-fg select-none [&::-webkit-details-marker]:hidden">
            <svg class="h-4 w-4 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="20" y2="12"/><line x1="10" y1="18" x2="20" y2="18"/></svg>
            Filters
            <?php if ($activeFilters): ?>
                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-accent text-white text-[10px] font-bold"><?= $activeFilters ?></span>
            <?php endif; ?>
            <span class="ml-auto flex items-center gap-3">
                <?php if ($activeFilters): ?>
                    <a href="<?= $clearUrl ?>" class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle hover:text-accent">Clear</a>
                <?php endif; ?>
                <svg class="h-4 w-4 text-fg-subtle transition-transform [[open]_&]:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </span>
        </summary>
        <div class="px-5 pb-5 pt-1 border-t border-line/70">
            <?php
            $facetRow('Genres', 'genre_ids', $facets['genres']);
            $facetRow('Tags', 'tag_ids', $facets['tags']);
            ?>
        </div>
    </details>

    <?php if ($total === 0): ?>
        <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center">
            <p class="font-display text-2xl text-fg">Nothing matches</p>
            <p class="mt-2 text-sm text-fg-muted">Try a different keyword or loosen the filters.</p>
            <a href="<?= Url::to(['/after-dark/index']) ?>" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">Reset</a>
        </div>
    <?php else: ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView'     => '@frontend/modules/game/views/game/_item',
            'summary'      => false,
            'layout'       => "<div class=\"grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-5 gap-y-8\">{items}</div>\n{pager}",
            'itemOptions'  => ['tag' => 'div'],
            'pager'        => ['class' => \common\widgets\Pager::class],
        ]) ?>
    <?php endif; ?>
</section>
