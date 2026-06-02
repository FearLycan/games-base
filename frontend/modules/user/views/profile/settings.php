<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $addEmailForm frontend\modules\user\models\AddEmailForm */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Account settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php $this->beginContent('@frontend/modules/user/views/layouts/account.php'); ?>
<div class="max-w-2xl">
    <h1 class="font-display text-2xl font-semibold text-fg text-balance mb-8 fade-up" style="animation-delay:.05s"><?= Html::encode($this->title) ?></h1>

    <section class="rounded-2xl border border-line bg-surface/40 p-6 fade-up" style="animation-delay:.12s">
        <h2 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Email</h2>

        <?php if ($user->email && $user->isEmailVerified()): ?>
            <p class="flex items-center gap-2 text-sm text-fg">
                <span class="truncate"><?= Html::encode($user->email) ?></span>
                <span class="inline-flex shrink-0 items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Verified</span>
            </p>
        <?php else: ?>
            <?php if ($user->email): ?>
                <div class="mb-4 text-sm text-fg-muted text-pretty">
                    <p class="flex items-center gap-2">
                        <span class="truncate text-fg"><?= Html::encode($user->email) ?></span>
                        <span class="inline-flex shrink-0 items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Pending</span>
                    </p>
                    <p class="mt-1.5">We sent a confirmation link to this address. Didn't get it? Re-enter your email below to resend.</p>
                </div>
            <?php else: ?>
                <p class="mb-4 text-sm text-fg-muted text-pretty">Add an email so you can recover your account and receive notifications. Signing in still happens through Steam.</p>
            <?php endif; ?>

            <?php $form = ActiveForm::begin([
                'id'          => 'add-email-form',
                'action'      => Url::to(['/user/profile/add-email']),
                'fieldConfig' => [
                    'options'      => ['class' => 'mb-4'],
                    'labelOptions' => ['class' => 'block text-xs font-medium text-fg-muted mb-1.5'],
                    'inputOptions' => ['class' => 'block w-full rounded-lg border border-line bg-canvas px-3 py-2.5 text-sm text-fg shadow-sm outline-none transition-colors focus:border-accent'],
                    'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
                ],
            ]); ?>
                <?= $form->field($addEmailForm, 'email')->textInput(['type' => 'email', 'autofocus' => true, 'autocomplete' => 'email'])->label('Email address') ?>
                <?= Html::submitButton('Save and send confirmation', [
                    'class' => 'inline-flex h-11 items-center rounded-lg bg-accent px-4 text-sm font-semibold text-white shadow-sm transition-transform duration-150 will-change-transform active:scale-[0.96]',
                ]) ?>
            <?php ActiveForm::end(); ?>
        <?php endif; ?>
    </section>
</div>
<?php $this->endContent(); ?>
