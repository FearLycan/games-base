<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $filter frontend\modules\user\models\WishlistFilter */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $wishlistCount int */
/* @var $pendingCount int */

use frontend\modules\user\models\WishlistFilter;
use yii\helpers\Html;
use yii\widgets\ListView;

$this->title = 'Wishlist';
$this->params['breadcrumbs'][] = ['label' => 'Account', 'url' => ['/user/profile/index']];
$this->params['breadcrumbs'][] = $this->title;

$shown = $dataProvider->getTotalCount();

$chevron = '<svg class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
$selectClass = 'h-10 appearance-none rounded-lg border border-line bg-canvas pl-3 pr-9 text-sm text-fg shadow-sm outline-none transition-colors focus:border-accent focus:ring-2 focus:ring-accent/20';
?>
<?php $this->beginContent('@frontend/modules/user/views/layouts/account.php'); ?>

    <div class="mb-6 fade-up" style="animation-delay:.05s">
        <h1 class="font-display text-2xl font-semibold text-fg text-balance">
            Wishlist
            <?php if ($wishlistCount): ?>
                <span class="ml-1 align-middle text-base font-medium text-fg-subtle tabular-nums">(<?= $wishlistCount ?>)</span>
            <?php endif; ?>
        </h1>
        <p class="mt-1 text-sm text-fg-muted">
            <?php if ($user->isSteamLibrarySynced()): ?>
                Last synced <?= Html::encode(Yii::$app->formatter->asRelativeTime($user->steam_synced_at)) ?>.
            <?php else: ?>
                Sync queued — your wishlist will appear here after the next sync run.
            <?php endif; ?>
        </p>
    </div>

    <?php if ($user->isSteamLibrarySynced() && !$user->isSteamProfilePublic()): ?>
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 fade-up" style="animation-delay:.1s">
            Your Steam profile looks private, so we can't read your wishlist. In Steam → Profile → Edit Profile → Privacy, set it to <strong>Public</strong>, then sync again.
        </div>
    <?php endif; ?>

    <?php if ($pendingCount > 0): ?>
        <p class="mb-5 inline-flex items-center gap-2 rounded-lg bg-surface-2 px-3 py-2 text-sm text-fg-muted fade-up" style="animation-delay:.1s">
            <svg class="h-4 w-4 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-2.64-6.36"></path><path d="M21 3v6h-6"></path></svg>
            <?= $pendingCount ?> more <?= $pendingCount === 1 ? 'game is' : 'games are' ?> still syncing and will show up here soon.
        </p>
    <?php endif; ?>

    <?php if ($wishlistCount === 0): ?>
        <div class="rounded-2xl border border-dashed border-line-strong bg-surface/40 px-6 py-14 text-center fade-up" style="animation-delay:.12s">
            <p class="font-display text-lg font-semibold text-fg">Nothing wishlisted</p>
            <p class="mx-auto mt-1.5 max-w-sm text-sm text-fg-muted text-pretty">
                <?= $user->isSteamLibrarySynced()
                    ? 'We synced your profile but found no wishlisted games.'
                    : 'Hang tight — your wishlist is queued for its first sync.' ?>
            </p>
        </div>
    <?php else: ?>
        <?= Html::beginForm(['/user/profile/wishlist'], 'get', [
            'class' => 'mb-6 flex flex-wrap items-center gap-2 fade-up',
            'style' => 'animation-delay:.12s',
            'role'  => 'search',
        ]) ?>
            <label class="relative min-w-[14rem] flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="q" value="<?= Html::encode($filter->q) ?>" placeholder="Search your wishlist" aria-label="Search your wishlist"
                       class="h-10 w-full rounded-lg border border-line bg-canvas pl-9 pr-3 text-sm text-fg shadow-sm outline-none transition-colors focus:border-accent focus:ring-2 focus:ring-accent/20">
            </label>

            <div class="relative">
                <?= Html::dropDownList('sort', $filter->sort, WishlistFilter::SORT_OPTIONS, ['class' => $selectClass, 'onchange' => 'this.form.submit()', 'aria-label' => 'Sort wishlist']) ?>
                <?= $chevron ?>
            </div>
        <?= Html::endForm() ?>

        <?php if ($filter->isActive() && $shown > 0): ?>
            <p class="mb-3 text-sm text-fg-subtle tabular-nums"><?= $shown ?> result<?= $shown === 1 ? '' : 's' ?></p>
        <?php endif; ?>

        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView'     => '_wishlist-tile',
            'summary'      => false,
            'layout'       => "<div class=\"grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4\">{items}</div>\n{pager}",
            'itemOptions'  => ['tag' => 'div'],
            'emptyText'    => '<div class="rounded-2xl border border-dashed border-line-strong bg-surface/40 px-6 py-14 text-center"><p class="font-display text-lg font-semibold text-fg">No matches</p><p class="mt-1.5 text-sm text-fg-muted">No games match your search. ' . Html::a('Clear', ['/user/profile/wishlist'], ['class' => 'font-medium text-accent hover:underline']) . '</p></div>',
            'pager'        => ['class' => \common\widgets\Pager::class],
        ]) ?>
    <?php endif; ?>

<?php $this->endContent(); ?>
