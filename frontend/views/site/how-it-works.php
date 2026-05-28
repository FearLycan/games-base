<?php

use yii\helpers\Url;
use yii\web\View;

/* @var $this View */

$this->title = 'How it works — ' . Yii::$app->params['meta-title'];
$this->params['breadcrumbs'][] = 'How it works';
$this->params['description'] = 'How Gamentator curates Steam games — where the data comes from, how often it refreshes, and what we filter out.';

$steps = [
    [
        'eyebrow' => 'Step 1',
        'dot'     => 'bg-emerald-500',
        'title'   => 'We pull from Steam, directly',
        'body'    => 'Every game, price, screenshot, and review count comes straight from the public Steam API and Steam store pages. No middlemen, no licensing deals — just the same data Valve publishes.',
    ],
    [
        'eyebrow' => 'Step 2',
        'dot'     => 'bg-sky-500',
        'title'   => 'Rankings refresh every few hours',
        'body'    => 'Bestsellers, new releases, and upcoming lists are rebuilt every six hours from Steam\'s own popularity signals. Catalog data — descriptions, tags, prices — refreshes daily.',
    ],
    [
        'eyebrow' => 'Step 3',
        'dot'     => 'bg-indigo-500',
        'title'   => 'We filter out the noise',
        'body'    => 'Asset flips, region-locked listings, DLC packs without context — those clutter Steam\'s native lists. We hide what we can, surface what looks worth a click.',
    ],
];

$facts = [
    ['label' => 'Data source',        'value' => 'Steam Web API + store scraping'],
    ['label' => 'Affiliate links',    'value' => 'None'],
    ['label' => 'Personalization',    'value' => 'None — same lists for everyone'],
    ['label' => 'Catalog refresh',    'value' => 'Daily'],
    ['label' => 'Ranking refresh',    'value' => 'Every 6 hours'],
    ['label' => 'Account required',   'value' => 'No'],
];
?>

<section class="relative left-1/2 w-screen -ml-[50vw] overflow-hidden bg-gradient-to-b from-slate-50/40 via-cyan-50/20 to-canvas pt-12 pb-14 sm:pt-16">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 -left-20 h-[460px] w-[460px] rounded-full bg-gradient-to-br from-cyan-200/40 to-sky-200/40 opacity-50 blur-3xl"></div>
        <div class="absolute -bottom-20 right-0 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-indigo-200/40 to-rose-200/40 opacity-30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-fg/5 ring-1 ring-fg/5 px-3 py-1 text-xs font-medium text-fg-muted">
            <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
            Behind the scenes
        </span>

        <h1 class="mt-6 font-display text-4xl sm:text-5xl font-bold text-fg tracking-tight leading-[1.05]">
            How <span class="text-accent">Gamentator</span> works
        </h1>
        <p class="mt-5 text-lg text-fg-muted leading-relaxed">
            Steam has the catalog. We have the patience to sort it. Here's exactly how that happens.
        </p>
    </div>
</section>

<section class="mt-16 max-w-3xl mx-auto px-4">
    <div class="space-y-6">
        <?php foreach ($steps as $step): ?>
            <div class="rounded-2xl bg-canvas ring-1 ring-line p-6 sm:p-8 hover:ring-line-strong transition">
                <div class="flex items-center gap-2 mb-3">
                    <span class="h-1.5 w-1.5 rounded-full <?= $step['dot'] ?>"></span>
                    <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">
                        <?= htmlspecialchars($step['eyebrow']) ?>
                    </span>
                </div>
                <h2 class="font-display text-xl sm:text-2xl font-bold text-fg tracking-tight">
                    <?= htmlspecialchars($step['title']) ?>
                </h2>
                <p class="mt-3 text-fg-muted leading-relaxed">
                    <?= htmlspecialchars($step['body']) ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-16 max-w-3xl mx-auto px-4">
    <div class="rounded-2xl bg-surface/60 ring-1 ring-line p-6 sm:p-8">
        <div class="flex items-center gap-2 mb-5">
            <span class="font-mono text-[11px] uppercase tracking-[0.2em] text-fg-subtle">Quick facts</span>
            <span class="h-px flex-1 bg-line"></span>
        </div>

        <dl class="divide-y divide-line">
            <?php foreach ($facts as $fact): ?>
                <div class="py-3 flex items-center justify-between gap-4">
                    <dt class="text-sm text-fg-muted"><?= htmlspecialchars($fact['label']) ?></dt>
                    <dd class="font-mono text-xs tabular-nums text-fg"><?= htmlspecialchars($fact['value']) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>

<section class="mt-16 mb-8 text-center">
    <p class="text-sm text-fg-muted mb-4">Ready to dig in?</p>
    <a href="<?= Url::to(['/games']) ?>"
       class="inline-flex items-center gap-2 rounded-full bg-fg text-canvas px-6 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
        Browse games <span aria-hidden="true">→</span>
    </a>
</section>
