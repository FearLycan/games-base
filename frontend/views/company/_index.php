<?php

use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;

/* @var $this View */
/* @var $dataProvider ActiveDataProvider */
/* @var $sort string */
/* @var $country string|null */
/* @var $countries string[] */
/* @var $kind string */
/* @var $kindPlural string */
/* @var $pageTitle string */
/* @var $pageIntro string */

$this->title = $pageTitle . ' · ' . Yii::$app->params['meta-title'];
$this->params['breadcrumbs'][] = $pageTitle;
$this->registerCssFile('@web/css/company.css');

$baseUrl = ['/' . $kind . '/' . $kind . '/index'];
$total = $dataProvider->getTotalCount();

$sortOptions = [
    'games'  => 'Most games',
    'name'   => 'Alphabetical',
    'newest' => 'Recently added',
];
?>

<header class="mb-10">
    <div class="flex items-center gap-3 mb-4">
        <span class="font-mono text-[11px] uppercase tracking-[0.22em] text-fg-subtle">
            <?= Html::encode(ucfirst($kindPlural)) ?>
        </span>
        <span class="h-px flex-1 bg-line"></span>
        <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-fg-subtle">
            <?= number_format($total) ?> total
        </span>
    </div>
    <h1 class="font-display text-3xl sm:text-4xl font-bold text-fg tracking-tight leading-tight">
        <?= Html::encode($pageTitle) ?>
    </h1>
    <p class="mt-4 max-w-3xl text-fg-muted leading-relaxed">
        <?= Html::encode($pageIntro) ?>
    </p>
</header>

<section class="mb-8 flex flex-wrap items-center gap-3">
    <div class="flex flex-wrap items-center gap-1.5">
        <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle mr-2">Sort</span>
        <?php foreach ($sortOptions as $key => $label): ?>
            <a href="<?= Url::to(array_merge($baseUrl, ['sort' => $key, 'country' => $country])) ?>"
               class="sort-chip"
               data-active="<?= $sort === $key ? 'true' : 'false' ?>">
                <?= Html::encode($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($countries)): ?>
        <div class="ml-auto flex items-center gap-2">
            <label for="country-filter" class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle">Country</label>
            <select id="country-filter"
                    class="rounded-lg border border-line bg-canvas px-3 py-1.5 text-sm text-fg focus:outline-none focus:ring-2 focus:ring-accent/30"
                    onchange="window.location.href = this.value">
                <option value="<?= Url::to(array_merge($baseUrl, ['sort' => $sort])) ?>">All countries</option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= Url::to(array_merge($baseUrl, ['sort' => $sort, 'country' => $c])) ?>"
                            <?= $country === $c ? 'selected' : '' ?>>
                        <?= Html::encode($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
</section>

<?php if ($total === 0): ?>
    <div class="rounded-2xl border border-dashed border-line bg-surface/40 px-8 py-16 text-center">
        <p class="font-display text-lg font-semibold text-fg">No <?= Html::encode($kindPlural) ?> match these filters</p>
        <p class="mt-2 text-sm text-fg-muted">Try clearing the country filter or pick another sort.</p>
    </div>
<?php else: ?>
    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView'     => function ($item) use ($kind) {
            return $this->render('_card', ['item' => $item, 'kind' => $kind]);
        },
        'summary'      => false,
        'layout'       => "<div class=\"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4\">{items}</div>\n{pager}",
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
