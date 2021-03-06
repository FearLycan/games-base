<?php

use frontend\modules\game\models\Game;
use yii\helpers\ArrayHelper;
use yii\web\View;
use frontend\components\Helper;

/* @var $this View */
/* @var $model Game */

$this->title = $model->title;

?>

    <!-- Listing Section Begin -->
    <section class="listing-hero set-bg"
             data-setbg="<?= $model->getBackground() ?>">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="listing__hero__option">
                        <div class="listing__hero__icon">
                            <img src="<?= $model->getIcon() ?>"
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
                                             alt="">
                                        <!--<span><i class="fa fa-camera"></i> 170 Image</span>-->
                                    </div>
                                    <div class="listing__details__gallery__slider owl-carousel">

                                        <?php foreach ($model->getScreenshots() as $key => $screenshot): ?>
                                            <img data-imgbigurl="<?= $screenshot->url ?>"
                                                 src="<?= $screenshot->url ?>"
                                                 alt="<?= $model->title ?> #<?= ($key + 1) ?>">
                                        <?php endforeach; ?>

                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <!--
                                                <div class="listing__details__amenities">
                                                    <h4>Amenities</h4>
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-1.png" alt="">
                                                                <h6>Accept Credit Card</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-2.png" alt="">
                                                                <h6>Free Wifi</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-3.png" alt="">
                                                                <h6>Smoking Area</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-4.png" alt="">
                                                                <h6>Free Parking</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-5.png" alt="">
                                                                <h6>Family Friendly</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-6.png" alt="">
                                                                <h6>Coffee</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-7.png" alt="">
                                                                <h6>Massage</h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-3 col-md-3 col-6">
                                                            <div class="listing__details__amenities__item">
                                                                <img src="img/listing/details/amenities/ame-8.png" alt="">
                                                                <h6>Coupons</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> -->

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
                            <ul>
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
                                                <img src="<?= $category->image ?>" class="img-fluid"
                                                     alt="<?= $category->name ?>">
                                            </div>

                                            <?= $category->name ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        </div>
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
        })
    });

JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>