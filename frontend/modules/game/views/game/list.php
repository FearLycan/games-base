<?php

use common\models\Category;
use common\models\Genre;
use common\models\Tag;
use common\schema\factory\ItemListSchemaFactory;
use common\schema\JsonLdRenderer;
use frontend\modules\game\models\searches\GameSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;

/* @var $this View */
/* @var $searchModel GameSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $model Genre|Category|Tag */
/* @var $stats array */

$this->title = "Best {$model->name} games on Steam" . " - " . Yii::$app->params['meta-title'];
$this->params['description'] = ($model->description ?? null)
    ? mb_substr(trim(strip_tags($model->description)), 0, 160)
    : "Browse the best {$model->name} games on Steam — ranked by reviews, with prices, ratings and release dates in one place.";
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
if ($model instanceof Tag) {
    $this->params['breadcrumbs'][] = ['label' => 'Tags', 'url' => ['/tags']];
} elseif ($model instanceof Category) {
    $this->params['breadcrumbs'][] = ['label' => 'Features', 'url' => ['/categories']];
} elseif ($model instanceof Genre) {
    $this->params['breadcrumbs'][] = ['label' => 'Genres', 'url' => ['/genres']];
}
$this->params['breadcrumbs'][] = $model->name;
$this->registerCssFile('@web/css/game.css');

$models = $dataProvider->getModels();
$totalCount = $dataProvider->getTotalCount();
$kind = match (true) {
    $model instanceof Category => 'Category',
    $model instanceof Tag      => 'Tag',
    default                    => 'Genre',
};

$pagination = $dataProvider->getPagination();
$startPosition = $pagination !== false ? $pagination->getPage() * $pagination->getPageSize() + 1 : 1;
echo JsonLdRenderer::render([
    ItemListSchemaFactory::fromGames($models, "Best {$model->name} games on Steam", $startPosition, $totalCount),
]);
?>

<header class="mb-10">
    <div class="flex items-center gap-3 mb-4">
        <span class="font-mono text-[11px] uppercase tracking-[0.22em] text-fg-subtle">
            <?= $kind ?>
        </span>
        <span class="h-px flex-1 bg-line"></span>
        <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-fg-subtle">
            <?= number_format($totalCount) ?> games
        </span>
    </div>
    <h1 class="font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight leading-tight">
        Best <span class="text-accent"><?= Html::encode($model->name) ?></span> games on Steam
    </h1>

    <p class="mt-4 max-w-3xl text-fg-muted leading-relaxed">
        <?= Html::encode($stats['intro']) ?>
    </p>

    <?php
    $statChips = array_filter([
        $stats['yearText']  ?: null,
        $stats['avgRating'] !== null ? $stats['avgRating'] . '% positive' : null,
        $stats['priceText'] ?: null,
        $stats['free'] > 0 && $stats['priceText'] !== 'Free to play' ? number_format($stats['free']) . ' free' : null,
    ]);
    ?>
    <?php if ($statChips): ?>
        <div class="mt-5 flex flex-wrap items-center gap-2">
            <?php foreach ($statChips as $chip): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-surface ring-1 ring-line px-3 py-1 text-xs font-medium text-fg-muted">
                    <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-accent/60"></span>
                    <?= Html::encode($chip) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($stats['related'])): ?>
        <div class="mt-6">
            <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle"><?= Html::encode($stats['relatedLabel']) ?></span>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <?php foreach ($stats['related'] as $rel): ?>
                    <a href="<?= Url::to([$rel['url']]) ?>"
                       class="group inline-flex items-center gap-1.5 rounded-full bg-canvas ring-1 ring-line px-3 py-1 text-xs font-medium text-fg-muted hover:ring-line-strong hover:text-fg transition">
                        <?= Html::encode($rel['name']) ?>
                        <span class="font-mono text-[10px] tabular-nums text-fg-subtle group-hover:text-fg-muted transition"><?= number_format($rel['count']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</header>

<section class="grid grid-cols-1 lg:grid-cols-12 gap-10">
    <div class="lg:col-span-8">
        <?php if (empty($models)): ?>
            <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center">
                <p class="font-display text-lg font-semibold text-fg">No games yet</p>
                <p class="mt-2 text-sm text-fg-muted">Check back soon — we sync new titles from Steam every day.</p>
            </div>
        <?php else: ?>
            <?= ListView::widget([
                    'id'           => 'gameList',
                    'dataProvider' => $dataProvider,
                    'itemView'     => '_item',
                    'summary'      => false,
                    'layout'       => "<div class=\"grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-7\">{items}</div>\n{pager}",
                    'itemOptions'  => function ($model) {
                        return [
                                'tag'      => 'div',
                                'class'    => 'game-list-item',
                                'data-key' => $model->id,
                        ];
                    },
                    'pager'        => ['class' => \common\widgets\Pager::class],
            ]) ?>
        <?php endif; ?>
    </div>

    <aside class="lg:col-span-4">
        <div class="lg:sticky lg:top-24" id="gameDetailsBox">
            <?php if (isset($models[0])): ?>
                <?= $this->render('_right-bar', ['model' => $models[0], 'gameViewButton' => true]) ?>
            <?php endif; ?>
        </div>
    </aside>
</section>

<?php
$detailsUrl = Url::to(['/game/details']);
$js = <<<JS
(function () {
    var box = document.getElementById('gameDetailsBox');
    var list = document.getElementById('gameList');
    if (!box || !list) return;

    var hoverTimer = null;
    var currentXHR = null;
    var currentKey = null;
    var endpoint = '{$detailsUrl}';

    function setActive(item) {
        list.querySelectorAll('.game-list-item').forEach(function (el) {
            el.setAttribute('data-active', el === item ? 'true' : 'false');
        });
    }

    function loadGame(id, item) {
        if (id === currentKey) return;
        if (currentXHR && typeof currentXHR.abort === 'function') currentXHR.abort();

        box.classList.add('game-details-loading');

        var controller = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        currentXHR = controller;

        fetch(endpoint + '?id=' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined
        })
            .then(function (res) { return res.ok ? res.text() : Promise.reject(); })
            .then(function (html) {
                box.innerHTML = html;
                currentKey = id;
                setActive(item);
            })
            .catch(function () {})
            .finally(function () {
                box.classList.remove('game-details-loading');
            });
    }

    list.addEventListener('mouseover', function (e) {
        var item = e.target.closest('.game-list-item');
        if (!item) return;
        var key = item.getAttribute('data-key');
        if (!key || key === currentKey) return;
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(function () { loadGame(key, item); }, 380);
    });

    list.addEventListener('mouseleave', function () {
        clearTimeout(hoverTimer);
    });

    // Mark first item active on load
    var first = list.querySelector('.game-list-item');
    if (first) {
        first.setAttribute('data-active', 'true');
        currentKey = first.getAttribute('data-key');
    }
})();
JS;
$this->registerJs($js, View::POS_END);
?>
