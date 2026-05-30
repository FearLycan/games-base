<?php

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use common\models\User;
use common\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$isAdmin = !Yii::$app->user->isGuest
    && Yii::$app->user->identity instanceof User
    && (int)Yii::$app->user->identity->role === User::ROLE_ADMIN;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl'   => Yii::$app->homeUrl,
        'options'    => [
            'class' => 'navbar navbar-expand-md navbar-dark bg-dark fixed-top',
        ],
    ]);

    $menuItems = [
        ['label' => 'Home', 'url' => ['/site/index']],
    ];

    if ($isAdmin) {
        $menuItems[] = [
            'label' => 'Admin',
            'items' => [
                ['label' => 'Dashboard', 'url' => ['/admin']],
                '<hr class="dropdown-divider">',
                ['label' => 'Games', 'url' => ['/admin/game/index']],
                ['label' => 'Stores', 'url' => ['/admin/store/index']],
                ['label' => 'Categories', 'url' => ['/admin/category/index']],
                ['label' => 'Genres', 'url' => ['/admin/genre/index']],
                ['label' => 'Tags', 'url' => ['/admin/tag/index']],
                ['label' => 'Developers', 'url' => ['/admin/developer/index']],
                ['label' => 'Publishers', 'url' => ['/admin/publisher/index']],
                ['label' => 'Platforms', 'url' => ['/admin/platform/index']],
                ['label' => 'Reviews', 'url' => ['/admin/review/index']],
                ['label' => 'Users', 'url' => ['/admin/user/index']],
            ],
        ];
    }

    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav me-auto'],
        'items'   => $menuItems,
    ]);

    if (!Yii::$app->user->isGuest) {
        echo Html::beginForm(['/site/logout'], 'post', ['class' => 'd-flex'])
            . Html::submitButton(
                'Logout (' . Html::encode(Yii::$app->user->identity->username) . ')',
                ['class' => 'btn btn-outline-light']
            )
            . Html::endForm();
    }

    NavBar::end();
    ?>
</header>

<main role="main" class="flex-shrink-0">
    <div class="container-fluid px-4" style="margin-top: 70px;">
        <?= Breadcrumbs::widget([
            'links' => $this->params['breadcrumbs'] ?? [],
        ]) ?>
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
    </div>
</main>

<footer class="footer mt-auto py-3 text-muted">
    <div class="container-fluid px-4">
        <p class="float-start">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>
        <p class="float-end"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
