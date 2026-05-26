<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $item array|object */
/* @var $kind string */

$name = $item->name ?? '';
$slug = $item->slug ?? '';
$gamesCount = (int)($item->games_count ?? 0);
$profile = $item->profile ?? null;
$initials = mb_strtoupper(mb_substr($name, 0, 2));
?>

<a href="<?= Url::to(['/' . $kind . '/' . $kind . '/view', 'slug' => $slug]) ?>"
   class="company-card group">

    <div class="company-card-logo">
        <?php if ($profile?->logo_url): ?>
            <img src="<?= Html::encode($profile->logo_url) ?>"
                 alt="<?= Html::encode($name) ?>"
                 loading="lazy">
        <?php else: ?>
            <span class="company-card-initials"><?= Html::encode($initials) ?></span>
        <?php endif; ?>
    </div>

    <div class="company-card-body">
        <h3 class="company-card-name"><?= Html::encode($name) ?></h3>

        <div class="company-card-meta">
            <?php if ($profile?->country): ?>
                <span class="truncate"><?= Html::encode($profile->country) ?></span>
                <span class="company-card-dot"></span>
            <?php endif; ?>
            <span class="shrink-0"><?= number_format($gamesCount) ?> game<?= $gamesCount === 1 ? '' : 's' ?></span>
            <?php if ($profile?->founded_year): ?>
                <span class="company-card-dot"></span>
                <span class="shrink-0">since <?= (int)$profile->founded_year ?></span>
            <?php endif; ?>
        </div>

        <?php if ($profile?->description): ?>
            <p class="company-card-desc">
                <?= Html::encode($profile->description) ?>
            </p>
        <?php endif; ?>
    </div>
</a>
