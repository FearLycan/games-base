<?php

/* @var $this \yii\web\View */

/* @var $content string */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap4\Breadcrumbs;
use yii\bootstrap4\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>" class="h-100">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/android-chrome-512x512.png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
        <meta name="msapplication-TileColor" content="#da532c">
        <meta name="theme-color" content="#ffffff">

        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Organization",
                "name": "Gamentator",
                "description": "<?= Yii::$app->params['meta-description'] ?>",
                "url": "https://gamentator.com",
                "logo": "<?= Yii::$app->params['og_image']['content'] ?>"
            }
        </script>

        <!-- Primary Meta Tags -->
        <title><?= Html::encode($this->title) ?></title>
        <meta name="title" content="<?= Html::encode($this->title) ?>"/>
        <meta name="description" content="<?= Html::encode(Yii::$app->params['meta-description']) ?>"/>

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website"/>
        <meta property="og:url" content="<?= Yii::$app->request->absoluteUrl ?>"/>
        <meta property="og:title" content="<?= Html::encode($this->title) ?>"/>
        <meta property="og:description" content="<?= Html::encode(Yii::$app->params['meta-description']) ?>"/>
        <meta property="og:image" content="<?= Yii::$app->params['og_image']['content'] ?>"/>

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image"/>
        <meta property="twitter:url" content="<?= Yii::$app->request->absoluteUrl ?>"/>
        <meta property="twitter:title" content="<?= Html::encode($this->title) ?>"/>
        <meta property="twitter:description" content="<?= Html::encode(Yii::$app->params['meta-description']) ?>"/>
        <meta property="twitter:image" content="<?= Yii::$app->params['og_image']['content'] ?>"/>

        <link rel="canonical" href="<?= Yii::$app->request->absoluteUrl ?>"/>

        <?php $this->registerCsrfMetaTags() ?>

        <!-- Google Font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap"
              rel="stylesheet">

        <?php $this->head() ?>
    </head>
    <body>
    <?php $this->beginBody() ?>

    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>

    <!-- Header Section Begin -->
    <header class="header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-md-3">
                    <div class="header__logo">
                        <a href="<?= Yii::$app->homeUrl ?>">
                            <img src="/img/logo.png" alt="<?= Yii::$app->name ?>" loading="lazy">
                        </a>
                    </div>
                </div>
                <div class="col-lg-9 col-md-9">
                    <div class="header__nav">
                        <nav class="header__menu mobile-menu">
                            <ul>
                                <!--<li class="active"><a href="./index.html">Home</a></li>-->
                                <!--<li><a href="./listing.html">Listing</a></li>
                                <li><a href="#">Categories</a></li>
                                <li><a href="#">Pages</a>
                                    <ul class="dropdown">
                                        <li><a href="./about.html">About</a></li>
                                        <li><a href="./listing-details.html">Listing Details</a></li>
                                        <li><a href="./blog-details.html">Blog Details</a></li>
                                        <li><a href="./contact.html">Contact</a></li>
                                    </ul>
                                </li>
                                <li><a href="./blog.html">Blog</a></li>
                                <li><a href="#">Shop</a></li>-->
                            </ul>
                        </nav>
                        <!--<div class="header__menu__right">
                            <a href="#" class="primary-btn"><i class="fa fa-plus"></i>Add Listing</a>
                            <a href="#" class="login-btn"><i class="fa fa-user"></i></a>
                        </div>-->
                    </div>
                </div>
            </div>
            <div id="mobile-menu-wrap"></div>
        </div>
    </header>
    <!-- Header Section End -->

    <?= $content ?>

    <!-- Footer Section Begin -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="footer__copyright">
                        <div class="footer__copyright__text">
                            <p>
                                Copyrights © <?= date('Y') ?> by <strong><?= Yii::$app->name ?></strong>.
                                All rights reserved.
                            </p>
                        </div>
                        <div class="footer__copyright__links">
                            <!--<a href="#">Terms</a>
                            <a href="#">Privacy Policy</a>
                            <a href="#">Cookie Policy</a>-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- Footer Section End -->

    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage();
