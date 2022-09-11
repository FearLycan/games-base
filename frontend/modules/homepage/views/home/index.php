<?php

use common\models\GameGenre;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $genres GameGenre */

$this->title = Yii::$app->name;
?>

    <!-- Hero Section Begin -->
    <section class="hero set-bg" data-setbg="/img/hero/hero-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="hero__text">
                        <div class="section-title">
                            <h2>Discover The Best Services Near You</h2>
                            <p>1.118.940.376 The best service package is waiting for you</p>
                        </div>
                        <div class="hero__search__form">
                            <form action="#">
                                <input type="text" id="game-name-select" placeholder="Search...">


                            </form>
                        </div>
                        <ul class="hero__categories__tags">

                            <?php foreach ($genres as $genre): ?>
                                <li data-item="<?= $genre->genre->id ?>">
                                    <a href="<?= Url::to(['/games/' . $genre->genre->slug]) ?>">
                                        <!-- <img src="/img/genre-icon-default.png" style="display: none;" loading="lazy"
                                             alt="<?= $genre->genre->name ?>"> -->
                                        <?= $genre->genre->name ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li>
                                <a href="#"><img src="/img/hero/cat-6.png" loading="lazy" alt=""> All Categories</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Hero Section End -->

    <p>
        <a href="<?= \yii\helpers\Url::to(['/game/game/view', 'id' => 1259420, 'slug' => 'days-gone']) ?>">Days Gone</a>
    </p>


<?php
$js = <<<JS
   
   $('#game-name-select').easyAutocomplete({
        url: function (phrase) {
            return '/game/search-list?phrase=' + encodeURIComponent(phrase);
        },
        getValue: function (element) {
            return element.title;
        },
        template: {
            type: "custom",
            method: function (title, item) {
                if(item.url) {
                    return "" +
                    "<div class='row'>" +      
                        "<a class='game-search-link' href='/game/" + item.id + "/" + item.slug + "'>" + 
                            "<div class='col-md-2 col-sm-2 col-xs-5' style='display:inline-block;'>" +
                                "<img height='45' class='img-fluid' loading='lazy' src='" + item.url + "' />" +
                            "</div>" +
                            "<div class='col-md-8 col-sm-8 col-xs-5' style='display:inline-block;'>" +
                                "<div class='search-title'>" + title + "</div>" +               
                            "</div>" +
                        "</a>" +
                    "</div>";
                } else {
                    return title;
                }
            }
        },
        highlightPhrase: false,
        requestDelay: 350,       
        list: {
            match: {
                enabled: true
            },
            maxNumberOfElements: 10,
            onClickEvent: function () {
                let input = $('#game-name-select');
                console.log(input.getSelectedItemData().id)
                /*$('#game-id').val(input.getSelectedItemData().id);
                input.val('');
                getData();*/
            }
        }
    });

    $('.hero__search__form').find('.easy-autocomplete').css({'width': 'auto'});
    $('.hero__search__form').find('.easy-autocomplete').prepend('<button type="submit">Explore Now</button>');
    
    
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
?>