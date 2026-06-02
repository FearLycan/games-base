<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $stats frontend\modules\user\models\ProfileStats */

use yii\helpers\Html;

$this->title = 'Profile';
$this->params['breadcrumbs'][] = $this->title;

$topPlayed = $stats->topPlayed(8);
$recent = $stats->recentlyPlayed(8);
$perfect = $stats->perfectGames(8);
$genres = $stats->topGenresByPlaytime(8);
$rarest = $stats->rarestAchievement();
$completion = $stats->completionPercent();
$maxGenre = $genres ? max(array_column($genres, 'minutes')) : 0;

$rarity = $stats->rarityCounts();
$recentUnlocks = $stats->recentUnlocks(10);
$deals = $stats->wishlistDeals(8);
$recommended = $stats->recommendedGames(8);
$valueLabel = $stats->libraryValueLabel();

// Which jump-nav groups have anything to show.
$hasGames = $topPlayed || $recent || $perfect || $genres;
$hasAchievements = $recentUnlocks || ($rarest && $rarest->achievement);
$hasDiscover = $deals || $recommended;
$groups = [['id' => 'overview', 'label' => 'Overview']];
if ($hasGames)        { $groups[] = ['id' => 'games', 'label' => 'Games']; }
if ($hasAchievements) { $groups[] = ['id' => 'achievements', 'label' => 'Achievements']; }
if ($hasDiscover)     { $groups[] = ['id' => 'discover', 'label' => 'Discover']; }

