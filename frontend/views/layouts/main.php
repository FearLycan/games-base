<?php

use common\schema\factory\BreadcrumbListSchemaFactory;
use common\schema\factory\OrganizationSchemaFactory;
use common\schema\JsonLdRenderer;
use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var $content string
 * @var $this    \yii\web\View
 */


AppAsset::register($this);

$schemaNodes = [OrganizationSchemaFactory::fromParams()];
if (!empty($this->params['breadcrumbs'])) {
    $breadcrumbSchema = BreadcrumbListSchemaFactory::fromView(
            $this->params['breadcrumbs'],
            ['label' => 'Home', 'url' => Yii::$app->homeUrl]
    );
    if ($breadcrumbSchema !== []) {
        $schemaNodes[] = $breadcrumbSchema;
    }
}
?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>" class="h-full">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">

        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/android-chrome-512x512.png">
        <link rel="manifest" href="/site.webmanifest">
        <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#059669">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="theme-color" content="#ffffff">

        <?= JsonLdRenderer::render($schemaNodes) ?>

        <?php
        $pageDescription = $this->params['description'] ?? Yii::$app->params['meta-description'];
        $pageOgImage = $this->params['og_image'] ?? Yii::$app->params['og_image']['content'];
        // Canonical without the query string so sort/filter/page/role variants
        // all consolidate to one indexable URL per page.
        $canonicalUrl = Yii::$app->request->hostInfo . (parse_url(Yii::$app->request->url, PHP_URL_PATH) ?: '/');
        ?>
        <title><?= Html::encode($this->title) ?></title>
        <meta name="title" content="<?= Html::encode($this->title) ?>"/>
        <meta name="description" content="<?= Html::encode($pageDescription) ?>"/>

        <meta property="og:type" content="website"/>
        <meta property="og:url" content="<?= Html::encode($canonicalUrl) ?>"/>
        <meta property="og:title" content="<?= Html::encode($this->title) ?>"/>
        <meta property="og:description" content="<?= Html::encode($pageDescription) ?>"/>
        <meta property="og:image" content="<?= Html::encode($pageOgImage) ?>"/>

        <meta property="twitter:card" content="summary_large_image"/>
        <meta property="twitter:url" content="<?= Html::encode($canonicalUrl) ?>"/>
        <meta property="twitter:title" content="<?= Html::encode($this->title) ?>"/>
        <meta property="twitter:description" content="<?= Html::encode($pageDescription) ?>"/>
        <meta property="twitter:image" content="<?= Html::encode($pageOgImage) ?>"/>

        <link rel="canonical" href="<?= Html::encode($canonicalUrl) ?>"/>

        <?php $this->registerCsrfMetaTags() ?>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet"
              href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap">

        <style type="text/tailwindcss">
            @theme {
                --color-canvas: #ffffff;
                --color-surface: #f8fafc;
                --color-surface-2: #f1f5f9;
                --color-line: #e2e8f0;
                --color-line-strong: #cbd5e1;
                --color-fg: #0f172a;
                --color-fg-muted: #475569;
                --color-fg-subtle: #94a3b8;
                --color-accent: #059669;
                --color-accent-soft: #ecfdf5;
                --font-display: "Lexend", system-ui, sans-serif;
                --font-body: "Lexend", system-ui, sans-serif;
                --font-mono: "Fira Code", ui-monospace, monospace;
            }

            @keyframes fade-up {
                from {
                    opacity: 0;
                    transform: translateY(8px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @utility fade-up {
                animation: fade-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                opacity: 0;
            }

            @keyframes soft-pulse {
                0%, 100% {
                    opacity: 1;
                    transform: scale(1);
                }
                50% {
                    opacity: 0.7;
                    transform: scale(0.92);
                }
            }

            @utility soft-pulse {
                animation: soft-pulse 2.4s ease-in-out infinite;
            }
        </style>

        <?php $this->head() ?>

        <?php if (isset(Yii::$app->params['leadTag']) && Yii::$app->params['leadTag']): ?>
            <meta name="mylead-verification" content="<?= Yii::$app->params['leadTag'] ?>">
        <?php endif; ?>

        <?php if (isset(Yii::$app->params['impactTag']) && Yii::$app->params['impactTag']): ?>
            <meta name="impact-site-verification"
                  value="<?= Yii::$app->params['impactTag'] ?>"
                  content="<?= Yii::$app->params['impactTag'] ?>">
        <?php endif; ?>

        <?php if (isset(Yii::$app->params['gtag']) && Yii::$app->params['gtag']): ?>
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?= Yii::$app->params['gtag'] ?>"></script>
            <script>
                window.dataLayer = window.dataLayer || [];

                function gtag() {
                    dataLayer.push(arguments);
                }

                gtag('js', new Date());
                gtag('config', '<?= Yii::$app->params['gtag'] ?>');
            </script>
        <?php endif; ?>

        <?php if (isset(Yii::$app->params['pagead2']) && Yii::$app->params['pagead2']): ?>
            <meta name="google-adsense-account" content="<?= Yii::$app->params['pagead2'] ?>">
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= Yii::$app->params['pagead2'] ?>" crossorigin="anonymous"></script>
        <?php endif; ?>

    </head>
    <body class="min-h-full flex flex-col font-body bg-canvas text-fg antialiased overflow-x-hidden selection:bg-accent/20 selection:text-fg">
    <?php $this->beginBody() ?>

    <header class="sticky top-0 z-30 border-b border-line bg-canvas/80 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="<?= Yii::$app->homeUrl ?>" class="flex items-center gap-2 group shrink-0">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-fg text-canvas font-bold text-sm group-hover:bg-accent transition">G</span>
                <span class="font-display font-semibold text-lg text-fg hidden sm:inline">
                <?= Html::encode(Yii::$app->name ?: 'Gamentator') ?>
            </span>
            </a>

            <button type="button"
                    data-search-trigger
                    aria-label="Open search"
                    class="group flex-1 max-w-md inline-flex items-center gap-2 h-9 px-3 rounded-full border border-line bg-surface/60 hover:bg-surface-2 hover:border-line-strong transition text-fg-subtle text-sm">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span class="flex-1 text-left truncate">Search games, genres, studios…</span>
                <span class="hidden sm:inline-flex items-center gap-1">
                <span class="search-kbd">Ctrl</span>
                <span class="search-kbd">K</span>
            </span>
            </button>

            <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-fg-muted shrink-0">
                <a href="<?= Url::to(['/games']) ?>" class="hover:text-fg transition">Games</a>
                <a href="<?= Url::to(['/genres']) ?>" class="hover:text-fg transition">Genres</a>
                <a href="<?= Url::to(['/tags']) ?>" class="hover:text-fg transition">Tags</a>
                <?php if (Yii::$app->user->isGuest): ?>
                    <a href="<?= Url::to(['/user/auth/login']) ?>" class="hover:text-fg transition">Login</a>
                <?php else: ?>
                    <?php $identity = Yii::$app->user->identity; ?>
                    <div class="relative" data-user-menu>
                        <button type="button"
                                data-user-menu-button
                                aria-haspopup="true"
                                aria-expanded="false"
                                class="flex h-9 items-center gap-2 rounded-full border border-line bg-surface/60 py-1 pl-1 pr-2.5 text-fg transition-colors hover:border-line-strong hover:bg-surface-2 cursor-pointer">
                            <?php if ($identity->steam_avatar): ?>
                                <img src="<?= Html::encode($identity->steam_avatar) ?>" alt="" width="28" height="28"
                                     class="h-7 w-7 rounded-full object-cover outline outline-1 -outline-offset-1 outline-black/10">
                            <?php else: ?>
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-fg text-canvas text-xs font-semibold"><?= Html::encode(mb_strtoupper(mb_substr($identity->username, 0, 1))) ?></span>
                            <?php endif; ?>
                            <span class="max-w-[9rem] truncate text-sm font-medium"><?= Html::encode($identity->username) ?></span>
                            <svg data-user-menu-chevron class="h-4 w-4 shrink-0 text-fg-subtle transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </button>

                        <div data-user-menu-panel
                             role="menu"
                             aria-hidden="true"
                             class="absolute right-0 mt-2 w-64 origin-top-right -translate-y-1 rounded-xl border border-line bg-canvas p-1.5 opacity-0 pointer-events-none shadow-[0_4px_6px_-2px_rgba(15,23,42,.08),0_14px_30px_-10px_rgba(15,23,42,.22)] transition duration-150 ease-out will-change-transform z-50">
                            <div class="flex items-center gap-3 px-2.5 py-2">
                                <?php if ($identity->steam_avatar): ?>
                                    <img src="<?= Html::encode($identity->steam_avatar) ?>" alt="" width="36" height="36"
                                         class="h-9 w-9 rounded-lg object-cover outline outline-1 -outline-offset-1 outline-black/10">
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-fg"><?= Html::encode($identity->username) ?></p>
                                    <?php if ($identity->steam_profile_url): ?>
                                        <a href="<?= Html::encode($identity->steam_profile_url) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-fg-subtle hover:text-accent transition-colors">Steam profile ↗</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="my-1 border-t border-line"></div>

                            <a href="<?= Url::to(['/user/profile/index']) ?>" role="menuitem" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg">
                                <svg class="h-[18px] w-[18px] shrink-0 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M5.5 21a8.38 8.38 0 0 1 13 0"></path></svg>
                                Profile
                            </a>
                            <a href="<?= Url::to(['/user/profile/library']) ?>" role="menuitem" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg">
                                <svg class="h-[18px] w-[18px] shrink-0 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
                                Library
                            </a>
                            <a href="<?= Url::to(['/user/profile/wishlist']) ?>" role="menuitem" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg">
                                <svg class="h-[18px] w-[18px] shrink-0 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A3.5 3.5 0 0 0 12 6 3.5 3.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path></svg>
                                Wishlist
                            </a>
                            <a href="<?= Url::to(['/user/profile/achievements']) ?>" role="menuitem" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg">
                                <svg class="h-[18px] w-[18px] shrink-0 text-fg-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path></svg>
                                Achievements
                            </a>

                            <div class="my-1 border-t border-line"></div>

                            <a href="<?= Url::to(['/user/profile/settings']) ?>" role="menuitem" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-fg-muted transition-colors hover:bg-surface-2 hover:text-fg">
                                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                Settings
                            </a>
                            <?= Html::beginForm(['/user/auth/logout'], 'post') ?>
                                <button type="submit" role="menuitem" class="flex w-full cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2 text-left text-sm text-fg-muted transition-colors hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path></svg>
                                    Sign out
                                </button>
                            <?= Html::endForm() ?>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>

            <button type="button"
                    data-menu-toggle
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    class="lg:hidden shrink-0 grid h-9 w-9 place-items-center rounded-lg border border-line text-fg-muted hover:bg-surface-2 hover:text-fg transition">
                <svg data-menu-icon-open class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="7" x2="20" y2="7"></line>
                    <line x1="4" y1="12" x2="20" y2="12"></line>
                    <line x1="4" y1="17" x2="20" y2="17"></line>
                </svg>
                <svg data-menu-icon-close hidden class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                    <line x1="6" y1="18" x2="18" y2="6"></line>
                </svg>
            </button>
        </div>

        <nav id="mobile-menu"
             data-menu-panel
             hidden
             aria-label="Mobile"
             class="lg:hidden border-t border-line bg-canvas">
            <div class="mx-auto max-w-7xl px-6 py-3 flex flex-col text-sm font-medium text-fg-muted">
                <a href="<?= Url::to(['/games']) ?>" class="py-2.5 hover:text-fg transition">Games</a>
                <a href="<?= Url::to(['/genres']) ?>" class="py-2.5 hover:text-fg transition">Genres</a>
                <a href="<?= Url::to(['/tags']) ?>" class="py-2.5 hover:text-fg transition">Tags</a>
                <?php if (Yii::$app->user->isGuest): ?>
                    <a href="<?= Url::to(['/user/auth/login']) ?>" class="py-2.5 hover:text-fg transition">Login</a>
                <?php else: ?>
                    <?php $mIdentity = Yii::$app->user->identity; ?>
                    <div class="mt-2 flex items-center gap-3 border-t border-line pt-3">
                        <?php if ($mIdentity->steam_avatar): ?>
                            <img src="<?= Html::encode($mIdentity->steam_avatar) ?>" alt="" width="32" height="32"
                                 class="h-8 w-8 rounded-full object-cover outline outline-1 -outline-offset-1 outline-black/10">
                        <?php else: ?>
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-fg text-canvas text-xs font-semibold"><?= Html::encode(mb_strtoupper(mb_substr($mIdentity->username, 0, 1))) ?></span>
                        <?php endif; ?>
                        <span class="truncate text-sm font-semibold text-fg"><?= Html::encode($mIdentity->username) ?></span>
                    </div>
                    <a href="<?= Url::to(['/user/profile/index']) ?>" class="py-2.5 hover:text-fg transition">Profile</a>
                    <a href="<?= Url::to(['/user/profile/library']) ?>" class="py-2.5 hover:text-fg transition">Library</a>
                    <a href="<?= Url::to(['/user/profile/wishlist']) ?>" class="py-2.5 hover:text-fg transition">Wishlist</a>
                    <a href="<?= Url::to(['/user/profile/achievements']) ?>" class="py-2.5 hover:text-fg transition">Achievements</a>
                    <a href="<?= Url::to(['/user/profile/settings']) ?>" class="py-2.5 hover:text-fg transition">Account settings</a>
                    <?= Html::beginForm(['/user/auth/logout'], 'post', ['class' => 'py-2.5']) ?>
                        <button type="submit" class="cursor-pointer text-left text-red-600 hover:text-red-700 transition">Sign out</button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div data-search-modal
         data-search-url="<?= Url::to(['/autocomplete/search']) ?>"
         data-trending-url="<?= Url::to(['/autocomplete/trending']) ?>"
         data-track-url="<?= Url::to(['/autocomplete/track']) ?>"
         hidden
         class="fixed inset-0 z-50 px-4 sm:px-6 pt-[8vh] sm:pt-[12vh] bg-fg/40 backdrop-blur-sm"
         role="dialog"
         aria-modal="true"
         aria-labelledby="search-modal-title">
        <div data-search-panel
             class="mx-auto w-full max-w-2xl rounded-2xl bg-canvas border border-line shadow-2xl shadow-fg/10 overflow-hidden">
            <div class="flex items-center gap-3 px-5 h-14 border-b border-line">
                <svg class="h-5 w-5 text-fg-subtle shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text"
                       data-search-input
                       id="search-modal-title"
                       placeholder="Search games, genres, publishers, developers…"
                       autocomplete="off"
                       spellcheck="false"
                       class="flex-1 h-full bg-transparent border-0 outline-none text-[15px] text-fg placeholder:text-fg-subtle">
                <div data-search-spinner hidden class="h-4 w-4 rounded-full border-2 border-line border-t-accent shrink-0"></div>
                <button type="button"
                        data-search-close
                        aria-label="Close search"
                        class="hidden sm:inline-flex items-center justify-center h-7 px-2 rounded-md text-[11px] font-medium text-fg-muted hover:bg-surface-2 transition gap-1">
                    <span class="search-kbd">Esc</span>
                </button>
            </div>

            <div data-search-body class="max-h-[60vh] overflow-y-auto">
                <div data-search-state="idle" class="px-6 py-10 text-center text-sm text-fg-subtle">
                    <div class="mx-auto mb-4 grid h-10 w-10 place-items-center rounded-xl bg-surface-2 text-fg-muted">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <p class="font-medium text-fg">Start typing to search</p>
                    <p class="mt-1">Find titles, genres, categories, publishers and developers.</p>
                </div>

                <div data-search-state="empty" hidden class="px-6 py-10 text-center text-sm text-fg-subtle">
                    <p class="font-medium text-fg">No matches</p>
                    <p class="mt-1">Try a different keyword or check the spelling.</p>
                </div>

                <div data-search-results hidden></div>
            </div>

            <div class="hidden sm:flex items-center justify-between gap-4 px-5 h-10 border-t border-line bg-surface/40 text-[11px] text-fg-muted">
                <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5">
                    <span class="search-kbd">↑</span>
                    <span class="search-kbd">↓</span>
                    <span>navigate</span>
                </span>
                    <span class="inline-flex items-center gap-1.5">
                    <span class="search-kbd">↵</span>
                    <span>open</span>
                </span>
                    <span class="inline-flex items-center gap-1.5">
                    <span class="search-kbd">Esc</span>
                    <span>close</span>
                </span>
                </div>
                <span class="font-mono uppercase tracking-wider text-fg-subtle">Search</span>
            </div>
        </div>
    </div>

    <main class="flex-1">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <?php
                $crumbs = $this->params['breadcrumbs'];
                $homeIcon = '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12l9-9 9 9"></path><path d="M5 10v10h14V10"></path></svg><span class="sr-only">Home</span>';
                $items = [];
                $items[] = ['label' => $homeIcon, 'url' => Yii::$app->homeUrl, 'encode' => false];
                foreach ($crumbs as $crumb) {
                    $items[] = is_array($crumb) ? $crumb : ['label' => $crumb];
                }
                $lastIndex = count($items) - 1;
                ?>
                <nav class="crumbs" aria-label="Breadcrumb">
                    <?php foreach ($items as $i => $item): ?>
                        <?php
                        $rawLabel = $item['label'] ?? '';
                        $label = (isset($item['encode']) && $item['encode'] === false) ? $rawLabel : Html::encode($rawLabel);
                        $isActive = $i === $lastIndex;
                        ?>
                        <?php if ($isActive): ?>
                            <span class="crumb crumb-active" aria-current="page"><?= $label ?></span>
                        <?php elseif (!empty($item['url'])): ?>
                            <span class="crumb"><a href="<?= Html::encode(yii\helpers\Url::to($item['url'])) ?>"><?= $label ?></a></span>
                        <?php else: ?>
                            <span class="crumb"><?= $label ?></span>
                        <?php endif; ?>
                        <?php if (!$isActive): ?>
                            <span class="crumb-sep" aria-hidden="true">/</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <?= Alert::widget([
                    'alertTypes' => [
                            'error'   => 'rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 mb-4',
                            'danger'  => 'rounded-xl border border-red-200 bg-red-50 text-red-800 px-4 py-3 mb-4',
                            'success' => 'rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 mb-4',
                            'info'    => 'rounded-xl border border-sky-200 bg-sky-50 text-sky-800 px-4 py-3 mb-4',
                            'warning' => 'rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 mb-4',
                    ],
            ]) ?>
            <?= $content ?>
        </div>
    </main>

    <footer class="border-t border-line mt-24 bg-surface/40">
        <div class="mx-auto max-w-7xl px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-8 mb-10">
                <div>
                    <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Browse</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= Url::to(['/games']) ?>" class="text-fg-muted hover:text-fg transition">All games</a></li>
                        <li><a href="<?= Url::to(['/genres']) ?>" class="text-fg-muted hover:text-fg transition">Genres</a></li>
                        <li><a href="<?= Url::to(['/tags']) ?>" class="text-fg-muted hover:text-fg transition">Tags</a></li>
                        <li><a href="<?= Url::to(['/categories']) ?>" class="text-fg-muted hover:text-fg transition">Features</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Studios</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= Url::to(['/developers']) ?>" class="text-fg-muted hover:text-fg transition developers">Developers</a></li>
                        <li><a href="<?= Url::to(['/publishers']) ?>" class="text-fg-muted hover:text-fg transition publishers">Publishers</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Discover</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= Url::to(['/games/bestsellers']) ?>" class="text-fg-muted hover:text-fg transition bestsellers">Bestsellers</a></li>
                        <li><a href="<?= Url::to(['/games/new-and-noteworthy']) ?>" class="text-fg-muted hover:text-fg transition new-and-noteworthy">New &amp; Noteworthy</a></li>
                        <li><a href="<?= Url::to(['/games/upcoming']) ?>" class="text-fg-muted hover:text-fg transition upcoming">Upcoming</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">About</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= Url::to(['/how-it-works']) ?>" class="text-fg-muted hover:text-fg transition how-it-works">How it works</a></li>
                        <li><a href="<?= Url::to(['/contact']) ?>" class="text-fg-muted hover:text-fg transition contact">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-6 border-t border-line/60 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-fg-muted">
                <div class="flex items-center gap-2">
                    <span class="grid h-6 w-6 place-items-center rounded-md bg-fg text-canvas font-bold text-[10px]">G</span>
                    © <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?>. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <?php $this->endBody() ?>

    <?php if (Yii::$app->user->isGuest && isset(Yii::$app->params['smart-links']['aliexpress']) && Yii::$app->params['smart-links']['aliexpress']): ?>
        <iframe src="<?= Yii::$app->params['smart-links']['aliexpress'] ?>" style="display:none;"></iframe>
    <?php endif; ?>
    </body>
    </html>
<?php $this->endPage();
