<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $filter frontend\modules\user\models\AchievementsFilter */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $total int */
/* @var $games array<int,string> game id => title */

use frontend\modules\user\models\AchievementsFilter;
use yii\helpers\Html;
use yii\widgets\ListView;

$this->title = 'Achievements';
$this->params['breadcrumbs'][] = ['label' => 'Account', 'url' => ['/user/profile/index']];
$this->params['breadcrumbs'][] = $this->title;

$shown = $dataProvider->getTotalCount();

$chevron = '<svg class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
$selectClass = 'h-10 max-w-[14rem] appearance-none rounded-lg border border-line bg-canvas pl-3 pr-9 text-sm text-fg shadow-sm outline-none transition-colors focus:border-accent focus:ring-2 focus:ring-accent/20';
?>
<?php $this->beginContent('@frontend/modules/user/views/layouts/account.php'); ?>

    <div class="mb-6 fade-up" style="animation-delay:.05s">
        <h1 class="font-display text-2xl font-semibold text-fg text-balance">
            Achievements
            <?php if ($total): ?>
                <span class="ml-1 align-middle text-base font-medium text-fg-subtle tabular-nums">(<?= number_format($total) ?>)</span>
            <?php endif; ?>
        </h1>
        <p class="mt-1 text-sm text-fg-muted">Every achievement you've unlocked across your library.</p>
    </div>

    <?php if ($total === 0 && !$filter->isActive()): ?>
        <div class="rounded-2xl border border-dashed border-line-strong bg-surface/40 px-6 py-14 text-center fade-up" style="animation-delay:.12s">
            <p class="font-display text-lg font-semibold text-fg">No achievements yet</p>
            <p class="mx-auto mt-1.5 max-w-sm text-sm text-fg-muted text-pretty">As your games' achievements sync, everything you've unlocked will show up here.</p>
        </div>
    <?php else: ?>
        <?= Html::beginForm(['/user/profile/achievements'], 'get', [
            'class' => 'mb-6 flex flex-wrap items-center gap-2 fade-up',
            'style' => 'animation-delay:.12s',
            'role'  => 'search',
        ]) ?>
            <label class="relative min-w-[14rem] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="q" value="<?= Html::encode($filter->q) ?>" placeholder="Search achievements or games" aria-label="Search achievements"
                       class="h-10 w-full rounded-lg border border-line bg-canvas pl-9 pr-3 text-sm text-fg shadow-sm outline-none transition-colors focus:border-accent focus:ring-2 focus:ring-accent/20">
            </label>

            <div class="relative">
                <?= Html::dropDownList('game', $filter->game, ['' => 'All games'] + $games, ['class' => $selectClass, 'onchange' => 'this.form.submit()', 'aria-label' => 'Filter by game']) ?>
                <?= $chevron ?>
            </div>

            <div class="relative">
                <?= Html::dropDownList('rarity', $filter->rarity, AchievementsFilter::rarityOptions(), ['class' => $selectClass, 'onchange' => 'this.form.submit()', 'aria-label' => 'Filter by rarity']) ?>
                <?= $chevron ?>
            </div>

            <div class="relative">
                <?= Html::dropDownList('sort', $filter->sort, AchievementsFilter::SORT_OPTIONS, ['class' => $selectClass, 'onchange' => 'this.form.submit()', 'aria-label' => 'Sort achievements']) ?>
                <?= $chevron ?>
            </div>
        <?= Html::endForm() ?>

        <?php if ($filter->isActive() && $shown > 0): ?>
            <p class="mb-3 text-sm text-fg-subtle tabular-nums"><?= number_format($shown) ?> result<?= $shown === 1 ? '' : 's' ?></p>
        <?php endif; ?>

        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView'     => '_achievement-tile',
            'summary'      => false,
            'layout'       => "<div class=\"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4\">{items}</div>\n{pager}",
            'itemOptions'  => ['tag' => 'div'],
            'emptyText'    => '<div class="rounded-2xl border border-dashed border-line-strong bg-surface/40 px-6 py-14 text-center"><p class="font-display text-lg font-semibold text-fg">No matches</p><p class="mt-1.5 text-sm text-fg-muted">No achievements match your filters. ' . Html::a('Clear', ['/user/profile/achievements'], ['class' => 'font-medium text-accent hover:underline']) . '</p></div>',
            'pager'        => ['class' => \common\widgets\Pager::class],
        ]) ?>
    <?php endif; ?>

<?php $this->endContent(); ?>