/** Renders a small game grid section. @var common\models\UserGame[] $games */
$gameSection = function (string $title, array $games, string $titleClass = 'text-fg'): void {
    if ($games === []) {
        return;
    }
    echo '<section><h2 class="mb-4 font-display text-lg font-semibold ' . $titleClass . '">' . Html::encode($title) . '</h2>';
    echo '<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">';
    foreach ($games as $g) {
        echo $this->render('_game-tile', ['model' => $g]);
    }
    echo '</div></section>';
};
?>
<?php $this->beginContent('@frontend/modules/user/views/layouts/account.php'); ?>

    <?php if (!$stats->hasData()): ?>
        <div class="rounded-2xl border border-dashed border-line-strong bg-surface/40 px-6 py-16 text-center fade-up">
            <p class="font-display text-lg font-semibold text-fg">Your profile is warming up</p>
            <p class="mx-auto mt-1.5 max-w-sm text-sm text-fg-muted text-pretty">Once your Steam library finishes syncing, your stats, top games and achievements will appear here.</p>
        </div>
    <?php else: ?>

        <?php if (count($groups) > 1): ?>
            <nav data-scrollspy
                 class="sticky top-16 z-20 -mx-1 mb-8 flex gap-1 overflow-x-auto border-b border-line bg-canvas/85 px-1 py-2 backdrop-blur fade-up"
                 aria-label="Profile sections">
                <?php foreach ($groups as $i => $grp): ?>
                    <a href="#<?= $grp['id'] ?>" data-scrollspy-link="<?= $grp['id'] ?>" data-active="<?= $i === 0 ? 'true' : 'false' ?>"
                       class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium text-fg-muted transition-colors hover:text-fg data-[active=true]:bg-surface-2 data-[active=true]:text-fg">
                        <?= Html::encode($grp['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="space-y-14">

            <!-- Overview -->
            <section id="overview" class="scroll-mt-32 space-y-8">
                <div class="flex items-center gap-4 fade-up">
                    <?php if ($user->steam_avatar): ?>
                        <img src="<?= Html::encode($user->steam_avatar) ?>" alt=""
                             class="rounded-2xl object-cover outline outline-1 -outline-offset-1 outline-black/10" style="height:72px;width:72px">
                    <?php endif; ?>
                    <div class="min-w-0">
                        <h1 class="font-display text-2xl font-semibold text-fg text-balance truncate"><?= Html::encode($user->username) ?></h1>
                        <p class="mt-0.5 text-sm text-fg-muted">
                            Member since <?= Html::encode(Yii::$app->formatter->asDate($user->created_at, 'MMMM y')) ?>
                            <?php if ($user->steam_profile_url): ?>
                                · <a href="<?= Html::encode($user->steam_profile_url) ?>" target="_blank" rel="noopener noreferrer" class="text-accent hover:underline">Steam profile ↗</a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <?php
                    $cards = [
                        ['Games', number_format($stats->gamesCount()), 'text-fg'],
                        ['Hours played', number_format($stats->playtimeHours()), 'text-fg'],
                        ['Achievements', number_format($stats->achievementsUnlocked()), 'text-fg'],
                        ['Perfect games', number_format($stats->perfectCount()), $stats->perfectCount() > 0 ? 'text-amber-500' : 'text-fg'],
                        ['Ultra-rare unlocks', number_format($rarity['ultra']), $rarity['ultra'] > 0 ? 'text-violet-500' : 'text-fg'],
                        ['Backlog', $stats->backlogPercent() . '%', 'text-fg'],
                        ['Est. value', $valueLabel ?? '—', 'text-fg'],
                    ];
                    foreach ($cards as [$label, $value, $valueClass]): ?>
                        <div class="rounded-2xl border border-line bg-surface/40 p-5">
                            <div class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle"><?= $label ?></div>
                            <div class="mt-1 font-display text-3xl font-semibold tabular-nums <?= $valueClass ?>"><?= $value ?></div>
                        </div>
                    <?php endforeach; ?>

                    <div class="rounded-2xl border border-line bg-surface/40 p-5 flex items-center gap-4">
                        <div class="relative h-14 w-14 shrink-0">
                            <svg viewBox="0 0 36 36" class="h-14 w-14 -rotate-90" aria-hidden="true">
                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="var(--color-line,#e2e8f0)" stroke-width="3"></circle>
                                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="var(--color-accent,#059669)" stroke-width="3" stroke-linecap="round" stroke-dasharray="<?= $completion ?> 100"></circle>
                            </svg>
                            <span class="absolute inset-0 grid place-items-center text-xs font-semibold tabular-nums text-fg"><?= $completion ?>%</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle">Completion</div>
                            <div class="mt-0.5 text-sm text-fg-muted">across <?= number_format($stats->achievementGamesCount()) ?> games</div>
                        </div>
                    </div>
                </div>
            </section>

            <?php if ($hasGames): ?>
                <!-- Games -->
                <section id="games" class="scroll-mt-32 space-y-10">
                    <?php $gameSection('Most played', $topPlayed); ?>
                    <?php $gameSection('Recently played', $recent); ?>
                    <?php $gameSection('100% completed', $perfect, 'text-amber-500'); ?>

                    <?php if ($genres !== []): ?>
                        <section>
                            <h2 class="mb-4 font-display text-lg font-semibold text-fg">Top genres <span class="text-sm font-normal text-fg-subtle">by playtime</span></h2>
                            <div class="rounded-2xl border border-line bg-surface/40 p-5 space-y-3">
                                <?php foreach ($genres as $genre): ?>
                                    <div class="flex items-center gap-3">
                                        <span class="w-32 shrink-0 truncate text-sm text-fg-muted"><?= Html::encode($genre['name']) ?></span>
                                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-line">
                                            <span class="block h-full rounded-full bg-accent" style="width:<?= $maxGenre > 0 ? round((int)$genre['minutes'] / $maxGenre * 100) : 0 ?>%"></span>
                                        </span>
                                        <span class="w-12 shrink-0 text-right text-xs font-medium text-fg-subtle tabular-nums"><?= number_format(round((int)$genre['minutes'] / 60)) ?>h</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($hasAchievements): ?>
                <!-- Achievements -->
                <section id="achievements" class="scroll-mt-32 space-y-10">
                    <?php if ($recentUnlocks !== []): ?>
                        <section>
                            <h2 class="mb-4 font-display text-lg font-semibold text-fg">Recent unlocks</h2>
                            <div class="overflow-hidden rounded-2xl border border-line bg-surface/40 divide-y divide-line">
                                <?php foreach ($recentUnlocks as $ua): ?>
                                    <?php $a = $ua->achievement; if ($a === null) { continue; } ?>
                                    <div class="flex items-center gap-3 px-4 py-3">
                                        <?php if ($a->icon): ?>
                                            <img src="<?= Html::encode($a->icon) ?>" alt="" width="36" height="36" class="h-9 w-9 shrink-0 rounded-lg object-cover outline outline-1 -outline-offset-1 outline-black/10">
                                        <?php endif; ?>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-fg"><?= Html::encode($a->name) ?></p>
                                            <p class="truncate text-xs text-fg-subtle"><?= Html::encode($ua->game->title ?? '') ?></p>
                                        </div>
                                        <?php if ($ua->unlocked_at): ?>
                                            <span class="shrink-0 text-xs text-fg-subtle"><?= Html::encode(Yii::$app->formatter->asRelativeTime($ua->unlocked_at)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($rarest !== null && $rarest->achievement !== null): ?>
                        <section>
                            <h2 class="mb-4 font-display text-lg font-semibold text-fg">Rarest unlock</h2>
                            <div class="flex items-center gap-4 rounded-2xl border border-line bg-surface/40 p-5">
                                <?php if ($rarest->achievement->icon): ?>
                                    <img src="<?= Html::encode($rarest->achievement->icon) ?>" alt="" width="56" height="56"
                                         class="h-14 w-14 shrink-0 rounded-xl object-cover outline outline-1 -outline-offset-1 outline-black/10">
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-fg"><?= Html::encode($rarest->achievement->name) ?></p>
                                    <p class="mt-0.5 truncate text-sm text-fg-muted">
                                        <?= Html::encode($rarest->game->title ?? '') ?>
                                        <?php if ($rarest->achievement->getPercentLabel()): ?>
                                            · <span class="font-medium text-accent"><?= Html::encode($rarest->achievement->getPercentLabel()) ?></span> of players
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($hasDiscover): ?>
                <!-- Discover -->
                <section id="discover" class="scroll-mt-32 space-y-10">
                    <?php if ($deals !== []): ?>
                        <section>
                            <h2 class="mb-4 font-display text-lg font-semibold text-fg">Wishlist deals <span class="text-sm font-normal text-fg-subtle">on sale now</span></h2>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                <?php foreach ($deals as $d): ?>
                                    <?= $this->render('_wishlist-tile', ['model' => $d]) ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if ($recommended !== []): ?>
                        <section>
                            <h2 class="mb-4 font-display text-lg font-semibold text-fg">Recommended for you <span class="text-sm font-normal text-fg-subtle">based on what you play</span></h2>
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                <?php foreach ($recommended as $rg): ?>
                                    <?= $this->render('_catalog-tile', ['model' => $rg, 'reasons' => $stats->recommendationReasons($rg)]) ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

        </div>
    <?php endif; ?>

<?php $this->endContent(); ?>
