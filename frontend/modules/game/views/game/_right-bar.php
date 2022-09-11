<?php

use frontend\components\Helper;
use frontend\modules\game\models\Game;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Game */

?>

<div class="col-lg-4">
    <div class="listing__sidebar">
        <div class="listing__sidebar__working__hours mb-4">
            <h4><?= $model->title ?></h4>
            <img class="img-fluid" loading="lazy" alt="<?= $model->title ?>"
                 src="<?= $model->getHeader() ?>">
            <ul style="margin-top: 16px;">
                <li>
                    Genre <span>
                                        <?= Helper::getLinkList($model->genres, 'name', 'slug', '/games/') ?>
                                    </span>
                </li>
                <li>
                    Developer <span>
                                        <?= Helper::getLinkList($model->developers, 'name', 'slug', '/developer/') ?>
                                    </span>
                </li>
                <li>
                    Publisher <span>
                                        <?= Helper::getLinkList($model->publishers, 'name', 'slug', '/publisher/') ?>
                                    </span>
                </li>
                <li>
                    Release Date <span>
                                    <?= (new DateTime($model->release_date))->format('d M, Y') ?></span>
                </li>
                <li>
                    Platforms <span>
                        <?php foreach ($model->platforms as $platform) : ?>
                            <a href="#" class="platform-icon"><?= $platform->getIcon() ?></a>
                        <?php endforeach; ?>
                    </span>
                </li>
                <hr>
                <h5>Steam reviews</h5>
                <ul style="margin-top: 16px;">
                    <li>Mostly positive</li>
                    <li>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" data-toggle="tooltip"
                                 data-placement="top" title="<?= $model->review->getShortPositiveDescription() ?>"
                                 style="width: <?= $model->review->getPercentsOfPositive() . '%' ?>"
                                 aria-valuenow="<?= $model->review->getPercentsOfPositive() ?>" aria-valuemin="0"
                                 aria-valuemax="100">
                                <?= $model->review->getPercentsOfPositive() . '%' ?>
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" data-toggle="tooltip"
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

            </ul>
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
            <div class="blog__sidebar__recent">
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
            <div class="blog__sidebar__tags">
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


    </div>
</div>