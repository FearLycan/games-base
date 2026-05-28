<?php

use frontend\modules\game\models\Game;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this View */
/* @var $model Game */
/* @var $gameViewButton bool */

$reviewPercent = $model->review ? $model->review->getPercentsOfPositive() : 0;
$negativePercent = $model->review ? $model->review->getPercentsOfNegative() : 0;

$steamDeckMap = [
    Game::STEAM_DECK_VERIFIED    => ['label' => 'Verified',    'cls' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
    Game::STEAM_DECK_PLAYABLE    => ['label' => 'Playable',    'cls' => 'bg-amber-50 text-amber-700 ring-amber-200'],
    Game::STEAM_DECK_UNSUPPORTED => ['label' => 'Unsupported', 'cls' => 'bg-rose-50 text-rose-700 ring-rose-200'],
];

$linkList = function (array $models, string $base) {
    if (empty($models)) {
        return '<span class="text-fg-subtle">—</span>';
    }
    $parts = [];
    foreach ($models as $m) {
        $parts[] = '<a href="' . Html::encode($base . $m->slug) . '" class="text-accent hover:underline">' . Html::encode($m->name) . '</a>';
    }
    return implode(', ', $parts);
};
?>

<div class="game-details space-y-5" data-key="<?= $model->id ?>">

    <div class="rounded-2xl border border-line bg-canvas overflow-hidden shadow-sm">
        <img src="<?= Html::encode($model->getHeader()) ?>"
             alt="<?= Html::encode($model->title) ?>"
             loading="lazy"
             class="w-full aspect-[460/215] object-cover">

        <div class="p-5">
            <h3 class="font-display text-lg font-semibold text-fg leading-tight">
                <?= Html::encode($model->title) ?>
            </h3>

            <dl class="mt-4 divide-y divide-line/70 text-sm">
                <?php if (!empty($model->genres)): ?>
                    <div class="flex items-start gap-4 py-2.5">
                        <dt class="w-24 shrink-0 text-fg-subtle">Genre</dt>
                        <dd class="flex-1 text-right text-fg"><?= $linkList($model->genres, '/games/') ?></dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($model->studios)): ?>
                    <div class="flex items-start gap-4 py-2.5">
                        <dt class="w-24 shrink-0 text-fg-subtle" title="Developer & Publisher">Studio</dt>
                        <dd class="flex-1 text-right text-fg"><?= $linkList($model->studios, '/company/') ?></dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($model->devOnly)): ?>
                    <div class="flex items-start gap-4 py-2.5">
                        <dt class="w-24 shrink-0 text-fg-subtle">Developer</dt>
                        <dd class="flex-1 text-right text-fg"><?= $linkList($model->devOnly, '/developer/') ?></dd>
                    </div>
                <?php endif; ?>

                <?php if (!empty($model->pubOnly)): ?>
                    <div class="flex items-start gap-4 py-2.5">
                        <dt class="w-24 shrink-0 text-fg-subtle">Publisher</dt>
                        <dd class="flex-1 text-right text-fg"><?= $linkList($model->pubOnly, '/publisher/') ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ($model->release_date): ?>
                    <div class="flex items-start gap-4 py-2.5">
                        <dt class="w-24 shrink-0 text-fg-subtle">Release</dt>
                        <dd class="flex-1 text-right text-fg font-mono text-xs">
                            <?= (new DateTime($model->release_date))->format('d M Y') ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php $availablePlatforms = $model->getAvailablePlatforms(); ?>
                <?php if (!empty($availablePlatforms)): ?>
                    <div class="flex items-start gap-4 py-2.5">
                        <dt class="w-24 shrink-0 text-fg-subtle">Platforms</dt>
                        <dd class="flex-1 text-right text-fg-muted flex justify-end items-center gap-2.5">
                            <?php $platformIcons = ['windows' => 'fa-windows', 'mac' => 'fa-apple', 'linux' => 'fa-linux']; ?>
                            <?php foreach ($availablePlatforms as $platform): ?>
                                <span class="inline-flex items-center"
                                      title="<?= ucfirst(Html::encode($platform->name)) ?>"
                                      aria-label="<?= ucfirst(Html::encode($platform->name)) ?>">
                                    <?php if (isset($platformIcons[$platform->slug])): ?>
                                        <i class="fa-brands <?= $platformIcons[$platform->slug] ?> text-base leading-none" aria-hidden="true"></i>
                                    <?php else: ?>
                                        <span class="text-xs font-mono"><?= Html::encode(strtoupper(substr($platform->name, 0, 3))) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>

        <?php if ($model->review && $model->review->total_reviews): ?>
            <div class="border-t border-line p-5">
                <div class="flex items-baseline justify-between">
                    <h4 class="font-display text-sm font-semibold text-fg">Steam reviews</h4>
                    <span class="font-mono text-[11px] text-fg-subtle"><?= number_format($model->review->total_reviews) ?></span>
                </div>
                <p class="mt-1 text-sm text-fg-muted">
                    <?= Html::encode($model->review->description ?: '—') ?>
                </p>
                <div class="reviews-bar mt-3" title="<?= $reviewPercent ?>% positive">
                    <span class="reviews-bar-positive" style="width: <?= $reviewPercent ?>%"></span>
                    <span class="reviews-bar-negative" style="width: <?= $negativePercent ?>%"></span>
                </div>
                <div class="mt-2 flex items-center justify-between font-mono text-[11px]">
                    <span class="text-accent"><?= $reviewPercent ?>% positive</span>
                    <span class="text-rose-600"><?= $negativePercent ?>% negative</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($model->metacritic && ($model->metacritic->score || $model->metacritic->user_score)): ?>
            <div class="border-t border-line p-5">
                <div class="grid grid-cols-2 gap-4">
                    <?php if ($model->metacritic->score): ?>
                        <div class="flex flex-col items-center">
                            <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle mb-2">Metacritic</span>
                            <div class="score-ring"
                                 style="--value: <?= (int)$model->metacritic->score ?>; --color: <?= Html::encode($model->metacritic->getScoreColor((int)$model->metacritic->score)) ?>"
                                 role="progressbar"
                                 aria-valuenow="<?= (int)$model->metacritic->score ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                <span><?= (int)$model->metacritic->score ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($model->metacritic->user_score): ?>
                        <div class="flex flex-col items-center">
                            <span class="font-mono text-[10px] uppercase tracking-wider text-fg-subtle mb-2">User score</span>
                            <div class="score-ring"
                                 style="--value: <?= (int)$model->metacritic->user_score ?>; --color: <?= Html::encode($model->metacritic->getScoreColor((int)$model->metacritic->user_score)) ?>"
                                 role="progressbar"
                                 aria-valuenow="<?= (int)$model->metacritic->user_score ?>"
                                 aria-valuemin="0"
                                 aria-valuemax="100">
                                <span><?= (int)$model->metacritic->user_score ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($model->categories)): ?>
        <div class="rounded-2xl border border-line bg-canvas p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Includes</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>
            <ul class="space-y-1">
                <?php foreach ($model->categories as $category): ?>
                    <li>
                        <a href="<?= Url::to(['/games/' . $category->slug]) ?>"
                           class="flex items-center gap-3 rounded-lg px-2 py-1.5 text-sm text-fg-muted hover:bg-surface hover:text-fg transition group">
                            <span class="grid h-8 w-8 place-items-center rounded-md bg-fg shrink-0 group-hover:bg-accent transition-colors">
                                <img src="<?= Html::encode($category->getImage()) ?>"
                                     alt=""
                                     loading="lazy"
                                     class="h-[18px] w-[18px] object-contain">
                            </span>
                            <span class="flex-1 truncate"><?= Html::encode($category->name) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($model->steam_deck && isset($steamDeckMap[$model->steam_deck])): ?>
        <?php $deck = $steamDeckMap[$model->steam_deck]; ?>
        <div class="rounded-2xl border border-line bg-canvas p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Steam Deck</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>
            <span class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium ring-1 <?= $deck['cls'] ?>">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                <?= Html::encode($deck['label']) ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if (!empty($model->gameTags)): ?>
        <div class="rounded-2xl border border-line bg-canvas p-5 shadow-sm">
            <div class="flex items-center gap-2 mb-3">
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Popular tags</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($model->gameTags as $idx => $gameTag): ?>
                    <a href="<?= Url::to(['/game/game/list-by-tag', 'slug' => $gameTag->tag->slug]) ?>"
                       class="tag-chip"
                       title="Browse games tagged <?= Html::encode($gameTag->tag->name) ?>"
                       <?= $idx >= 6 ? 'data-tag-hidden hidden' : '' ?>>
                        <?= Html::encode($gameTag->tag->name) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (count($model->gameTags) > 6): ?>
                <button type="button"
                        data-tag-toggle
                        data-expanded="false"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-accent hover:underline">
                    <span data-tag-more>Show all <?= count($model->gameTags) ?></span>
                    <span data-tag-less hidden>Show less</span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($gameViewButton)): ?>
        <a href="<?= Url::to(['game/view', 'id' => $model->steam_appid, 'slug' => $model->slug]) ?>"
           class="block text-center rounded-xl bg-fg text-canvas px-4 py-3 text-sm font-semibold hover:bg-fg/90 transition shadow-sm">
            Go to game page →
        </a>
    <?php else: ?>
        <?php $priceLabel = $model->getPriceLabel(); $discount = $model->getDiscountPercent(); ?>
        <a href="<?= Html::encode($model->getSteamUrl()) ?>"
           target="_blank"
           rel="nofollow noopener external"
           class="flex items-center justify-center gap-2.5 rounded-xl bg-accent text-white px-4 py-3.5 text-sm font-semibold hover:bg-accent/90 transition shadow-sm">
            <i class="fa-brands fa-steam text-lg leading-none" aria-hidden="true"></i>
            View on Steam
            <?php if ($priceLabel !== null): ?>
                <span class="text-white/80">·</span>
                <span><?= $discount > 0 ? '-' . $discount . '% ' : '' ?><?= Html::encode($priceLabel) ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>
</div>
