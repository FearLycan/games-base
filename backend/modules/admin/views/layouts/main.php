<?php

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use backend\modules\admin\assets\AdminAsset;
use backend\modules\admin\components\AdminHtml;
use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);
AdminAsset::register($this);

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap');

$active = Yii::$app->controller->id;
$identity = Yii::$app->user->identity;

$groups = [
    'Overview' => [
        ['Dashboard', 'default', 'default/index', 'bi-grid-1x2'],
    ],
    'Catalog' => [
        ['Games', 'game', 'game/index', 'bi-controller'],
        ['Categories', 'category', 'category/index', 'bi-tags'],
        ['Genres', 'genre', 'genre/index', 'bi-collection'],
        ['Tags', 'tag', 'tag/index', 'bi-hash'],
        ['Platforms', 'platform', 'platform/index', 'bi-pc-display'],
        ['Reviews', 'review', 'review/index', 'bi-star'],
    ],
    'Companies' => [
        ['Developers', 'developer', 'developer/index', 'bi-code-slash'],
        ['Publishers', 'publisher', 'publisher/index', 'bi-building'],
    ],
    'Commerce' => [
        ['Stores', 'store', 'store/index', 'bi-shop'],
        ['Offers', 'game-offer', 'game-offer/index', 'bi-cart3'],
        ['Lists', 'game-sale', 'game-sale/index', 'bi-graph-up-arrow'],
    ],
    'System' => [
        ['Users', 'user', 'user/index', 'bi-people'],
        ['IP Addresses', 'ip-address', 'ip-address/index', 'bi-shield-lock'],
    ],
];

$pendingOffers = AdminHtml::pendingOffersCount();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title ? $this->title . ' · Admin' : 'Admin') ?></title>
    <?php $this->head() ?>
</head>
<body class="admin-body">
<?php $this->beginBody() ?>

<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-brand">
            <a href="<?= Url::to(['/admin']) ?>" class="admin-brand__mark">
                <span class="admin-brand__glyph">◆</span>
                <span class="admin-brand__name"><?= Html::encode(Yii::$app->name) ?></span>
            </a>
            <button type="button" class="admin-sidebar__close js-sidebar-toggle" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="admin-nav">
            <?php foreach ($groups as $label => $items): ?>
                <div class="admin-nav__group">
                    <span class="admin-nav__heading"><?= Html::encode($label) ?></span>
                    <?php foreach ($items as [$title, $id, $route, $icon]): ?>
                        <?php
                        $badge = '';
                        if ($id === 'game-offer' && $pendingOffers > 0) {
                            $badge = Html::tag('span', (string)$pendingOffers, ['class' => 'admin-nav__badge']);
                        }
                        ?>
                        <a href="<?= Url::to(['/admin/' . $route]) ?>"
                           class="admin-nav__link<?= $active === $id ? ' is-active' : '' ?>">
                            <i class="bi <?= $icon ?>"></i>
                            <span><?= Html::encode($title) ?></span>
                            <?= $badge ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__foot">
            <div class="admin-user">
                <span class="admin-user__avatar"><?= Html::encode(strtoupper(substr((string)($identity->username ?? '?'), 0, 1))) ?></span>
                <span class="admin-user__name"><?= Html::encode($identity->username ?? '') ?></span>
            </div>
            <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'admin-logout']) ?>
            <?= Html::submitButton('<i class="bi bi-box-arrow-right"></i> Sign out', ['class' => 'admin-logout__btn']) ?>
            <?= Html::endForm() ?>
        </div>
    </aside>

    <div class="admin-backdrop js-sidebar-toggle"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-topbar__burger js-sidebar-toggle" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <?= Breadcrumbs::widget([
                'options'                => ['class' => 'admin-breadcrumb'],
                'homeLink'               => ['label' => 'Admin', 'url' => Url::to(['/admin'])],
                'links'                  => $this->params['breadcrumbs'] ?? [],
                'itemTemplate'           => "<li class=\"admin-breadcrumb__item\">{link}</li>\n",
                'activeItemTemplate'     => "<li class=\"admin-breadcrumb__item is-active\">{link}</li>\n",
            ]) ?>
        </header>

        <main class="admin-content">
            <?= Alert::widget([
                'alertTypes' => [
                    'error'   => 'alert alert-danger alert-dismissible fade show',
                    'danger'  => 'alert alert-danger alert-dismissible fade show',
                    'success' => 'alert alert-success alert-dismissible fade show',
                    'info'    => 'alert alert-info alert-dismissible fade show',
                    'warning' => 'alert alert-warning alert-dismissible fade show',
                ],
            ]) ?>
            <?= $content ?>
        </main>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
