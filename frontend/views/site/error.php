<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */

/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>

<div class="blog-details-hero set-bg" data-setbg="/img/background-default.jpg"
     style="background-image: url(/img/background-default.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="blog__hero__text">
                    <div class="label">Problem</div>
                    <h2><?= Html::encode($this->title) ?></h2>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="blog-details spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="blog__details__text">
                    <h5><?= nl2br(Html::encode($message)) ?></h5>
                    <p>
                        The above error occurred while the Web server was processing your request.
                    </p>
                    <p>
                        Please contact us if you think this is a server error. Thank you.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
