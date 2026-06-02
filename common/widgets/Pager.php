<?php

namespace common\widgets;

use yii\widgets\LinkPager;

/**
 * The site's standard pagination control — one consistent look everywhere.
 *
 * Encapsulates the Tailwind/`pager-*` styling (defined in site.css) so views
 * don't repeat the config. Use it as the ListView pager:
 *
 * ```php
 * ListView::widget([
 *     'dataProvider' => $dataProvider,
 *     'pager' => ['class' => \common\widgets\Pager::class],
 * ]);
 * ```
 *
 * or stand-alone: `Pager::widget(['pagination' => $dataProvider->getPagination()])`.
 */
class Pager extends LinkPager
{
    public function init()
    {
        $this->options = ['class' => 'mt-12 flex flex-wrap items-center justify-center gap-1.5 list-none p-0'];
        $this->linkContainerOptions = ['class' => 'pager-item'];
        $this->linkOptions = ['class' => 'inline-flex items-center justify-center min-w-10 h-10 px-3.5 rounded-lg border border-line text-sm font-medium text-fg-muted hover:bg-surface hover:border-line-strong transition'];
        $this->activePageCssClass = 'pager-active';
        $this->disabledPageCssClass = 'pager-disabled';
        $this->firstPageLabel = false;
        $this->lastPageLabel = false;
        $this->prevPageLabel = '<i class="fa-solid fa-angle-left"></i>';
        $this->nextPageLabel = '<i class="fa-solid fa-angle-right"></i>';
        $this->maxButtonCount = 7;

        parent::init();
    }
}
