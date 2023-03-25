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
        'css/owl.carousel.min.css',
        'css/slicknav.min.css',

        'lib/easy-autocomplete/easy-autocomplete.min.css',
        'lib/easy-autocomplete/easy-autocomplete.themes.min.css',

        'css/circular-progress-bar.css',
        'css/style.css',
        'css/site.css',
    ];
    public $js = [
        'js/bootstrap.min.js',
        'js/jquery.nice-select.min.js',
        'js/jquery.nicescroll.min.js',
        'js/jquery.barfiller.js',
        'js/jquery.magnific-popup.min.js',
        'js/jquery.slicknav.js',
        'js/owl.carousel.min.js',
        'js/jquery-read-more.min.js',
        'lib/easy-autocomplete/jquery.easy-autocomplete.min.js',
        'js/main.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
    ];
}
