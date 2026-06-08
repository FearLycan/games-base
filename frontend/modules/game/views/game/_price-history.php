<?php

use common\models\GameOfferPrice;
use yii\helpers\Json;
use yii\web\View;

/* @var $this View */
/* @var $chart array{
 *     currency:string,
 *     official:array<int,array{t:int,y:int}>,
 *     keyshop:array<int,array{t:int,y:int}>,
 *     officialLow:int|null,
 *     keyshopLow:int|null,
 *     currentLow:int,
 *     lowestEver:int,
 *     peak:int,
 *     atLow:bool
 * } */

$currency = $chart['currency'];
$hasOfficial = $chart['official'] !== [];
$hasKeyshop = $chart['keyshop'] !== [];

// Currency formatting handed to the client so axis/tooltips match formatCents().
$symbol = GameOfferPrice::symbolFor($currency);
$suffix = strtoupper($currency) === 'PLN';

$fmt = static fn(?int $c): ?string => $c === null ? null : GameOfferPrice::formatCents($c, $currency);

$officialLowStr = $fmt($chart['officialLow']);
$keyshopLowStr = $fmt($chart['keyshopLow']);
$currentLowStr = $fmt($chart['currentLow']);
$peakStr = $fmt($chart['peak']);

// "X% below peak" — the headline trust signal next to the live price.
$offPeak = ($chart['peak'] > 0 && $chart['currentLow'] < $chart['peak'])
    ? (int)round(($chart['peak'] - $chart['currentLow']) / $chart['peak'] * 100)
    : 0;

$officialColor = '#059669'; // emerald accent — official stores
$keyshopColor = '#f97316';  // amber — keyshops

$payload = Json::encode([
    'official' => $chart['official'],
    'keyshop'  => $chart['keyshop'],
    'symbol'   => $symbol,
    'suffix'   => $suffix,
    'colors'   => ['official' => $officialColor, 'keyshop' => $keyshopColor],
]);
?>

<section class="price-chart" data-price-chart aria-label="Price history">
    <span class="price-chart-glow" aria-hidden="true"></span>

    <header class="price-chart-head">
        <div class="price-chart-ticker">
            <span class="price-chart-kicker">Lowest price · live</span>
            <div class="price-chart-now">
                <span class="price-chart-amount tabular-nums"><?= htmlspecialchars($currentLowStr) ?></span>
                <?php if ($chart['atLow']): ?>
                    <span class="price-chart-flag is-low">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="m5 12 7 7 7-7"></path></svg>
                        Lowest ever
                    </span>
                <?php elseif ($offPeak > 0): ?>
                    <span class="price-chart-flag"><?= $offPeak ?>% below peak</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="price-chart-controls">
            <div class="price-chart-legend-group">
                <?php if ($hasOfficial): ?>
                    <button type="button" class="price-chart-legend" data-series="official" data-active="true"
                            style="--dot: <?= $officialColor ?>">
                        <span class="price-chart-dot"></span>Official
                    </button>
                <?php endif; ?>
                <?php if ($hasKeyshop): ?>
                    <button type="button" class="price-chart-legend" data-series="keyshop" data-active="true"
                            style="--dot: <?= $keyshopColor ?>">
                        <span class="price-chart-dot"></span>Keyshops
                    </button>
                <?php endif; ?>
            </div>

            <div class="price-chart-ranges" role="group" aria-label="Chart range">
                <?php foreach (['1m' => '1M', '3m' => '3M', '6m' => '6M', '1y' => '1Y', 'all' => 'All'] as $key => $label): ?>
                    <button type="button" class="price-chart-range" data-range="<?= $key ?>"
                            data-active="<?= $key === 'all' ? 'true' : 'false' ?>"><?= $label ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </header>

    <div class="price-chart-plot">
        <canvas data-price-chart-canvas role="img"
                aria-label="Price history chart of official store and keyshop prices over time"></canvas>
    </div>

    <dl class="price-chart-stats">
        <?php if ($officialLowStr !== null): ?>
            <div class="price-chart-stat" style="--tick: <?= $officialColor ?>">
                <dt>Official low</dt>
                <dd class="tabular-nums"><?= htmlspecialchars($officialLowStr) ?></dd>
            </div>
        <?php endif; ?>
        <?php if ($keyshopLowStr !== null): ?>
            <div class="price-chart-stat" style="--tick: <?= $keyshopColor ?>">
                <dt>Keyshop low</dt>
                <dd class="tabular-nums"><?= htmlspecialchars($keyshopLowStr) ?></dd>
            </div>
        <?php endif; ?>
        <?php if ($peakStr !== null): ?>
            <div class="price-chart-stat" style="--tick: var(--color-line-strong, #cbd5e1)">
                <dt>All-time peak</dt>
                <dd class="tabular-nums"><?= htmlspecialchars($peakStr) ?></dd>
            </div>
        <?php endif; ?>
    </dl>
</section>

<?php
$this->registerJsFile('@web/libs/chartjs/chart.umd.min.js', ['position' => View::POS_END]);
$this->registerJsFile('@web/libs/chartjs/chartjs-adapter-date-fns.bundle.min.js', ['position' => View::POS_END]);

