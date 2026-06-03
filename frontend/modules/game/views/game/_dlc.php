<?php

use common\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dlcs Game[] */
?>

<ul class="dlc-list">
    <?php foreach ($dlcs as $i => $dlc): ?>
        <?php
        $price = $dlc->getDisplayPrice();
        $year = $dlc->release_date ? date('Y', strtotime($dlc->release_date)) : null;
        ?>
        <li class="dlc-row" style="--i: <?= $i ?>" <?= $i >= 3 ? 'data-dlc-hidden hidden' : '' ?>>
            <a href="<?= Url::to(['/game/game/view', 'id' => $dlc->steam_appid, 'slug' => $dlc->slug]) ?>"
               class="dlc-row-link">
                <span class="dlc-thumb">
                    <img src="<?= Html::encode($dlc->getHeader()) ?>"
                         alt="<?= Html::encode($dlc->title) ?>"
                         loading="lazy">
                </span>

                <span class="dlc-body">
                    <span class="dlc-title"><?= Html::encode($dlc->title) ?></span>
                    <span class="dlc-meta">
                        DLC<?php if ($year): ?> · <?= Html::encode($year) ?><?php endif; ?>
                    </span>
                </span>

                <span class="dlc-pricing">
                    <?php if ($price !== null): ?>
                        <?php if ($price->isDiscounted()): ?>
                            <span class="dlc-discount">−<?= $price->discount ?>%</span>
                        <?php endif; ?>
                        <span class="dlc-price <?= $price->free ? 'is-free' : '' ?>">
                            <?= Html::encode($price->final) ?>
                        </span>
                    <?php endif; ?>
                    <span class="dlc-arrow" aria-hidden="true">→</span>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (count($dlcs) > 3): ?>
    <button type="button"
            data-dlc-toggle
            data-expanded="false"
            class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-accent hover:underline">
        <span data-dlc-more>Show all <?= count($dlcs) ?></span>
        <span data-dlc-less hidden>Show less</span>
        <span aria-hidden="true" class="transition-transform" data-dlc-icon>↓</span>
    </button>
<?php endif; ?>
