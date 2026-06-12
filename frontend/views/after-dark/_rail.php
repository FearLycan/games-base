<?php

use yii\helpers\Url;

/**
 * "Just arrived" rail — newest adult releases, rendered with the shared game
 * tile so it inherits the active theme tokens.
 *
 * @var \yii\web\View         $this
 * @var \common\models\Game[] $fresh
 */
?>
<section class="pt-16">
    <div class="flex items-center gap-3 mb-6">
        <span class="font-mono text-[11px] uppercase tracking-[0.22em] text-fg-subtle">Just arrived</span>
        <span class="h-px flex-1 bg-line"></span>
        <a href="#catalog" class="font-mono text-[11px] uppercase tracking-wider text-accent hover:text-fg transition">See all →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5">
        <?php foreach ($fresh as $i => $game): ?>
            <div class="fade-up" style="animation-delay:<?= sprintf('%.2fs', 0.04 + $i * 0.06) ?>">
                <?= $this->render('@frontend/modules/game/views/game/_item', ['model' => $game]) ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
