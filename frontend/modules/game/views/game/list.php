<?php

use common\models\Category;
use common\models\Genre;
use frontend\components\Helper;
use frontend\components\Pager;
use frontend\modules\game\models\searches\GameSearch;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ListView;
use yii\data\ActiveDataProvider;

/* @var $this View */
/* @var $searchModel GameSearch */
/* @var $dataProvider ActiveDataProvider */
/* @var $model Genre|Category */

$this->title = "Best {$model->name} games on Steam";
$this->params['breadcrumbs'][] = 'Games';
$this->params['breadcrumbs'][] = $model->name;

$models = $dataProvider->getModels();
?>

<div class="breadcrumb-area set-bg" data-setbg="/img/background-default.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb__text">
                    <h2><?= $this->title ?></h2>
                    <div class="breadcrumb__option">
                        <a href="<?= Url::to(['/games']) ?>">
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
            <div class="col-12 col-sm-12 col-md-8 col-lg-8">
                <?= ListView::widget([
                    'id'           => 'gameList',
                    'pager'        => Helper::pager(),
                    'dataProvider' => $dataProvider,
                    'itemOptions'  => ['class' => 'col-lg-6 col-md-6 game'],
                    'itemView'     => '_item',
                    'options'      => ['class' => 'row'],
                    'summary'      => false,
                ]) ?>
            </div>

            <div class="col-12 col-sm-12 col-md-4 col-lg-4" id="gameDetailsBox">
                <?php if (isset($models[0])): ?>
                    <?= $this->render('_right-bar', ['model' => $models[0], 'gameViewButton' => false]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php

$js = <<<JS

var timer;

$(document).ready(function () {

    $("div.game").hover(function () {
        //mouseenter
        let key = $(this).data('key');
        let details = $(document).find('div.game-details[data-key="' + key + '"]');
        
        if (details.length === 0) {
            clearTimeout(timer);
            timer = setTimeout(function() { loadGame(key); }, 550);
        }

    }, function () {
        //mouseleave 
    });


    function loadGame(id) {
        let box = $('#gameDetailsBox');
        $.ajax({
            url: '/game/details',
            type: 'GET',
            data: {'id': id},
            success: function (data) {
                $(box).html(data);
            },
            beforeSend: function () {
                $(box).addClass('game-details-loading');
            },
            complete: function () {
                $(box).removeClass('game-details-loading');
            }
        });
    }
    
});
JS;

$this->registerJs($js, View::POS_READY);
?>

