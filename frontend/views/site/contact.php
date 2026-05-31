<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\models\ContactForm */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Contact — ' . Yii::$app->params['meta-title'];
$this->params['breadcrumbs'][] = 'Contact';
$this->params['description'] = 'Found a bug, a wrong price, or just want to say hi? Send the Gamentator team a message and we\'ll get back to you.';

// Shared field styling — kept inline (Tailwind browser runtime) to match the
// rest of the site, which styles pages with utilities rather than view CSS.
$inputClass = 'w-full h-11 px-3.5 rounded-xl border border-line bg-canvas text-sm text-fg placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent transition';
$textareaClass = 'w-full px-3.5 py-3 rounded-xl border border-line bg-canvas text-sm text-fg leading-relaxed placeholder:text-fg-subtle focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent transition resize-y';
$labelClass = 'block font-mono text-[11px] uppercase tracking-[0.18em] text-fg-subtle mb-2';
// NOTE: yii.activeForm.js derives the error selector by joining these classes
// with dots ("error":".mt-2.text-xs..."), so every class here must be
// selector-safe — no Tailwind tokens with '.', ':', '/', or '[]' (e.g. avoid
// mt-1.5). Style the input/textarea freely; only this error class is constrained.
$errorClass = 'mt-2 text-xs font-medium text-rose-600';

$channels = [
    [
        'dot'   => 'bg-sky-500',
        'label' => 'Reply time',
        'value' => '1–2 business days',
        'href'  => null,
        'note'  => 'Every message gets read, even the angry ones.',
    ],
    [
        'dot'   => 'bg-indigo-500',
        'label' => 'Wrong data?',
        'value' => 'Check How it works first',
        'href'  => \yii\helpers\Url::to(['/how-it-works']),
        'note'  => 'Most "why is this price off?" answers live there.',
    ],
];
?>

<section class="relative px-4 sm:px-8 lg:px-12 py-10 sm:py-14 lg:py-16">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden rounded-3xl" aria-hidden="true">
        <div class="absolute -top-24 -left-24 h-[420px] w-[420px] rounded-full bg-gradient-to-br from-emerald-200/40 via-teal-200/30 to-cyan-200/30 opacity-60 blur-3xl"></div>
        <div class="absolute top-1/3 -right-20 h-[380px] w-[380px] rounded-full bg-gradient-to-br from-sky-200/30 to-indigo-200/30 opacity-50 blur-3xl"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-start">

        <!-- Left: the invitation + ways to reach us -->
        <div class="lg:col-span-5 lg:pt-6">
            <p class="fade-up inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted"
               style="animation-delay: .05s">
                <span class="h-1.5 w-1.5 rounded-full bg-accent soft-pulse"></span>
                Get in touch
            </p>

            <h1 class="fade-up mt-6 font-display text-4xl sm:text-5xl font-bold text-fg tracking-tight leading-[1.05]"
                style="animation-delay: .12s">
                Let's <span class="text-accent">talk</span>.
            </h1>

            <p class="fade-up mt-5 max-w-md text-lg text-fg-muted leading-relaxed"
               style="animation-delay: .2s">
                Found a bug, spotted a price that looks wrong, or have a partnership in mind?
                We're a small team and we read everything.
            </p>

            <div class="fade-up mt-10 space-y-3" style="animation-delay: .3s">
                <?php foreach ($channels as $c): ?>
                    <?php
                    $inner = <<<HTML
                        <div class="mt-0.5 h-2 w-2 shrink-0 rounded-full {$c['dot']}"></div>
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">{$c['label']}</div>
                            <div class="mt-1 font-display text-sm font-semibold text-fg truncate">{$c['value']}</div>
                            <div class="mt-0.5 text-xs text-fg-muted">{$c['note']}</div>
                        </div>
                    HTML;
                    ?>
                    <?php if ($c['href']): ?>
                        <a href="<?= Html::encode($c['href']) ?>"
                           class="group flex items-start gap-3 rounded-2xl bg-canvas ring-1 ring-line p-4 hover:ring-line-strong hover:shadow-sm transition">
                            <?= $inner ?>
                            <span aria-hidden="true" class="ml-auto self-center text-fg-subtle opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition">→</span>
                        </a>
                    <?php else: ?>
                        <div class="flex items-start gap-3 rounded-2xl bg-surface/60 ring-1 ring-line p-4">
                            <?= $inner ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: the form -->
        <div class="lg:col-span-7 fade-up" style="animation-delay: .25s">
            <div class="rounded-3xl bg-canvas ring-1 ring-line shadow-xl shadow-fg/5 p-6 sm:p-9">
                <div class="flex items-center gap-2 mb-7">
                    <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">Send a message</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>

                <?php $form = ActiveForm::begin([
                    'id'             => 'contact-form',
                    'validateOnBlur' => true,
                    'fieldConfig'    => [
                        'options'      => ['class' => 'mb-5'],
                        'inputOptions' => ['class' => $inputClass],
                        'labelOptions' => ['class' => $labelClass],
                        'errorOptions' => ['class' => $errorClass, 'tag' => 'div'],
                        'template'     => "{label}\n{input}\n{error}",
                    ],
                ]); ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5">
                    <?= $form->field($model, 'name')->textInput([
                        'autofocus'   => true,
                        'placeholder' => 'Your name',
                        'autocomplete' => 'name',
                    ]) ?>

                    <?= $form->field($model, 'email')->input('email', [
                        'placeholder'  => 'you@example.com',
                        'autocomplete' => 'email',
                    ]) ?>
                </div>

                <?= $form->field($model, 'subject')->textInput([
                    'placeholder' => 'What\'s this about?',
                ]) ?>

                <?= $form->field($model, 'body')->textarea([
                    'rows'        => 6,
                    'placeholder' => 'Tell us what\'s on your mind…',
                    'class'       => $textareaClass,
                ]) ?>

                <div class="mt-7 flex flex-wrap items-center gap-4">
                    <?= Html::submitButton('Send message <span aria-hidden="true" class="ml-0.5">→</span>', [
                        'class'  => 'inline-flex items-center gap-1.5 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm',
                        'name'   => 'contact-button',
                        'encode' => false,
                    ]) ?>
                    <p class="text-xs text-fg-subtle">
                        We'll only use your email to reply. No lists, no spam.
                    </p>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</section>
