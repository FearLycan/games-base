<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/font-awesome.min.css',
        'css/elegant-icons.css',
        'css/flaticon.css',
        'css/nice-select.css',
        'css/barfiller.css',
        'css/magnific-popup.css',
        'css/jquery-ui.min.css',
        'css/owl.carousel.min.css',
        'css/slicknav.min.css',
        'css/style.css',
        //'css/site.css',
    ];
    public $js = [
        'js/jquery.nice-select.min.js',
        'js/jquery-ui.min.js',
        'js/jquery.nicescroll.min.js',
        'js/jquery.barfiller.js',
        'js/jquery.magnific-popup.min.js',
        'js/jquery.slicknav.js',
        'js/owl.carousel.min.js',
        'js/main.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
    ];
}
