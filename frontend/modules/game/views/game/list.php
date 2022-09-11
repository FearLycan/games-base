<?php

use common\models\Category;
use common\models\Genre;
use yii\helpers\Html;
use yii\grid\GridView;
use frontend\modules\game\models\searches\GameSearch;
use yii\helpers\Url;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $searchModel GameSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model Genre|Category */

$this->title = "Best {$model->name} games on Steam";
$this->params['breadcrumbs'][] = 'Games';
$this->params['breadcrumbs'][] = $model->name;
?>

<div class="breadcrumb-area set-bg" data-setbg="/img/background-default.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text">
                    <h2><?= $this->title ?></h2>
                    <div class="breadcrumb__option">
                        <a href="<?= Url::to(['/game/index']) ?>">
                            <i class="fa fa-home"></i> Games
                        </a>
                        <span><?= $model->name ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="blog-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'itemOptions' => ['class' => 'col-lg-4 col-md-4'],
                    'itemView' => '_item',
                    'options' => ['class' => 'row'],
                    'summary' => false,
                ]); ?>
            </div>
        </div>
    </div>
</section>

