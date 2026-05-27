<?php

use common\models\Genre;
use common\models\Tag;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\HttpException;
use yii\web\View;

/* @var $this View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */
/* @var $genres Genre[] */
/* @var $tags Tag[] */

$genres = $genres ?? [];
$tags = $tags ?? [];

$statusCode = ($exception instanceof HttpException) ? $exception->statusCode : null;

$variants = [
    404 => [
        'eyebrow'  => '404 · Not found',
        'title'    => 'Game over.',
        'accent'   => 'for this URL.',
        'message'  => 'We searched the catalog and came up empty. The page you\'re after might have moved, retired, or never existed.',
        'dot'      => 'bg-amber-500',
        'pillBg'   => 'bg-amber-50',
        'pillText' => 'text-amber-700',
        'pillRing' => 'ring-amber-200',
        'glow'     => 'from-amber-200/50 via-rose-200/30 to-pink-200/30',
    ],
    403 => [
        'eyebrow'  => '403 · Forbidden',
        'title'    => 'Locked door.',
        'accent'   => 'No entry from here.',
        'message'  => 'You don\'t have permission to view this page. If this looks like a mistake, drop us a line.',
        'dot'      => 'bg-rose-500',
        'pillBg'   => 'bg-rose-50',
        'pillText' => 'text-rose-700',
        'pillRing' => 'ring-rose-200',
        'glow'     => 'from-rose-200/40 via-pink-200/30 to-violet-200/30',
    ],
    500 => [
        'eyebrow'  => '500 · Server error',
        'title'    => 'We tripped.',
        'accent'   => 'Sorry about that.',
        'message'  => 'Something broke on our side while loading this page. We\'re looking into it. Try again in a moment, or head somewhere quieter.',
        'dot'      => 'bg-rose-500',
        'pillBg'   => 'bg-rose-50',
        'pillText' => 'text-rose-700',
        'pillRing' => 'ring-rose-200',
        'glow'     => 'from-rose-200/40 via-amber-200/30 to-violet-200/30',
    ],
    503 => [
        'eyebrow'  => '503 · Maintenance',
        'title'    => 'Be right back.',
        'accent'   => 'We\'re shipping a patch.',
        'message'  => 'Quick maintenance in progress. The site should be back in a few minutes — try refreshing shortly.',
        'dot'      => 'bg-sky-500',
        'pillBg'   => 'bg-sky-50',
        'pillText' => 'text-sky-700',
        'pillRing' => 'ring-sky-200',
        'glow'     => 'from-sky-200/40 via-cyan-200/30 to-indigo-200/30',
    ],
];

$v = $variants[$statusCode] ?? [
    'eyebrow'  => $statusCode ? $statusCode . ' · Error' : 'Error',
    'title'    => 'Something went wrong.',
    'accent'   => '',
    'message'  => 'We\'re not entirely sure what happened. Try going back home — and let us know if it keeps happening.',
    'dot'      => 'bg-fg-subtle',
    'pillBg'   => 'bg-fg/5',
    'pillText' => 'text-fg-muted',
    'pillRing' => 'ring-fg/10',
    'glow'     => 'from-slate-200/40 via-zinc-200/30 to-stone-200/30',
];

$this->title = $v['eyebrow'] . ' — ' . Yii::$app->params['meta-title'];
$this->params['description'] = $v['message'];
?>

