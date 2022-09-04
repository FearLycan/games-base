<?php

use frontend\components\Helper;
use frontend\modules\game\models\Game;
use yii\web\View;

/* @var $this View */
/* @var $model Game */

$this->title = $model->title;

?>

    <!-- Listing Section Begin -->
    <section class="listing-hero set-bg" data-setbg="<?= $model->getBackground() ?>">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="listing__hero__option">
                        <div class="listing__hero__icon">
                            <img src="<?= $model->getIcon() ?>" loading="lazy"
                                 style="height:132px;" class="rounded-circle" alt="<?= $model->title ?>">
                        </div>
                        <div class="listing__hero__text">
                            <h2><?= $model->title ?></h2>
                            <div class="listing__hero__widget" style="margin-top: -5px;">
                                <div class="rating">
                                    <div class="rating-upper" style="width: <?= $model->review->getPercents() ?>%">
                                        <span>★</span>
                                        <span>★</span>
                                        <span>★</span>
                                        <span>★</span>
                                        <span>★</span>
                                    </div>
                                    <div class="rating-lower">
                                        <span>★</span>
                                        <span>★</span>
                                        <span>★</span>
                                        <span>★</span>
                                        <span>★</span>
                                    </div>
                                </div>
                                <div>
                                    <?= $model->review->total_reviews ?>
                                    Review<?= $model->review->total_reviews > 1 ? 's' : '' ?>
                                </div>

                            </div>

                            <p>
                                <?= $model->short_description ?>
                            </p>

                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="listing__hero__btns">
                        <a href="<?= $model->getSteamUrl() ?>" rel="nofollow noopener external" target="_blank"
                           class="primary-btn share-btn">
                            <i class="fa fa-steam-square" aria-hidden="true"></i> View on Steam</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Listing Section End -->

    <!-- Listing Details Section Begin -->
    <section class="listing-details spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="listing__details__text">
                        <div class="listing__details__about">
                            <h4>ABOUT THIS GAME</h4>

                            <div class="read-more" data-controller="#readMore">
                                <?= $model->detailed_description ?>
                            </div>

                            <a id="readMore">Read more</a>

                        </div>

                        <?php if (count($model->getScreenshots())): ?>
                            <div class="listing__details__gallery">
                                <h4>Gallery</h4>
                                <div class="listing__details__gallery__pic">
                                    <div class="listing__details__gallery__item">
                                        <img class="listing__details__gallery__item__large"
                                             src="<?= $model->getScreenshots()[0]->url ?>"
                                             alt="<?= $model->title ?> #0" loading="lazy">
                                    </div>
                                    <div class="listing__details__gallery__slider owl-carousel">
                                        <?php foreach ($model->getScreenshots() as $key => $screenshot): ?>
                                            <img data-imgbigurl="<?= $screenshot->url ?>"
                                                 src="<?= $screenshot->url ?>" loading="lazy"
                                                 alt="<?= $model->title ?> #<?= ($key + 1) ?>">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="listing__details__gallery">

                        <div class="listing__details__about">
                            <h4>SYSTEM REQUIREMENTS</h4>

                            <div class="most__search__tab most__search__tab-requirements">

                                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">

                                    <?php foreach ($model->getAvailablePlatforms() as $key => $platform): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?= $key == 0 ? 'active' : '' ?>"
                                               id="pills-<?= $platform->slug ?>-tab" data-toggle="pill"
                                               href="#pills-<?= $platform->slug ?>" role="tab"
                                               aria-controls="pills-<?= $platform->slug ?>" aria-selected="false">
                                                <?= ucwords($platform->name) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="tab-content" id="pills-tabContent">

                                    <?php foreach ($model->getAvailablePlatforms() as $key => $platform): ?>
                                        <div class="tab-pane fade <?= $key == 0 ? 'show active' : '' ?>"
                                             id="pills-<?= $platform->slug ?>" role="tabpanel"
                                             aria-labelledby="pills-<?= $platform->slug ?>-tab">

                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <?= $platform->requirements_minimum ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <?= $platform->requirements_recommended ?>
                                                </div>
                                            </div>


                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            </div>
                        </div>


                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="listing__sidebar">
                        <div class="listing__sidebar__working__hours mb-4">
                            <h4><?= $model->title ?></h4>
                            <img class="img-fluid" loading="lazy" alt="<?= $model->title ?>"
                                 src="<?= $model->getHeader() ?>">
                            <ul style="margin-top: 16px;">
                                <li>
                                    Genre <span>
                                        <?= Helper::getLinkList($model->genres, 'name', 'slug', '/genre/') ?>
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
                                    <?= (new \DateTime($model->release_date))->format('d M, Y') ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="listing__sidebar__working__hours mb-4">
                            <ul class="list-group category-list">
                                <?php foreach ($model->categories as $category): ?>
                                    <li class="list-group-item category-item">
                                        <a href="#" class="category-link">
                                            <div class="category-icon">
                                                <img src="<?= $category->image ?>" loading="lazy" class="img-fluid"
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
            </div>
        </div>
    </section>
    <!-- Listing Details Section End -->

<?php

$js = <<<JS
   
    $(function(){
        $("div.read-more").readMore({
            lines: 5,
            readMoreLabel:'<i class="fa fa-plus" aria-hidden="true"></i> Read more',
            readLessLabel:'<i class="fa fa-minus" aria-hidden="true"></i> Read less',
        });
        
        
        $('a[data-hide="1"]').hide();
        
        $("p#showMore").on( "click", function() {
            let p = this;
            $('a[data-hide="1"]').each(function (index){
                if($(this).is(":visible")){
                    //$(this).hide( "fast", arguments.callee );
                    $(this).fadeOut("fast");
                    $(p).html('<i class="fa fa-plus" aria-hidden="true"></i> Show more')
                }else{
                    //$(this).show( "fast", arguments.callee );
                    $(this).fadeIn("fast");
                    $(p).html('<i class="fa fa-minus" aria-hidden="true"></i> Show less')
                }
            })
        });
        
    });

JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>