<?php

use common\models\Category;
use common\models\Genre;
use common\models\Tag;
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

$this->title = "Best {$model->name} games on Steam" . " - " . Yii::$app->params['meta-title'];
$this->params['breadcrumbs'][] = ['label' => 'Games', 'url' => ['/game/game/index']];
if ($model instanceof Tag) {
    $this->params['breadcrumbs'][] = 'Tag';
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
    <?php if ($model->description ?? null): ?>
        <p class="mt-4 max-w-3xl text-fg-muted leading-relaxed">
            <?= Html::encode($model->description) ?>
        </p>
    <?php else: ?>
        <p class="mt-4 max-w-3xl text-fg-muted">
            Hand-picked <?= Html::encode(strtolower($model->name)) ?> titles. Hover any card to preview details on the right.
        </p>
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
