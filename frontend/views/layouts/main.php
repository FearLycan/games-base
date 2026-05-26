<?php

/* @var $this \yii\web\View */

/* @var $content string */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

AppAsset::register($this);
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

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Gamentator",
            "description": "<?= Yii::$app->params['meta-description'] ?>",
            "url": "https://gamentator.com",
            "logo": "<?= Yii::$app->params['og_image']['content'] ?>"
        }
    </script>

    <title><?= Html::encode($this->title) ?></title>
    <meta name="title" content="<?= Html::encode($this->title) ?>"/>
    <meta name="description" content="<?= Html::encode(Yii::$app->params['meta-description']) ?>"/>

    <meta property="og:type" content="website"/>
    <meta property="og:url" content="<?= Yii::$app->request->absoluteUrl ?>"/>
    <meta property="og:title" content="<?= Html::encode($this->title) ?>"/>
    <meta property="og:description" content="<?= Html::encode(Yii::$app->params['meta-description']) ?>"/>
    <meta property="og:image" content="<?= Yii::$app->params['og_image']['content'] ?>"/>

    <meta property="twitter:card" content="summary_large_image"/>
    <meta property="twitter:url" content="<?= Yii::$app->request->absoluteUrl ?>"/>
    <meta property="twitter:title" content="<?= Html::encode($this->title) ?>"/>
    <meta property="twitter:description" content="<?= Html::encode(Yii::$app->params['meta-description']) ?>"/>
    <meta property="twitter:image" content="<?= Yii::$app->params['og_image']['content'] ?>"/>

    <link rel="canonical" href="<?= Yii::$app->request->absoluteUrl ?>"/>

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
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @utility fade-up {
            animation: fade-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        @keyframes soft-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.7; transform: scale(0.92); }
        }

        @utility soft-pulse {
            animation: soft-pulse 2.4s ease-in-out infinite;
        }
    </style>

    <?php $this->head() ?>
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
            <a href="#" class="hover:text-fg transition">Browse</a>
            <a href="#" class="hover:text-fg transition">Genres</a>
            <a href="#" class="hover:text-fg transition">New</a>
            <a href="#" class="hover:text-fg transition">About</a>
        </nav>
    </div>
</header>

<div data-search-modal
     data-search-url="<?= yii\helpers\Url::to(['/autocomplete/search']) ?>"
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
                    <li><a href="/developers" class="text-fg-muted hover:text-fg transition">Developers</a></li>
                    <li><a href="/publishers" class="text-fg-muted hover:text-fg transition">Publishers</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Discover</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/" class="text-fg-muted hover:text-fg transition">Home</a></li>
                    <li><a href="#" class="text-fg-muted hover:text-fg transition">Bestsellers</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="text-fg-muted hover:text-fg transition">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-mono text-[10px] uppercase tracking-[0.18em] text-fg-subtle mb-4">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="text-fg-muted hover:text-fg transition">Terms</a></li>
                    <li><a href="#" class="text-fg-muted hover:text-fg transition">Privacy</a></li>
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
</body>
</html>
<?php $this->endPage();
