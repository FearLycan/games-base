<?php

use common\models\Game;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model Game */

?>

<div class="blog__item">
    <div class="blog__item__pic set-bg" data-setbg="<?= $model->getHeader() ?>"></div>
    <div class="blog__item__text">
        <ul class="blog__item__tags">
            <li><i class="fa fa-tags"></i> Travel</li>
            <li>Videos</li>
        </ul>
        <h5>
            <a href="<?= Url::to(['/game/game/view', 'id' => $model->steam_appid, 'slug' => $model->slug]) ?>"><?= $model->title ?></a>
        </h5>
        <div class="listing__item__text__rating">
            <div class="listing__item__rating__star">
            </div>

            <?php if ($model->steam_price_initial): ?>
                <h6><?= $model->getInitialPrice() ?></h6>
            <?php endif; ?>
        </div>
    </div>
</div>
