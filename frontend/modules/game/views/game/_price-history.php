<?php

use common\models\GameOfferPrice;
use yii\web\View;

/* @var $this View */
/* @var $series array<int, array{price_final:string, recorded_at:string}> */
/* @var $currency string */

// Dependency-free price chart from the cheapest offer's recorded history.
// Caller guarantees at least two points.
$finals = array_map(static fn($r): int => (int)$r['price_final'], $series);
$n = count($finals);
$min = min($finals);
$max = max($finals);
$range = $max - $min;

$w = 600;
$h = 140;
$pad = 10;
$plotW = $w - 2 * $pad;
$plotH = $h - 2 * $pad;

$coords = [];
foreach ($finals as $i => $v) {
    $x = $pad + ($n > 1 ? $i / ($n - 1) * $plotW : $plotW / 2);
    $y = $pad + ($range > 0 ? (1 - ($v - $min) / $range) * $plotH : 0.5) * ($range > 0 ? 1 : $plotH);
    $coords[] = [round($x, 1), round($y, 1)];
}
$line = implode(' ', array_map(static fn($c): string => $c[0] . ',' . $c[1], $coords));
$area = $line . ' ' . $coords[$n - 1][0] . ',' . ($h - $pad) . ' ' . $coords[0][0] . ',' . ($h - $pad);

$current = $finals[$n - 1];
$atLow = $current <= $min;
$currentStr = GameOfferPrice::formatCents($current, $currency);
$lowStr = GameOfferPrice::formatCents($min, $currency);
$highStr = GameOfferPrice::formatCents($max, $currency);
$firstDate = date('d M Y', strtotime($series[0]['recorded_at']));
$lastDate = date('d M Y', strtotime($series[$n - 1]['recorded_at']));
?>

<div class="rounded-2xl border border-line bg-canvas p-5 shadow-sm mt-5">
    <div class="flex items-center gap-2 mb-4">
        <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Price history</span>
        <span class="h-px flex-1 bg-line"></span>
        <?php if ($atLow): ?>
            <span class="inline-flex items-center gap-1 rounded-md bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v12"></path><path d="m6 12 6 6 6-6"></path><path d="M5 21h14"></path></svg>
                Lowest ever
            </span>
        <?php endif; ?>
    </div>

    <svg viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="none" class="w-full h-32" role="img"
         aria-label="Price history: now <?= htmlspecialchars($currentStr) ?>, lowest <?= htmlspecialchars($lowStr) ?>, highest <?= htmlspecialchars($highStr) ?>">
        <defs>
            <linearGradient id="ph-fill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#059669" stop-opacity="0.18"></stop>
                <stop offset="100%" stop-color="#059669" stop-opacity="0"></stop>
            </linearGradient>
        </defs>
        <polygon points="<?= $area ?>" fill="url(#ph-fill)"></polygon>
        <polyline points="<?= $line ?>" fill="none" stroke="#059669" stroke-width="2"
                  vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"></polyline>
    </svg>

    <div class="mt-3 flex items-center justify-between font-mono text-[11px] text-fg-subtle">
        <span><?= $firstDate ?></span>
        <span class="text-fg-muted">Now <span class="font-semibold text-fg tabular-nums"><?= htmlspecialchars($currentStr) ?></span> · Low <span class="tabular-nums"><?= htmlspecialchars($lowStr) ?></span></span>
        <span><?= $lastDate ?></span>
    </div>
</div>
