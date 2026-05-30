<?php

namespace backend\modules\admin\assets;

use backend\assets\AppAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for the admin module: ships the AJAX glue for inline status
 * toggling and row deletion on top of the shared backend (Bootstrap 5) bundle.
 */
class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/admin.css',
    ];
    public $js = [
        'js/admin.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}
