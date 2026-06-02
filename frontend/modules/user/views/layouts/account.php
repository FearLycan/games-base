<?php

/* @var $this yii\web\View */

/* @var $content string */

use yii\helpers\Html;
use yii\helpers\Url;

// Active tab is derived from the action: add-email re-renders the settings page,
// and wishlist/achievements share the placeholder action ids.
$actionId = Yii::$app->controller->action->id;
$active = match ($actionId) {
    'index' => 'profile',
    'library' => 'library',
    'wishlist' => 'wishlist',
    'achievements' => 'achievements',
    default => 'settings',
};

$tabs = [
        ['key'  => 'profile', 'label' => 'Profile', 'url' => ['/user/profile/index'],
         'icon' => '<circle cx="12" cy="8" r="4"></circle><path d="M5.5 21a8.38 8.38 0 0 1 13 0"></path>'],
        ['key'  => 'library', 'label' => 'Library', 'url' => ['/user/profile/library'],
         'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>'],
        ['key'  => 'wishlist', 'label' => 'Wishlist', 'url' => ['/user/profile/wishlist'],
         'icon' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A3.5 3.5 0 0 0 12 6 3.5 3.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>'],
        ['key'  => 'achievements', 'label' => 'Achievements', 'url' => ['/user/profile/achievements'],
         'icon' => '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path>'],
        ['key'  => 'settings', 'label' => 'Settings', 'url' => ['/user/profile/settings'],
         'icon' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>'],
];
$identity = Yii::$app->user->identity;
$cooldown = $identity->getSyncCooldownLabel();
?>
<div>
    <div class="mb-8 flex items-end justify-between gap-4 border-b border-line fade-up" style="animation-delay:.04s;">
        <nav class="flex gap-1 overflow-x-auto -mx-1 px-1" aria-label="Account sections" style="overflow: hidden;">
            <?php foreach ($tabs as $tab): ?>
                <?php $isActive = $tab['key'] === $active; ?>
                <a href="<?= Url::to($tab['url']) ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>
                   class="group relative inline-flex shrink-0 items-center gap-2 whitespace-nowrap px-3 py-3 text-sm font-medium transition-colors <?= $isActive ? 'text-fg' : 'text-fg-muted hover:text-fg' ?>">
                    <svg class="h-[18px] w-[18px] <?= $isActive ? 'text-accent' : 'text-fg-subtle group-hover:text-fg-muted' ?> transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $tab['icon'] ?></svg>
                    <?= Html::encode($tab['label']) ?>
                    <span class="pointer-events-none absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-accent transition-opacity <?= $isActive ? 'opacity-100' : 'opacity-0' ?>"></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="hidden sm:block shrink-0 pb-2">
            <?php if ($cooldown === null): ?>
                <?= Html::beginForm(['/user/profile/sync'], 'post', ['class' => 'inline']) ?>
                <button type="submit"
                        class="inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-line bg-canvas px-3.5 text-sm font-medium text-fg shadow-sm transition-transform duration-150 will-change-transform hover:bg-surface-2 active:scale-[0.96]">
                    <svg class="h-4 w-4 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                        <path d="M21 3v6h-6"></path>
                    </svg>
                    Sync now
                </button>
                <?= Html::endForm() ?>
            <?php else: ?>
                <span class="inline-flex h-9 cursor-not-allowed items-center gap-2 rounded-lg border border-line bg-surface/60 px-3.5 text-sm font-medium text-fg-subtle"
                      title="Full sync available again in <?= Html::encode($cooldown) ?>">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
                    Sync in <?= Html::encode($cooldown) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?= $content ?>
</div>
