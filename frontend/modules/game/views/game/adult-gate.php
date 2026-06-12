<?php

use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Game */
/* @var $isGuest bool */

$isGuest ??= false;

$this->title = 'Mature content - ' . Yii::$app->params['meta-title'];
// This is an age-gate interstitial, never a destination for search engines.
$this->params['robots'] = 'noindex,nofollow';
$this->registerCssFile('@web/css/adult-gate.css');

// Guests must sign in (and opt in) before any 18+ page renders; signed-in,
// opted-out users only need to confirm their age for this one game. Carry the
// current game URL as ?return so Steam sign-in lands the visitor back here.
$loginUrl = Url::to(['/user/auth/login', 'return' => Url::current()]);
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
            <?php if ($isGuest): ?>
                Steam flags this title as adult-only sexual content. We keep mature games
                hidden until you sign in and confirm you're 18 or older.
            <?php else: ?>
                Steam flags this title as adult-only sexual content. You chose to keep mature
                games hidden, so we paused here. Continue only if you're 18 or older.
            <?php endif; ?>
        </p>

        <div class="agate-game fade-up" style="animation-delay:.3s">
            <span>Title</span>
            <span><?= Html::encode($model->title) ?></span>
        </div>

        <div class="agate-actions fade-up" style="animation-delay:.36s">
            <?php if ($isGuest): ?>
                <?= Html::a(
                    'Sign in to continue'
                    . '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
                    $loginUrl,
                    ['class' => 'agate-btn agate-btn--go', 'rel' => 'nofollow']
                ) ?>
            <?php else: ?>
                <?= Html::a(
                    'I\'m 18 or older'
                    . '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
                    $confirmUrl,
                    ['class' => 'agate-btn agate-btn--go', 'rel' => 'nofollow']
                ) ?>
            <?php endif; ?>
            <?= Html::a('Take me back', ['/'], ['class' => 'agate-btn agate-btn--back']) ?>
        </div>

        <p class="agate-note fade-up" style="animation-delay:.42s">
            <?php if ($isGuest): ?>
                Already have an account? After signing in, turn on 18+ games in your
                <?= Html::a('account settings', $settingsUrl) ?> to skip this step.
            <?php else: ?>
                Prefer to skip this every time? Show 18+ games by default in your
                <?= Html::a('account settings', $settingsUrl) ?>.
            <?php endif; ?>
        </p>

        <aside class="agate-teaser fade-up" style="animation-delay:.5s">
            <span class="agate-teaser-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 9 9 7 7 0 0 1-9-9Z"/><path d="m17 5 .6 1.6L19 7l-1.4.4L17 9l-.6-1.6L15 7l1.4-.4Z"/></svg>
            </span>
            <div class="agate-teaser-body">
                <p class="agate-teaser-eyebrow">Once you're in</p>
                <h2 class="agate-teaser-title">There's a whole After Dark</h2>
                <p class="agate-teaser-text">
                    Switch on 18+ and you unlock more than this page. <strong>After Dark</strong> is a
                    separate, members-only space for adult titles, with its own spotlights and a feed of
                    the newest arrivals. You even get three looks to switch between.
                </p>
                <?php if ($isGuest): ?>
                    <?= Html::a(
                        'Sign in to step inside'
                        . '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
                        $loginUrl,
                        ['class' => 'agate-teaser-link', 'rel' => 'nofollow']
                    ) ?>
                <?php else: ?>
                    <?= Html::a(
                        'Enable 18+ to step inside'
                        . '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
                        $settingsUrl,
                        ['class' => 'agate-teaser-link']
                    ) ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