$js = <<<JS
(function () {
    var root = document.querySelector('[data-price-chart]');
    var canvas = root && root.querySelector('[data-price-chart-canvas]');
    if (!root || !canvas || typeof Chart === 'undefined') { return; }

    var data = $payload;

    var fmt = function (cents) {
        var amount = (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return data.suffix ? amount + ' ' + data.symbol : data.symbol + amount;
    };

    var css = getComputedStyle(document.documentElement);
    var pick = function (name, fallback) { return (css.getPropertyValue(name) || fallback).trim(); };
    var line = pick('--color-line', '#e5e7eb');
    var lineStrong = pick('--color-line-strong', '#cbd5e1');
    var muted = pick('--color-fg-subtle', '#94a3b8');
    var ink = pick('--color-fg', '#0f172a');

    var hexToRgba = function (hex, a) {
        var n = parseInt(hex.slice(1), 16);
        return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
    };

    // Vertical gradient fill under each line — fades to nothing toward the axis.
    var fillFor = function (color) {
        return function (ctx) {
            var area = ctx.chart.chartArea;
            if (!area) { return 'transparent'; }
            var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
            g.addColorStop(0, hexToRgba(color, 0.20));
            g.addColorStop(1, hexToRgba(color, 0));
            return g;
        };
    };

    var makeSet = function (key, label, color) {
        return {
            label: label, key: key, data: data[key],
            borderColor: color, backgroundColor: fillFor(color),
            pointBackgroundColor: color, pointBorderColor: '#fff', pointBorderWidth: 2,
            stepped: true, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5,
            tension: 0, fill: 'start'
        };
    };

    var datasets = [];
    if (data.official.length) { datasets.push(makeSet('official', 'Official stores', data.colors.official)); }
    if (data.keyshop.length) { datasets.push(makeSet('keyshop', 'Keyshops', data.colors.keyshop)); }

    // Crosshair: a faint vertical guide tracking the hovered point.
    var crosshair = {
        id: 'phCrosshair',
        afterDatasetsDraw: function (chart) {
            var active = chart.tooltip && chart.tooltip.getActiveElements && chart.tooltip.getActiveElements();
            if (!active || !active.length) { return; }
            var x = active[0].element.x;
            var area = chart.chartArea, c = chart.ctx;
            c.save();
            c.beginPath();
            c.moveTo(x, area.top);
            c.lineTo(x, area.bottom);
            c.lineWidth = 1;
            c.setLineDash([3, 3]);
            c.strokeStyle = lineStrong;
            c.stroke();
            c.restore();
        }
    };

    var chart = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: { datasets: datasets },
        plugins: [crosshair],
        options: {
            parsing: { xAxisKey: 't', yAxisKey: 'y' },
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { top: 8 } },
            scales: {
                x: {
                    type: 'time',
                    time: { unit: 'month', tooltipFormat: 'PP', displayFormats: { month: 'MMM yyyy', day: 'd MMM' } },
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: muted, maxRotation: 0, autoSkip: true, font: { size: 11, family: 'Fira Code, monospace' } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: line, drawTicks: false, lineWidth: 1 },
                    border: { display: false },
                    ticks: {
                        color: muted, padding: 10, maxTicksLimit: 5,
                        font: { size: 11, family: 'Fira Code, monospace' },
                        callback: function (v) { return fmt(v); }
                    }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: ink,
                    padding: 12, cornerRadius: 10, displayColors: true, boxPadding: 5,
                    usePointStyle: true,
                    titleColor: '#fff', titleFont: { size: 11, weight: '600', family: 'Fira Code, monospace' },
                    bodyColor: '#e2e8f0', bodyFont: { size: 13, weight: '600' },
                    callbacks: { label: function (ctx) { return '  ' + ctx.dataset.label + '   ' + fmt(ctx.parsed.y); } }
                }
            }
        }
    });

    // Legend toggles — show/hide a series.
    root.querySelectorAll('.price-chart-legend').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idx = chart.data.datasets.findIndex(function (d) { return d.key === btn.dataset.series; });
            if (idx < 0) { return; }
            var visible = chart.isDatasetVisible(idx);
            chart.setDatasetVisibility(idx, !visible);
            btn.setAttribute('data-active', visible ? 'false' : 'true');
            chart.update();
        });
    });

    // Range selector — clamp the x axis to a trailing window.
    var ranges = { '1m': 1, '3m': 3, '6m': 6, '1y': 12, 'all': null };
    root.querySelectorAll('.price-chart-range').forEach(function (btn) {
        btn.addEventListener('click', function () {
            root.querySelectorAll('.price-chart-range').forEach(function (b) { b.setAttribute('data-active', 'false'); });
            btn.setAttribute('data-active', 'true');
            var months = ranges[btn.dataset.range];
            if (months === null) {
                chart.options.scales.x.min = undefined;
                chart.options.scales.x.max = undefined;
            } else {
                var d = new Date();
                chart.options.scales.x.max = d.getTime();
                d.setMonth(d.getMonth() - months);
                chart.options.scales.x.min = d.getTime();
            }
            chart.update();
        });
    });
})();
JS;
$this->registerJs($js, View::POS_END);
