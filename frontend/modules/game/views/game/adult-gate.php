<?php

use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Game */

$this->title = 'Mature content - ' . Yii::$app->params['meta-title'];
// This is an age-gate interstitial, never a destination for search engines.
$this->params['robots'] = 'noindex,nofollow';
$this->registerCssFile('@web/css/adult-gate.css');

$confirmUrl = Url::current(['confirm' => 1]);
$settingsUrl = Url::to(['/user/profile/settings']);
?>
<div class="agate">
    <span class="agate-rule" aria-hidden="true"></span>

    <div class="agate-inner">
        <div class="agate-seal-wrap fade-up" style="animation-delay:.04s" aria-hidden="true">
            <span class="agate-ring"></span>
            <span class="agate-ring agate-ring--inner"></span>
            <span class="agate-seal"><b>18<sup>+</sup></b></span>
        </div>

        <p class="agate-eyebrow fade-up" style="animation-delay:.12s">Mature content</p>

        <h1 class="agate-title fade-up" style="animation-delay:.18s">
            You're about to open an 18+ game page
        </h1>

        <p class="agate-lede fade-up" style="animation-delay:.24s">
            Steam flags this title as adult-only sexual content. You chose to keep mature
            games hidden, so we paused here. Continue only if you're 18 or older.
        </p>

        <div class="agate-game fade-up" style="animation-delay:.3s">
            <span>Title</span>
            <span><?= Html::encode($model->title) ?></span>
        </div>

        <div class="agate-actions fade-up" style="animation-delay:.36s">
            <?= Html::a(
                'I\'m 18 or older'
                . '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
                $confirmUrl,
                ['class' => 'agate-btn agate-btn--go', 'rel' => 'nofollow']
            ) ?>
            <?= Html::a('Take me back', ['/'], ['class' => 'agate-btn agate-btn--back']) ?>
        </div>

        <p class="agate-note fade-up" style="animation-delay:.42s">
            Prefer to skip this every time? Show 18+ games by default in your
            <?= Html::a('account settings', $settingsUrl) ?>.
        </p>
    </div>
</div>
