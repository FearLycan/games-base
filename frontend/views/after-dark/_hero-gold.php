<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Black & Gold hero: centred, shimmering gold headline + a numbered "curator's
 * selection" trio of the top adult titles.
 *
 * @var \yii\web\View         $this
 * @var \common\models\Game[] $featured
 * @var int                   $total
 */
$featured = array_values(array_slice($featured, 0, 3));
$gameUrl = static fn($g) => Url::to(['/game/game/view', 'id' => $g->steam_appid, 'slug' => $g->slug]);
?>
<section class="relative overflow-hidden border-b border-line">
    <div class="absolute inset-x-0 top-0 h-px hairline border-t"></div>
    <div class="mx-auto max-w-7xl px-6 lg:px-8 py-24 sm:py-32 text-center relative">
        <span class="fade-up inline-flex items-center gap-2 rounded-full border border-accent/40 bg-accent-soft px-3 py-1 font-mono text-[10px] uppercase tracking-[0.24em] text-accent" style="animation-delay:.04s">
            <span class="h-1.5 w-1.5 rounded-full bg-accent"></span> 18+ · Members only
        </span>
        <h1 class="fade-up mt-7 font-display text-7xl sm:text-9xl leading-[0.9] tracking-tight" style="animation-delay:.1s">
            <span class="gold-text">After Dark</span>
        </h1>
        <p class="fade-up mx-auto mt-7 max-w-xl text-lg text-fg-muted leading-relaxed italic font-display" style="animation-delay:.16s">
            An after-hours collection, members only. Adult-only Steam titles, synced daily and kept off the public catalog.
        </p>
        <div class="fade-up mt-10 flex flex-wrap items-center justify-center gap-4" style="animation-delay:.22s">
            <a href="#catalog" class="inline-flex items-center gap-2 h-11 px-7 rounded-full text-sm font-bold hover:brightness-110 transition shadow-[0_10px_36px_-12px_rgba(212,175,55,0.7)]" style="background:linear-gradient(135deg,#b8902b,#f1d57a 50%,#b8902b);color:#1a1407">
                Enter the collection
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
            <span class="font-mono text-xs text-fg-subtle"><?= number_format($total) ?> titles · members only</span>
        </div>
    </div>
    <div class="absolute inset-x-0 bottom-0 h-px hairline border-t"></div>
</section>

<?php if ($featured): ?>
    <section class="mx-auto max-w-7xl px-6 lg:px-8 pt-16">
        <div class="flex items-center gap-4 mb-7">
            <span class="font-mono text-[11px] uppercase tracking-[0.24em] text-accent">Curator's selection</span>
            <span class="h-px flex-1 hairline border-t"></span>
        </div>
        <div class="grid sm:grid-cols-3 gap-6">
            <?php foreach ($featured as $i => $g): ?>
                <a href="<?= $gameUrl($g) ?>" data-quick-view="<?= (int)$g->steam_appid ?>" class="card cover group block rounded-2xl overflow-hidden ring-1 ring-line <?= $i === 1 ? 'sm:-mt-6' : '' ?>">
                    <div class="relative aspect-[3/4]">
                        <img src="<?= Html::encode($g->getHeader()) ?>" alt="<?= Html::encode($g->title) ?>" class="absolute inset-0 z-[1] h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 z-[3] flex flex-col justify-end p-5">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-accent">Nº <?= sprintf('%02d', $i + 1) ?></span>
                            <h3 class="mt-1 font-display text-3xl text-white"><?= Html::encode($g->title) ?></h3>
                            <p class="mt-0.5 font-mono text-[10px] uppercase tracking-wider text-white/60">
                                <?= Html::encode($g->getMainGenre()) ?><?php if ($g->review): ?> · <?= $g->review->getPercentsOfPositive() ?>%<?php endif; ?>
                            </p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
