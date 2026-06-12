<?php

use common\models\Game;
use common\models\GameOffer;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Quick-view card for one adult title, injected into the After Dark modal over
 * AJAX. Pure presentation, token-driven so it re-skins with the active theme;
 * the slider / inline-trailer behaviour is wired up by the modal script in
 * index.php once this markup lands.
 *
 * @var \yii\web\View $this
 * @var Game          $game
 * @var string[]      $shots         lightweight screenshot URLs
 * @var GameOffer[]   $offers        sorted cheapest-first
 * @var GameOffer|null $bestOffer
 * @var string        $offerCurrency
 */

$trailer = $game->getTrailer();
$gameUrl = Url::to(['/game/game/view', 'id' => $game->steam_appid, 'slug' => $game->slug]);
$genre = $game->getMainGenre();

$year = '';
if ($game->release_date && ($ts = strtotime($game->release_date))) {
    $year = date('Y', $ts);
}

$meta = array_values(array_filter([$genre, $year]));
$display = $game->getDisplayPrice($offerCurrency);
$hasMedia = $trailer !== null || $shots !== [];
?>
<article class="qv">
    <header class="fade-up flex items-start gap-4 border-b border-line/60 px-5 sm:px-7 pt-6 pb-5 pr-14" style="animation-delay:.04s">
        <img src="<?= Html::encode($game->getHeader()) ?>" alt=""
             class="hidden sm:block w-32 shrink-0 rounded-lg object-cover ring-1 ring-line shadow-lg shadow-black/20" style="aspect-ratio:460/215">
        <div class="min-w-0">
            <h2 class="font-display text-2xl sm:text-3xl leading-tight tracking-tight text-fg"><?= Html::encode($game->title) ?></h2>
            <?php if ($meta): ?>
                <p class="mt-1.5 font-mono text-[11px] uppercase tracking-wider text-fg-subtle">
                    <?= Html::encode(implode(' · ', $meta)) ?>
                </p>
            <?php endif; ?>
        </div>
    </header>

    <div class="px-5 sm:px-7 py-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="fade-up min-w-0" style="animation-delay:.1s">
            <?php if ($hasMedia): ?>
                <div data-qv-media class="relative">
                    <div data-qv-track class="qv-track flex overflow-x-auto snap-x snap-mandatory rounded-xl bg-black ring-1 ring-line">
                        <?php if ($trailer !== null): ?>
                            <div class="qv-slide relative shrink-0 w-full snap-start aspect-video" data-embed="<?= Html::encode($trailer->getEmbedUrl()) ?>">
                                <img src="<?= Html::encode($trailer->getThumbnailUrl()) ?>" alt="" class="absolute inset-0 h-full w-full object-cover">
                                <span class="absolute inset-0 bg-gradient-to-t from-black/45 to-black/10"></span>
                                <button type="button" data-qv-play aria-label="Play trailer"
                                        class="group absolute inset-0 grid cursor-pointer place-items-center">
                                    <span class="grid h-14 w-14 place-items-center rounded-full bg-white/90 text-black shadow-lg ring-1 ring-black/10 backdrop-blur transition group-hover:scale-110 group-hover:bg-accent group-hover:text-white">
                                        <svg class="h-5 w-5 translate-x-[1px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>
                                    </span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($shots as $src): ?>
                            <div class="qv-slide relative shrink-0 w-full snap-start aspect-video">
                                <img src="<?= Html::encode($src) ?>" loading="lazy" alt="" class="absolute inset-0 h-full w-full object-cover">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php $slideCount = ($trailer !== null ? 1 : 0) + count($shots); ?>
                    <?php if ($slideCount > 1): ?>
                        <button type="button" data-qv-prev aria-label="Previous"
                                class="absolute left-2 top-1/2 -translate-y-1/2 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-black/55 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-black/75 disabled:cursor-default disabled:opacity-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <button type="button" data-qv-next aria-label="Next"
                                class="absolute right-2 top-1/2 -translate-y-1/2 grid h-9 w-9 cursor-pointer place-items-center rounded-full bg-black/55 text-white ring-1 ring-white/15 backdrop-blur transition hover:bg-black/75 disabled:cursor-default disabled:opacity-0">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6l6 6-6 6"/></svg>
                        </button>
                        <div class="mt-3 flex items-center justify-center gap-1.5">
                            <?php for ($i = 0; $i < $slideCount; $i++): ?>
                                <button type="button" data-qv-dot aria-label="Go to slide <?= $i + 1 ?>"
                                        class="qv-dot h-1.5 cursor-pointer rounded-full bg-fg/25 transition-all<?= $i === 0 ? ' is-on' : '' ?>"></button>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="relative aspect-video overflow-hidden rounded-xl ring-1 ring-line">
                    <img src="<?= Html::encode($game->getHeader()) ?>" alt="<?= Html::encode($game->title) ?>" class="absolute inset-0 h-full w-full object-cover">
                </div>
            <?php endif; ?>
        </div>

        <div class="fade-up min-w-0" style="animation-delay:.16s">
            <?php if ($game->short_description): ?>
                <p class="text-sm leading-relaxed text-fg-muted"><?= Html::encode($game->short_description) ?></p>
            <?php endif; ?>

            <div class="mt-5">
                <h3 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-2.5">Where to buy</h3>
                <?php if ($offers): ?>
                    <div class="space-y-2">
                        <?php foreach ($offers as $offer): ?>
                            <?php
                            $price = $offer->getPrice($offerCurrency);
                            if ($price === null || $price->getFinalPriceLabel() === null) {
                                continue;
                            }
                            $discount = $price->getDiscountPercent();
                            $isBest = $bestOffer && $offer->id === $bestOffer->id;
                            $rowMeta = implode(' · ', array_filter([$offer->edition, $offer->region]));
                            ?>
                            <a href="<?= Html::encode($offer->url) ?>" target="_blank" rel="nofollow noopener sponsored external"
                               class="flex items-center gap-3 rounded-xl border px-3 py-2.5 transition <?= $isBest ? 'border-accent bg-accent-soft' : 'border-line bg-surface-2/40 hover:border-line-strong' ?>">
                                <img src="<?= Html::encode($offer->store->getLogo()) ?>" alt="" loading="lazy" class="h-7 w-7 shrink-0 rounded-md bg-white/5 object-contain">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium text-fg"><?= Html::encode($offer->store->name) ?></span>
                                        <?php if ($isBest): ?>
                                            <span class="shrink-0 rounded-full bg-accent px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-white">Best</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($rowMeta !== ''): ?>
                                        <div class="truncate text-[11px] text-fg-subtle"><?= Html::encode($rowMeta) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="flex items-baseline justify-end gap-1.5">
                                        <?php if ($discount > 0): ?>
                                            <span class="rounded bg-accent/15 px-1.5 py-px text-[10px] font-bold text-accent">−<?= $discount ?>%</span>
                                        <?php endif; ?>
                                        <span class="text-sm font-semibold tabular-nums text-fg"><?= Html::encode($price->getFinalPriceLabel()) ?></span>
                                    </div>
                                    <?php if ($discount > 0 && $price->getInitialPriceLabel() !== null): ?>
                                        <div class="text-[11px] text-fg-subtle line-through tabular-nums"><?= Html::encode($price->getInitialPriceLabel()) ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($display !== null): ?>
                    <div class="inline-flex items-baseline gap-2 rounded-xl border border-line bg-surface-2/40 px-4 py-2.5">
                        <?php if ($display->isDiscounted()): ?>
                            <span class="rounded bg-accent/15 px-1.5 py-px text-[10px] font-bold text-accent">−<?= $display->discount ?>%</span>
                            <span class="text-sm text-fg-subtle line-through tabular-nums"><?= Html::encode($display->initial) ?></span>
                        <?php endif; ?>
                        <span class="text-base font-semibold tabular-nums <?= $display->free ? 'text-accent' : 'text-fg' ?>"><?= Html::encode($display->final) ?></span>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-fg-subtle">No price right now.</p>
                <?php endif; ?>
            </div>

            <a href="<?= $gameUrl ?>"
               class="qv-cta group mt-5 inline-flex w-full items-center justify-center gap-2 h-11 rounded-full bg-accent text-white text-sm font-semibold transition hover:bg-accent-2">
                View full game page
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>
    </div>
</article>
