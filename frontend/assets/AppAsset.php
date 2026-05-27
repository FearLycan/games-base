<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'libs/select2/select2.min.css',
        'css/site.css',
    ];
    public $js = [
        'libs/select2/select2.full.min.js',
        'js/main.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'common\assets\TailwindAsset',
    ];
}
