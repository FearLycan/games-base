<?php

/* @var $this yii\web\View */

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
                            <li><a href="#"><img src="/img/hero/cat-1.png" alt=""> Restaurent</a></li>
                            <li><a href="#"><img src="/img/hero/cat-2.png" alt=""> Food & Drink</a></li>
                            <li><a href="#"><img src="/img/hero/cat-3.png" alt=""> Shopping</a></li>
                            <li><a href="#"><img src="/img/hero/cat-4.png" alt=""> Beauty</a></li>
                            <li><a href="#"><img src="/img/hero/cat-5.png" alt=""> Hotels</a></li>
                            <li><a href="#"><img src="/img/hero/cat-6.png" alt=""> All Categories</a></li>
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
            return '/game/search-list?phrase=' +encodeURIComponent(phrase);
        },
        getValue: function (element) {
            return element.title;
        },
        highlightPhrase: false,
        requestDelay: 350,       
        list: {
            match: {
                enabled: true
            },
            maxNumberOfElements: 10,        
        }
    });

    $('.hero__search__form').find('.easy-autocomplete').css({'width': 'auto'});
    // let button = $('.hero__search__form').find('button');
    // console.log(button);
    $('.hero__search__form').find('.easy-autocomplete').prepend('<button type="submit">Explore Now</button>');
    
    
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
?>