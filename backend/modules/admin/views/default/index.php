<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $cards array<int, array{label: string, count: int, url: string, icon: string, flag: ?string}> */

$this->title = 'Dashboard';
?>
<div class="admin-page-header">
    <h1>Dashboard</h1>
</div>

<div class="row g-3 admin-dashboard">
    <?php foreach ($cards as $card): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <a href="<?= Url::to([$card['url']]) ?>" class="stat-card">
                <div class="stat-card__top">
                    <span class="stat-card__icon"><i class="bi <?= Html::encode($card['icon']) ?>"></i></span>
                    <?php if (!empty($card['flag'])): ?>
                        <span class="stat-card__flag"><?= Html::encode($card['flag']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="stat-card__count"><?= number_format($card['count']) ?></div>
                <div class="stat-card__label"><?= Html::encode($card['label']) ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
