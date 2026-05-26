<?php

namespace common\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Tailwind CSS v4 browser runtime, pinned per project skill (.claude/skills/tailwind/SKILL.md).
 *
 * Theme tokens and custom utilities should be defined in a
 * <style type="text/tailwindcss"> @theme { ... } </style> block (v4 CSS-first),
 * not in a tailwind.config.js.
 */
class TailwindAsset extends AssetBundle
{
    public $js = [
        ['https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.2.4', 'position' => View::POS_HEAD],
    ];
}
