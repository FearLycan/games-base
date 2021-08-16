<?php

use frontend\modules\game\models\Game;
use yii\helpers\ArrayHelper;
use yii\web\View;

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
                            <h2>Days Gone</h2>
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
                        <a href="#" class="primary-btn share-btn"><i class="fa fa-mail-reply"></i> Share</a>
                        <a href="#" class="primary-btn"><i class="fa fa-bookmark"></i> Bookmark</a>
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

                            <p class="read-more" data-controller="#readMore">
                                <?= $model->detailed_description ?>
                            </p>

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
                                        <!--                                    <span><i class="fa fa-camera"></i> 170 Image</span>-->
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
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="listing__sidebar">
                        <div class="listing__sidebar__working__hours">
                            <h4><?= $model->title ?></h4>
                            <ul>
                                <li>Release Date: <span>
                                    <?= (new \DateTime($model->release_date))->format('d M, Y') ?></span>
                                </li>
                                <li>Developer: <span>
                                    <?= implode(', ', ArrayHelper::map($model->developers, 'id', 'name')) ?></span>
                                </li>
                                <li>Publisher: <span>
                                    <?= implode(', ', ArrayHelper::map($model->publishers, 'id', 'name')) ?></span>
                                </li>
                            </ul>
                        </div>
                        <div class="listing__sidebar__contact">
                            <div class="listing__sidebar__contact__text">
                                <h4>Contacts</h4>
                                <ul>
                                    <li><span class="icon_pin_alt"></span> 236 Littleton St. New Philadelphia, Ohio,
                                        United States
                                    </li>
                                    <li><span class="icon_phone"></span> (+12) 345-678-910</li>
                                    <li><span class="icon_mail_alt"></span> Info.colorlib@gmail.com</li>
                                    <li><span class="icon_globe-2"></span> https://colorlib.com</li>
                                </ul>
                                <div class="listing__sidebar__contact__social">
                                    <a href="#"><i class="fa fa-facebook"></i></a>
                                    <a href="#" class="linkedin"><i class="fa fa-linkedin"></i></a>
                                    <a href="#" class="twitter"><i class="fa fa-twitter"></i></a>
                                    <a href="#" class="google"><i class="fa fa-google"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Listing Details Section End -->
<span class="fa-plus"></span>

<?php

$js = <<<JS
   
    $(function(){
        $("p.read-more").readMore({
            lines: 5,
            readMoreLabel:'<i class="fa fa-plus" aria-hidden="true"></i> Read more',
            readLessLabel:'<i class="fa fa-minus" aria-hidden="true"></i> Read less',
        })
    });

JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>