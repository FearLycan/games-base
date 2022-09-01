<?php


namespace common\components;


class Helper
{
    public static function getStatusLabel($model): string
    {
        switch ($model->status) {
            case 0:
            case 9:
                return '<span class="label label-table label-danger">' . $model->getStatusName() . '</span>';
                break;
            case 1:
            case 10:
                return '<span class="label label-table label-success">' . $model->getStatusName() . '</span>';
                break;
            default:
                return '<span class="label label-table label-default">' . $model->getStatusName() . '</span>';
        }
    }

    public static function clearHtml($html): string
    {
        $html = str_replace("h1>", "h4>", $html);

        return $html;
    }
}