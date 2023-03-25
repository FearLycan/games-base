<?php

use frontend\components\Helper;
use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Game */
?>

<div class="listing__sidebar game-details sticky-top" data-key="<?= $model->id ?>">

    <div class="listing__sidebar__working__hours mb-4">
        <h4><?= $model->title ?></h4>
        <img class="img-fluid" loading="lazy" alt="<?= $model->title ?>"
             src="<?= $model->getHeader() ?>">
        <ul style="margin-top: 16px;">
            <li>
                Genre
                <span>
                        <?= Helper::getLinkList($model->genres, 'name', 'slug', '/games/') ?>
                    </span>
            </li>
            <li>
                Developer
                <span>
                        <?= Helper::getLinkList($model->developers, 'name', 'slug', '/developer/') ?>
                    </span>
            </li>
            <li>
                Publisher
                <span>
                        <?= Helper::getLinkList($model->publishers, 'name', 'slug', '/publisher/') ?>
                    </span>
            </li>
            <?php if ($model->release_date): ?>
                <li>
                    Release Date
                    <span>
                            <?= (new DateTime($model->release_date))->format('d M, Y') ?>
                        </span>
                </li>
            <?php endif; ?>
            <li>
                Platforms
                <span>
                        <?php foreach ($model->getAvailablePlatforms() as $platform) : ?>
                            <a href="#" class="platform-icon"><?= $platform->getIcon() ?></a>
                        <?php endforeach; ?>
                    </span>
            </li>
        </ul>
        <?php if ($model->review->total_reviews): ?>
            <hr>
            <h5>Steam reviews</h5>
            <ul style="margin-top: 16px;">
                <li>
                    <?= Html::encode($model->review->description) ?>
                    <small class="text-muted">(<?= number_format($model->review->total_reviews) ?>)</small>
                </li>
                <li>
                    <div class="progress">
                        <div class="progress-bar bg-success" data-toggle="tooltip"
                             data-placement="top" title="<?= $model->review->getShortPositiveDescription() ?>"
                             style="width: <?= $model->review->getPercentsOfPositive() . '%' ?>"
                             aria-valuenow="<?= $model->review->getPercentsOfPositive() ?>" aria-valuemin="0"
                             aria-valuemax="100">
                            <?= $model->review->getPercentsOfPositive() . '%' ?>
                        </div>
                        <div class="progress-bar bg-danger" data-toggle="tooltip"
                             data-placement="top" title="<?= $model->review->getShortNegativeDescription() ?>"
                             style="width: <?= $model->review->getPercentsOfNegative() . '%' ?>"
                             aria-valuenow="<?= $model->review->getPercentsOfPositive() ?>"
                             aria-valuemin="0"
                             aria-valuemax="100">
                            <?= $model->review->getPercentsOfNegative() . '%' ?>
                        </div>
                    </div>
                </li>
            </ul>
        <?php endif; ?>

        <?php if ($model->metacritic): ?>
            <hr>
            <ul style="margin-top: 16px;">
                <li>
                    <div class="row">
                        <?php if ($model->metacritic->score): ?>
                            <div class="col-5 col-md-5 text-center">
                                Metacritic
                                <div role="progressbar" aria-valuenow="<?= $model->metacritic->score ?>"
                                     aria-valuemin="0" aria-valuemax="100"
                                     style="--value:<?= $model->metacritic->score ?>; --fg:<?= $model->metacritic->getScoreColor($model->metacritic->score) ?>"></div>
                            </div>
                        <?php endif; ?>

                        <?php if ($model->metacritic->user_score): ?>
                            <div class="col-5 offset-2 col-md-5 offset-md-2 text-center">
                                User score
                                <div role="progressbar" aria-valuenow="<?= $model->metacritic->user_score ?>"
                                     aria-valuemin="0" aria-valuemax="100"
                                     style="--value:<?= $model->metacritic->user_score ?>; --fg:<?= $model->metacritic->getScoreColor($model->metacritic->user_score) ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        <?php endif; ?>
    </div>
    <div class="listing__sidebar__working__hours mb-4">
        <ul class="list-group category-list">
            <?php foreach ($model->categories as $category): ?>
                <li class="list-group-item category-item">
                    <a href="<?= Url::to(['/games/' . $category->slug]) ?>" class="category-link">
                        <div class="category-icon">
                            <img src="<?= $category->getImage() ?>" loading="lazy" class="img-fluid"
                                 alt="<?= $category->name ?>">
                        </div>

                        <?= $category->name ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($model->steam_deck): ?>
        <div class="blog__sidebar__recent mb-4">
            <h5>Steam Deck Compatibility</h5>

            <?php if ((int)$model->steam_deck === Game::STEAM_DECK_VERIFIED): ?>
                <span class="btn btn-success btn-steam-deck">
                                        <span class="btn-label">
                                            <i class="fa fa-check-circle-o" aria-hidden="true"></i>
                                        </span>
                                        <?= $model->getSteamDecksStatusName() ?>
                                    </span>
            <?php endif; ?>

            <?php if ((int)$model->steam_deck === Game::STEAM_DECK_UNSUPPORTED): ?>
                <span class="btn btn-danger btn-steam-deck">
                                        <span class="btn-label">
                                            <i class="fa fa-ban" aria-hidden="true"></i>
                                        </span>
                                        <?= $model->getSteamDecksStatusName() ?>
                                    </span>
            <?php endif; ?>

            <?php if ((int)$model->steam_deck === Game::STEAM_DECK_PLAYABLE): ?>
                <span class="btn btn-warning btn-steam-deck">
                                        <span class="btn-label">
                                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                                        </span>
                                        <?= $model->getSteamDecksStatusName() ?>
                                    </span>
            <?php endif; ?>


        </div>
    <?php endif; ?>

    <?php if ($model->tags): ?>
        <div class="blog__sidebar__tags mb-4">
            <h5>Popular Tag</h5>
            <?php foreach ($model->gameTags as $key => $gameTag): ?>
                <a href="#" data-hide="<?= $key >= 6 ? 1 : 0 ?>"><?= $gameTag->tag->name ?></a>
            <?php endforeach; ?>
            <div class="row">
                <p class="text-right" id="showMore" style="width: 100%;">
                    <i class="fa fa-plus" aria-hidden="true"></i> Show more
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($gameViewButton) && $gameViewButton): ?>
        <div class="blog__sidebar__recent text-center mb-4">
            <a href="<?= Url::to(['game/view', 'id' => $model->steam_appid, 'slug' => $model->slug]) ?>"
               class="btn btn-secondary">
                Go to Game page
            </a>
        </div>
    <?php endif; ?>

</div>