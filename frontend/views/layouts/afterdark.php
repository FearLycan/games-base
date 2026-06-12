<?php

use common\enums\AfterDarkLayout;
use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Standalone layout for the After Dark (18+) area. Self-contained theming: the
 * member's chosen {@see AfterDarkLayout} redefines the Tailwind @theme tokens,
 * so every token-driven partial (tiles, pager, chips) re-skins automatically.
 *
 * Always emits robots=noindex — this area is intentionally kept out of search.
 *
 * @var string         $content
 * @var \yii\web\View  $this
 */

AppAsset::register($this);

/** @var \common\models\User $identity */
$identity = Yii::$app->user->identity;
$theme = $identity->getAfterDarkLayout();

// Per-theme presentation config. CSS lives in the layout (presentation layer);
// the enum stays a small value object.
$fonts = match ($theme) {
    AfterDarkLayout::NeonPlum  => 'Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600',
    AfterDarkLayout::LightRose => 'Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500',
    AfterDarkLayout::BlackGold => 'Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600',
};

$themeTokens = match ($theme) {
    AfterDarkLayout::NeonPlum => <<<CSS
        --color-canvas:#0a0510; --color-surface:#140a1d; --color-surface-2:#1d1029;
        --color-line:#2c1a3d; --color-line-strong:#422a5a;
        --color-fg:#f6edf7; --color-fg-muted:#b79dc7; --color-fg-subtle:#7d6390;
        --color-accent:#ff2d78; --color-accent-2:#b026ff; --color-accent-soft:#2a0f23;
        --font-display:"Cormorant Garamond", Georgia, serif;
    CSS,
    AfterDarkLayout::LightRose => <<<CSS
        --color-canvas:#fffafb; --color-surface:#fdf2f5; --color-surface-2:#f9e6ec;
        --color-line:#f3d9e1; --color-line-strong:#e7bccb;
        --color-fg:#2a0e1a; --color-fg-muted:#7a5560; --color-fg-subtle:#b08a96;
        --color-accent:#c81d5a; --color-accent-2:#8e1346; --color-accent-soft:#fdeaf1;
        --font-display:"Fraunces", Georgia, serif;
    CSS,
    AfterDarkLayout::BlackGold => <<<CSS
        --color-canvas:#0a0805; --color-surface:#14110a; --color-surface-2:#1e1810;
        --color-line:#2c2415; --color-line-strong:#463a22;
        --color-fg:#f4ecdc; --color-fg-muted:#b7a888; --color-fg-subtle:#7d7155;
        --color-accent:#d4af37; --color-accent-2:#f1d57a; --color-accent-soft:#1c1708;
        --font-display:"Playfair Display", Georgia, serif;
    CSS,
};

