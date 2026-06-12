<?php

use common\enums\AfterDarkLayout;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Compact, tucked-away theme switcher (lives in the footer). Each swatch posts
 * the choice, which is persisted on the account; the page then re-renders in the
 * new look.
 *
 * @var \yii\web\View    $this
 * @var AfterDarkLayout  $current
 */
?>
<div class="flex items-center gap-2">
    <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Theme</span>
    <div class="flex items-center gap-1.5">
        <?php foreach (AfterDarkLayout::cases() as $option): ?>
            <?php $active = $option === $current; ?>
            <?= Html::beginForm(Url::to(['/after-dark/layout']), 'post', ['class' => 'contents']) ?>
                <input type="hidden" name="layout" value="<?= Html::encode($option->value) ?>">
                <button type="submit"
                        title="<?= Html::encode($option->label()) ?>"
                        aria-label="Switch to <?= Html::encode($option->label()) ?>"
                        <?= $active ? 'aria-current="true" disabled' : '' ?>
                        class="h-5 w-5 rounded-full ring-2 ring-offset-2 ring-offset-canvas transition <?= $active
                            ? 'ring-accent cursor-default'
                            : 'ring-transparent hover:ring-line-strong cursor-pointer' ?>"
                        style="background:<?= $option->swatch() ?>"></button>
            <?= Html::endForm() ?>
        <?php endforeach; ?>

        <span class="mx-1 h-4 w-px bg-line" aria-hidden="true"></span>

        <a href="<?= Url::to(['/games']) ?>"
           title="Back to the standard layout"
           class="group inline-flex items-center gap-1.5">
            <span class="h-5 w-5 rounded-full ring-2 ring-transparent ring-offset-2 ring-offset-canvas transition group-hover:ring-line-strong"
                  style="background:linear-gradient(135deg,#ffffff,#cbd5e1)"></span>
            <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle transition group-hover:text-fg">Standard</span>
        </a>
    </div>
</div>
