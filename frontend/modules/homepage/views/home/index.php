<?php

use common\models\Game;
use common\models\GameGenre;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $genres GameGenre */
/* @var $bestsellers Game[] */
/* @var $popular_upcoming Game[] */
/* @var $new_and_noteworthy Game[] */

$this->title = Yii::$app->name;
?>

    <!-- Hero Section Begin -->
    <section class="hero set-bg" data-setbg="/img/hero/hero-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="hero__text">
                        <!-- <div class="section-title">
                            <h2>Discover Games</h2>
                            <p><?= Game::count() ?> games waiting to be discovered</p>
                        </div> -->
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

    <section class="categories spad">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title">
                        <h2>Most Popular Categories</h2>
                        <p>Velocity empowers travelers who are giving back on their trips in ways big and small</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog__sidebar__recent">
                        <h5>Bestsellers</h5>
                        <?php foreach ($bestsellers as $game): ?>
                            <?= $this->render('_game-sale-item', ['game' => $game]) ?>
                        <?php endforeach; ?>
                    </div>

                </div>
                <div class="col-md-4">
                    <div class="blog__sidebar__recent">
                        <h5>New and noteworthy</h5>
                        <?php foreach ($new_and_noteworthy as $game): ?>
                            <?= $this->render('_game-sale-item', ['game' => $game]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog__sidebar__recent">
                        <h5>Popular upcoming</h5>
                        <?php foreach ($popular_upcoming as $game): ?>
                            <?= $this->render('_game-sale-item', ['game' => $game]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>


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
                //let input = $('#game-name-select');
                //console.log(input.getSelectedItemData().id)
                /*$('#game-id').val(input.getSelectedItemData().id);
                input.val('');
                getData();*/
            }
        }
    });

    /*$('.hero__search__form').find('.easy-autocomplete').css({'width': 'auto'});
    $('.hero__search__form').find('.easy-autocomplete').prepend('<button class="" type="submit">Explore Now</button>');*/
    
    
JS;

$this->registerJs($js);
?>