// Theme-specific atmosphere (backgrounds, decorative utilities). Shared
// utilities (fade-up, cover overlay, card hover) are appended for every theme.
$themeExtras = match ($theme) {
    AfterDarkLayout::NeonPlum => <<<CSS
        body{background-color:var(--color-canvas);background-image:
            radial-gradient(60rem 50rem at 15% -10%, rgba(176,38,255,.18), transparent 60%),
            radial-gradient(55rem 45rem at 100% 0%, rgba(255,45,120,.16), transparent 55%),
            radial-gradient(45rem 40rem at 50% 120%, rgba(176,38,255,.10), transparent 60%);}
        .neon-text{text-shadow:0 0 26px rgba(255,45,120,.45),0 0 60px rgba(176,38,255,.30);}
        @keyframes glow-pulse{0%,100%{opacity:.55}50%{opacity:1}}
        .glow-pulse{animation:glow-pulse 4s ease-in-out infinite;}
    CSS,
    AfterDarkLayout::LightRose => <<<CSS
        body{background-color:var(--color-canvas);background-image:
            radial-gradient(50rem 40rem at 100% -5%, rgba(200,29,90,.07), transparent 60%),
            radial-gradient(45rem 38rem at 0% 5%, rgba(142,19,70,.05), transparent 60%);}
    CSS,
    AfterDarkLayout::BlackGold => <<<CSS
        body{background-color:var(--color-canvas);background-image:
            radial-gradient(55rem 45rem at 50% -10%, rgba(212,175,55,.10), transparent 60%),
            radial-gradient(40rem 35rem at 100% 100%, rgba(212,175,55,.05), transparent 60%);}
        @keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
        .gold-text{background:linear-gradient(100deg,#b8902b,#f1d57a 30%,#fff3c9 50%,#f1d57a 70%,#b8902b);
            background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent;
            animation:shimmer 6s linear infinite;}
        .hairline{border-image:linear-gradient(90deg,transparent,rgba(212,175,55,.5),transparent) 1;}
    CSS,
};
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-full">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= Html::encode($this->title) ?></title>

    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=<?= $fonts ?>&family=Lexend:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap">

    <style type="text/tailwindcss">
        @theme {
            <?= $themeTokens ?>

            --font-body: "Lexend", system-ui, sans-serif;
            --font-mono: "Fira Code", ui-monospace, monospace;
        }

        @keyframes fade-up { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        @utility fade-up { animation: fade-up 0.85s cubic-bezier(0.16,1,0.3,1) forwards; opacity: 0; }

        <?= $themeExtras ?>


        .cover { position: relative; isolation: isolate; }
        .cover::after { content:""; position:absolute; inset:0; z-index:2;
            background:linear-gradient(180deg, transparent 38%, color-mix(in srgb, var(--color-canvas) 88%, transparent) 100%); }
        .card { transition: transform .35s cubic-bezier(0.16,1,0.3,1), box-shadow .35s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 18px 44px -18px color-mix(in srgb, var(--color-accent) 55%, transparent); }
    </style>

    <?php $this->registerCsrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body class="min-h-full flex flex-col font-body bg-canvas text-fg antialiased overflow-x-hidden selection:bg-accent/25">
<?php $this->beginBody() ?>

<header class="sticky top-0 z-30 border-b border-line bg-canvas/70 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <a href="<?= Url::to(['/after-dark/index']) ?>" class="flex items-center gap-2 shrink-0">
            <span class="grid h-8 w-8 place-items-center rounded-lg text-white font-bold text-sm" style="background:<?= $theme->swatch() ?>"><?= Html::encode(mb_substr(Yii::$app->name, 0, 1)) ?></span>
            <span class="font-body font-semibold text-lg text-fg hidden sm:inline tracking-tight"><?= Html::encode(Yii::$app->name) ?></span>
            <span class="hidden sm:inline font-mono text-[10px] uppercase tracking-[0.2em] text-accent">After Dark</span>
        </a>

        <button type="button" data-search-trigger aria-label="Open search"
                class="group flex-1 max-w-md inline-flex items-center gap-2 h-9 px-3 rounded-full border border-line bg-surface/60 hover:border-line-strong transition text-fg-subtle text-sm">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <span class="flex-1 text-left truncate">Search games, genres, studios…</span>
        </button>

        <nav class="flex items-center gap-5 text-sm font-medium text-fg-muted shrink-0">
            <a href="<?= Url::to(['/games']) ?>" class="hidden sm:inline hover:text-fg transition">← Main site</a>
            <a href="<?= Url::to(['/user/profile/settings']) ?>" aria-label="Settings" class="grid h-9 w-9 place-items-center rounded-lg border border-line hover:border-line-strong hover:text-fg transition">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </a>
            <a href="<?= Url::to(['/user/profile/index']) ?>" class="shrink-0">
                <?php if ($identity->steam_avatar): ?>
                    <img src="<?= Html::encode($identity->steam_avatar) ?>" alt="" width="32" height="32" class="h-8 w-8 rounded-full object-cover outline outline-1 -outline-offset-1 outline-white/15">
                <?php else: ?>
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-surface-2 text-xs font-semibold text-fg"><?= Html::encode(mb_strtoupper(mb_substr($identity->username, 0, 1))) ?></span>
                <?php endif; ?>
            </a>
        </nav>
    </div>
</header>

<div data-search-modal
     data-search-url="<?= Url::to(['/autocomplete/search']) ?>"
     data-trending-url="<?= Url::to(['/autocomplete/trending']) ?>"
     data-track-url="<?= Url::to(['/autocomplete/track']) ?>"
     hidden
     class="fixed inset-0 z-50 px-4 sm:px-6 pt-[8vh] sm:pt-[12vh] bg-black/50 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-labelledby="search-modal-title">
    <div data-search-panel class="mx-auto w-full max-w-2xl rounded-2xl bg-canvas border border-line shadow-2xl overflow-hidden">
        <div class="flex items-center gap-3 px-5 h-14 border-b border-line">
            <svg class="h-5 w-5 text-fg-subtle shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" data-search-input id="search-modal-title" placeholder="Search games, genres, publishers, developers…" autocomplete="off" spellcheck="false"
                   class="flex-1 h-full bg-transparent border-0 outline-none text-[15px] text-fg placeholder:text-fg-subtle">
            <div data-search-spinner hidden class="h-4 w-4 rounded-full border-2 border-line border-t-accent shrink-0"></div>
        </div>
        <div data-search-body class="max-h-[60vh] overflow-y-auto">
            <div data-search-state="idle" class="px-6 py-10 text-center text-sm text-fg-subtle">
                <p class="font-medium text-fg">Start typing to search</p>
                <p class="mt-1">Find titles, genres, categories, publishers and developers.</p>
            </div>
            <div data-search-state="empty" hidden class="px-6 py-10 text-center text-sm text-fg-subtle">
                <p class="font-medium text-fg">No matches</p>
            </div>
            <div data-search-results hidden></div>
        </div>
    </div>
</div>

<main class="flex-1">
    <?php if (Yii::$app->session->hasFlash('success') || Yii::$app->session->hasFlash('info') || Yii::$app->session->hasFlash('error')): ?>
        <div class="mx-auto max-w-7xl px-6 lg:px-8 pt-6">
            <?= Alert::widget([
                'alertTypes' => [
                    'success' => 'rounded-xl border border-accent/30 bg-accent-soft text-fg px-4 py-3',
                    'info'    => 'rounded-xl border border-line-strong bg-surface text-fg-muted px-4 py-3',
                    'error'   => 'rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 px-4 py-3',
                ],
            ]) ?>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>

<footer class="border-t border-line mt-24 bg-surface/30">
    <div class="mx-auto max-w-7xl px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-5 text-sm text-fg-muted">
        <div class="flex items-center gap-2">
            <span class="grid h-6 w-6 place-items-center rounded-md text-white font-bold text-[10px]" style="background:<?= $theme->swatch() ?>"><?= Html::encode(mb_substr(Yii::$app->name, 0, 1)) ?></span>
            © <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?> · After Dark
        </div>
        <p class="font-mono text-[11px] text-fg-subtle order-last sm:order-none">Members only. What you browse here stays private.</p>
        <?= $this->render('@frontend/views/after-dark/_switcher', ['current' => $theme]) ?>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
