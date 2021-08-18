<?php

namespace frontend\components;

class Helper extends \common\components\Helper
{
    public static function getLinkList($models, $label, $slug, $base_url, $separator = ', ', $class = '')
    {
        $text = '';

        foreach ($models as $key => $model) {

            $text .= '<a href="' . $base_url . $model->{$slug} . '" class="' . $class . '">' . $model->{$label} . '</a>';

            if (sizeof($models) != ($key + 1)) {
                $text .= $separator;
            }
        }

        return $text;
    }
}