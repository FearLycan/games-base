<?php

use frontend\components\Helper;
use frontend\modules\game\models\searches\GameSearch;
use yii\helpers\Url;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $searchModel GameSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = "Bestsellers games on Steam" . " - " . Yii::$app->params['meta-title'];
$this->params['breadcrumbs'][] = 'Games';
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
                        <span>Bestsellers</span>
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
                    'pager'        => Helper::pager(),
                    'itemOptions'  => ['class' => 'col-lg-4 col-md-4'],
                    'itemView'     => 'game/_item',
                    'options'      => ['class' => 'row'],
                    'summary'      => false,
                ]) ?>
            </div>
        </div>
    </div>
</section>

