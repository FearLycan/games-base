<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Light Rosé hero: bright canvas, wine accent, two-image showcase beside the
 * headline.
 *
 * @var \yii\web\View         $this
 * @var \common\models\Game[] $featured
 * @var int                   $total
 */
$featured = array_values($featured);
$showcase = array_slice($featured, 0, 2);
$gameUrl = static fn($g) => Url::to(['/game/game/view', 'id' => $g->steam_appid, 'slug' => $g->slug]);
?>
<section class="relative overflow-hidden border-b border-line">
    <div class="mx-auto max-w-7xl px-6 lg:px-8 py-20 sm:py-24">
        <div class="grid lg:grid-cols-[1.1fr_0.9fr] gap-12 items-center">
            <div>
                <span class="fade-up inline-flex items-center gap-2 rounded-full border border-line-strong bg-accent-soft px-3 py-1 font-mono text-[10px] uppercase tracking-[0.2em] text-accent" style="animation-delay:.04s">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent"></span> 18+ · Age-verified
                </span>
                <h1 class="fade-up mt-6 font-display text-6xl sm:text-7xl leading-[0.95] tracking-tight text-fg" style="animation-delay:.1s">
                    After <span class="italic text-accent">Dark</span>
                </h1>
                <p class="fade-up mt-6 max-w-lg text-lg text-fg-muted leading-relaxed" style="animation-delay:.16s">
                    The grown-up corner of the catalog. Adult-only Steam titles, synced daily, for everyone who opted in.
                </p>
                <div class="fade-up mt-9 flex flex-wrap items-center gap-3" style="animation-delay:.22s">
                    <a href="#catalog" class="inline-flex items-center gap-2 h-11 px-6 rounded-full bg-accent text-white text-sm font-semibold hover:bg-accent-2 transition shadow-[0_10px_30px_-10px_rgba(200,29,90,0.55)]">
                        Enter the catalog
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                    <span class="font-mono text-xs text-fg-subtle"><?= number_format($total) ?> titles</span>
                </div>
            </div>
            <?php if ($showcase): ?>
                <div class="fade-up grid grid-cols-2 gap-4" style="animation-delay:.28s">
                    <?php foreach ($showcase as $i => $g): ?>
                        <a href="<?= $gameUrl($g) ?>" data-quick-view="<?= (int)$g->steam_appid ?>" class="card cover group relative block rounded-2xl overflow-hidden ring-1 ring-line aspect-[3/4] <?= $i === 0 ? 'mt-8' : '' ?>">
                            <img src="<?= Html::encode($g->getHeader()) ?>" alt="<?= Html::encode($g->title) ?>" class="absolute inset-0 z-[1] h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 z-[3] flex items-end p-4">
                                <span class="font-display italic text-2xl text-white"><?= Html::encode($g->title) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