<section class="relative left-1/2 w-screen -ml-[50vw] -mt-10 sm:-mt-14 overflow-hidden bg-gradient-to-b from-slate-50/30 to-canvas pt-16 pb-12 sm:pt-24 sm:pb-20">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 h-[520px] w-[520px] rounded-full bg-gradient-to-br <?= $v['glow'] ?> opacity-50 blur-3xl"></div>
        <div class="absolute -bottom-20 right-0 h-[420px] w-[420px] rounded-full bg-gradient-to-br <?= $v['glow'] ?> opacity-30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
        <p class="fade-up inline-flex items-center gap-2 rounded-full <?= $v['pillBg'] ?> ring-1 <?= $v['pillRing'] ?> px-3 py-1 text-xs font-medium <?= $v['pillText'] ?>"
           style="animation-delay: 0.05s">
            <span class="h-1.5 w-1.5 rounded-full <?= $v['dot'] ?>"></span>
            <?= Html::encode($v['eyebrow']) ?>
        </p>

        <h1 class="fade-up mt-6 font-display text-5xl sm:text-6xl lg:text-7xl font-bold text-fg tracking-tight leading-[1.02]"
            style="animation-delay: 0.15s">
            <?= Html::encode($v['title']) ?>
            <?php if ($v['accent']): ?>
                <br><span class="text-accent"><?= Html::encode($v['accent']) ?></span>
            <?php endif; ?>
        </h1>

        <p class="fade-up mt-6 max-w-xl mx-auto text-lg text-fg-muted leading-relaxed"
           style="animation-delay: 0.3s">
            <?= Html::encode($v['message']) ?>
        </p>

        <div class="fade-up mt-10 flex flex-wrap items-center justify-center gap-3"
             style="animation-delay: 0.45s">
            <a href="<?= Url::to(['/games']) ?>"
               class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
                Browse games <span aria-hidden="true">→</span>
            </a>
            <button type="button"
                    data-search-trigger
                    class="inline-flex items-center gap-2 rounded-full bg-canvas text-fg px-6 py-3 text-sm font-medium ring-1 ring-line hover:ring-line-strong hover:bg-surface transition">
                Try a search
            </button>
            <a href="<?= Yii::$app->homeUrl ?>"
               class="inline-flex items-center gap-2 rounded-full text-fg-muted px-4 py-3 text-sm font-medium hover:text-fg transition">
                Back home
            </a>
        </div>

        <?php if ($statusCode === 404 && (!empty($genres) || !empty($tags))): ?>
            <div class="fade-up mt-16 max-w-2xl mx-auto" style="animation-delay: 0.6s">
                <div class="flex items-center gap-3 mb-5">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">Maybe try one of these</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>

                <?php if (!empty($genres)): ?>
                    <p class="text-left text-xs text-fg-subtle font-mono mb-2">Genres</p>
                    <div class="flex flex-wrap justify-start gap-2 mb-6">
                        <?php foreach ($genres as $genre): ?>
                            <a href="<?= Url::to(['/games/' . $genre->slug]) ?>"
                               class="inline-flex items-center text-xs font-medium text-fg-muted bg-canvas border border-line rounded-full px-3 py-1.5 hover:border-line-strong hover:text-fg hover:bg-surface transition">
                                <?= Html::encode($genre->name) ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= Url::to(['/genres']) ?>"
                           class="inline-flex items-center gap-1 text-xs font-medium text-accent rounded-full px-3 py-1.5 hover:underline">
                            All <span aria-hidden="true">→</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($tags)): ?>
                    <p class="text-left text-xs text-fg-subtle font-mono mb-2">Tags</p>
                    <div class="flex flex-wrap justify-start gap-2">
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?= Url::to(['/game/game/list-by-tag', 'slug' => $tag->slug]) ?>"
                               class="inline-flex items-center gap-2 text-xs font-medium text-fg-muted bg-canvas border border-line rounded-full px-3 py-1.5 hover:border-line-strong hover:text-fg hover:bg-surface transition">
                                <?= Html::encode($tag->name) ?>
                                <span class="font-mono text-[10px] tabular-nums text-fg-subtle"><?= number_format((int)$tag->games_count) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= Url::to(['/tags']) ?>"
                           class="inline-flex items-center gap-1 text-xs font-medium text-accent rounded-full px-3 py-1.5 hover:underline">
                            All <span aria-hidden="true">→</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (YII_DEBUG && !empty($message) && $message !== $v['message']): ?>
            <div class="fade-up mt-14 mx-auto max-w-2xl rounded-2xl bg-surface/60 ring-1 ring-line p-5 text-left"
                 style="animation-delay: 0.75s">
                <div class="flex items-center gap-2 mb-3">
                    <span class="font-mono text-[10px] uppercase tracking-[0.2em] text-fg-subtle">Debug · only visible in dev</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>
                <p class="font-mono text-xs text-fg-muted whitespace-pre-wrap break-words">
                    <?= nl2br(Html::encode($message)) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>
