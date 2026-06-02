<?php

/* @var $this yii\web\View */
/* @var $model \frontend\modules\user\models\LoginForm */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Sign in';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="mx-auto w-full max-w-md">

    <div class="text-center mb-8 fade-up" style="animation-delay:.05s">
        <h1 class="font-display text-2xl font-semibold text-fg text-balance">Sign in to <?= Html::encode(Yii::$app->name) ?></h1>
        <p class="mt-2 text-sm text-fg-muted text-pretty">Connect with Steam to sync your library, wishlist and achievements.</p>
    </div>

    <div class="fade-up" style="animation-delay:.12s">
        <a href="<?= Url::to(['/auth/steam']) ?>"
           rel="nofollow"
           class="group flex h-12 w-full items-center justify-center gap-3 rounded-xl bg-fg px-5 text-sm font-semibold text-canvas shadow-[0_1px_2px_rgba(15,23,42,.16),0_8px_24px_-12px_rgba(15,23,42,.5)] transition-transform duration-150 will-change-transform hover:-translate-y-0.5 active:scale-[0.96]">
            <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M11.98 2C6.6 2 2.2 6.16 2 11.42l5.36 2.22a3.03 3.03 0 0 1 1.7-.53l.13.01 2.39-3.46v-.05a4.04 4.04 0 1 1 4.04 4.04h-.1l-3.41 2.43.01.1a3.04 3.04 0 0 1-6.03.56l-3.83-1.59A10 10 0 1 0 11.98 2zM9.27 17.18l-1.23-.5a2.28 2.28 0 0 0 4.2-.95 2.28 2.28 0 0 0-2.27-2.28c-.24 0-.47.04-.69.11l1.27.53a1.68 1.68 0 1 1-1.29 3.1zm9.46-6.43a2.69 2.69 0 1 0-5.38 0 2.69 2.69 0 0 0 5.38 0zm-4.7-.01a2.02 2.02 0 1 1 4.04 0 2.02 2.02 0 0 1-4.04 0z"/>
            </svg>
            Sign in through Steam
        </a>
    </div>

    <p class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-fg-subtle fade-up" style="animation-delay:.18s">
        <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
            <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
        </svg>
        We never see your Steam password — sign-in happens on Steam.
    </p>

</div>
