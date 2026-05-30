<?php

use common\models\GameOffer;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $offers GameOffer[] */
/* @var $bestOffer GameOffer|null */
/* @var $offerCurrency string */

// Shared "Where to buy" offers list. The caller owns the surrounding chrome
// (section header / sidebar card) and any currency switcher; this partial only
// renders the offer rows so the same markup serves the game page and the
// AJAX-loaded list preview.
?>
<div class="store-offers">
    <div class="store-offers-list">
        <?php foreach ($offers as $i => $offer): ?>
            <?php
            $price = $offer->getPrice($offerCurrency);
            $discount = $price ? $price->getDiscountPercent() : 0;
            $meta = implode(' · ', array_filter([$offer->edition, $offer->region]));
            $isBest = $bestOffer && $offer->id === $bestOffer->id;
            ?>

            <?php if ($isBest): ?>
                <a href="<?= Html::encode($offer->url) ?>"
                   target="_blank"
                   rel="nofollow noopener sponsored external"
                   class="offer offer-best"
                   style="animation-delay: <?= $i * 70 ?>ms">
                    <span class="offer-best-tag">Best price</span>

                    <div class="offer-best-head">
                        <img class="offer-logo"
                             src="<?= Html::encode($offer->store->getLogo()) ?>"
                             alt="<?= Html::encode($offer->store->name) ?>"
                             loading="lazy">
                        <div class="offer-body">
                            <div class="offer-store"><?= Html::encode($offer->store->name) ?></div>
                            <?php if ($meta !== ''): ?>
                                <div class="offer-meta"><?= Html::encode($meta) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="offer-best-price-row">
                        <div class="offer-best-pricing">
                            <?php if ($discount > 0): ?>
                                <span class="offer-discount">−<?= $discount ?>%</span>
                            <?php endif; ?>
                            <div class="offer-best-amount">
                                <span class="offer-best-price"><?= Html::encode($price->getFinalPriceLabel()) ?></span>
                                <?php if ($discount > 0 && $price->getInitialPriceLabel() !== null): ?>
                                    <span class="offer-best-initial"><?= Html::encode($price->getInitialPriceLabel()) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="offer-cta">Buy <span class="arrow" aria-hidden="true">→</span></span>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?= Html::encode($offer->url) ?>"
                   target="_blank"
                   rel="nofollow noopener sponsored external"
                   class="offer"
                   style="animation-delay: <?= $i * 70 ?>ms">
                    <img class="offer-logo"
                         src="<?= Html::encode($offer->store->getLogo()) ?>"
                         alt="<?= Html::encode($offer->store->name) ?>"
                         loading="lazy">
                    <div class="offer-body">
                        <div class="offer-store"><?= Html::encode($offer->store->name) ?></div>
                        <?php if ($meta !== ''): ?>
                            <div class="offer-meta"><?= Html::encode($meta) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($price && $price->getFinalPriceLabel() !== null): ?>
                        <div class="offer-pricing">
                            <?php if ($discount > 0): ?>
                                <span class="offer-discount">−<?= $discount ?>%</span>
                            <?php endif; ?>
                            <div class="offer-price"><?= Html::encode($price->getFinalPriceLabel()) ?></div>
                            <?php if ($discount > 0 && $price->getInitialPriceLabel() !== null): ?>
                                <div class="offer-initial"><?= Html::encode($price->getInitialPriceLabel()) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="offer-cta"><span class="arrow" aria-hidden="true">→</span></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
