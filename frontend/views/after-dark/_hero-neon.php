<?php

use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Neon Plum hero: dark, magenta-glow headline + a spotlight (one large + two
 * small) of the top adult titles.
 *
 * @var \yii\web\View         $this
 * @var \common\models\Game[] $featured
 * @var int                   $total
 */
$featured = array_values($featured);
$big = $featured[0] ?? null;
$small = array_slice($featured, 1, 2);

$gameUrl = static fn($g) => Url::to(['/game/game/view', 'id' => $g->steam_appid, 'slug' => $g->slug]);
?>
<section class="relative overflow-hidden border-b border-line">
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 h-96 w-[42rem] rounded-full bg-accent/20 blur-[120px] glow-pulse"></div>
    <div class="mx-auto max-w-7xl px-6 lg:px-8 py-20 sm:py-28 relative">
        <div class="max-w-3xl">
            <span class="fade-up inline-flex items-center gap-2 rounded-full border border-line-strong bg-surface/60 px-3 py-1 font-mono text-[10px] uppercase tracking-[0.22em] text-fg-muted" style="animation-delay:.04s">
                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span> 18+ · Age-verified
            </span>
            <h1 class="fade-up mt-6 font-display italic text-6xl sm:text-8xl leading-[0.92] tracking-tight text-fg neon-text" style="animation-delay:.1s">After Dark</h1>
            <p class="fade-up mt-6 max-w-xl text-lg text-fg-muted leading-relaxed" style="animation-delay:.16s">
                The after-hours side of the catalog. Adult-only Steam titles, for members who switched the lights off in settings.
            </p>
            <div class="fade-up mt-9 flex flex-wrap items-center gap-3" style="animation-delay:.22s">
                <a href="#catalog" class="inline-flex items-center gap-2 h-11 px-6 rounded-full text-white text-sm font-semibold hover:brightness-110 transition shadow-[0_10px_40px_-10px_rgba(255,45,120,0.6)]" style="background:linear-gradient(90deg,#ff2d78,#b026ff)">
                    Enter the catalog
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </a>
                <span class="font-mono text-xs text-fg-subtle"><?= number_format($total) ?> titles · synced daily</span>
            </div>
        </div>
    </div>
</section>

<?php if ($big): ?>
    <section class="mx-auto max-w-7xl px-6 lg:px-8 pt-16">
        <div class="flex items-center gap-3 mb-6">
            <span class="font-mono text-[11px] uppercase tracking-[0.22em] text-fg-subtle">Right now</span>
            <span class="h-px flex-1 bg-line"></span>
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <a href="<?= $gameUrl($big) ?>" data-quick-view="<?= (int)$big->steam_appid ?>" class="card cover group relative block aspect-[16/10] rounded-2xl overflow-hidden ring-1 ring-line">
                <img src="<?= Html::encode($big->getHeader()) ?>" alt="<?= Html::encode($big->title) ?>" class="absolute inset-0 z-[1] h-full w-full object-cover transition duration-500 group-hover:scale-105">
                <div class="absolute inset-0 z-[3] flex flex-col justify-end p-7">
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-widest text-white" style="background:#ff2d78e6">Editor's pick</span>
                    <h3 class="mt-3 font-display italic text-4xl text-white"><?= Html::encode($big->title) ?></h3>
                    <p class="mt-1 font-mono text-xs uppercase tracking-wider text-white/70">
                        <?= Html::encode($big->getMainGenre()) ?><?php if ($big->review): ?> · <?= $big->review->getPercentsOfPositive() ?>% positive<?php endif; ?>
                    </p>
                </div>
            </a>
            <div class="grid sm:grid-cols-2 gap-6">
                <?php foreach ($small as $g): ?>
                    <a href="<?= $gameUrl($g) ?>" data-quick-view="<?= (int)$g->steam_appid ?>" class="card cover group relative block rounded-2xl overflow-hidden ring-1 ring-line min-h-44">
                        <img src="<?= Html::encode($g->getHeader()) ?>" alt="<?= Html::encode($g->title) ?>" class="absolute inset-0 z-[1] h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 z-[3] flex flex-col justify-end p-5">
                            <h3 class="font-display italic text-2xl text-white"><?= Html::encode($g->title) ?></h3>
                            <p class="mt-0.5 font-mono text-[10px] uppercase tracking-wider text-white/70"><?= Html::encode($g->getMainGenre()) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
