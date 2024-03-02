<?php

namespace frontend\components;

class Helper extends \common\components\Helper
{
    public static function getLinkList($models, $label, $slug, $base_url, $separator = ', ', $class = '')
    {
        $text = '';
        foreach ($models as $key => $model) {
            $text .= '<a href="' . $base_url . $model->{$slug} . '" class="' . $class . '">' . $model->{$label} . '</a>';
            if (count($models) !== ($key + 1)) {
                $text .= $separator;
            }
        }

        return $text;
    }

    public static function pager(): array
    {
        return [
            'options'       => [
                'class' => 'blog__pagination',
            ],
            'nextPageLabel' => '<i class="fa fa-long-arrow-right"></i>',
            'prevPageLabel' => '<i class="fa fa-long-arrow-left"></i>',
        ];
    }
}