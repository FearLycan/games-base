<?php

use yii\helpers\Url;
use common\models\Game;

/* @var $game Game */

?>


<a href="<?= Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]) ?>"
   class="blog__sidebar__recent__item">
    <div class="blog__sidebar__recent__item__pic">
        <img loading="lazy" class="img-fluid" style="height: 60px; width: 104px;"
             alt="<?= $game->title ?>"
             src="<?= $game->getHeader() ?>">
    </div>
    <div class="blog__sidebar__recent__item__text">
        <span><i class="fa fa-tags"></i> <?= $game->getMainGenre() ?></span>
        <h6><?= $game->title ?></h6>
        <p>
            <!--  <i class="fa fa-clock-o"></i> <?= (new DateTime($game->release_date))->format('d M, Y') ?> -->
        </p>
    </div>
</a>
