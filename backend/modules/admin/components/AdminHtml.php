<?php

namespace backend\modules\admin\components;

use backend\models\GameOffer;
use common\models\Game;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Small presentation helpers for the admin GridViews. Kept out of the views so
 * they stay free of markup-building logic.
 */
class AdminHtml
{
    /**
     * Renders a Bootstrap 5 switch bound to the AJAX status toggle. The switch
     * is "on" when the model's $attribute currently equals $onValue.
     *
     * @param ActiveRecord $model     row model
     * @param string       $attribute toggled attribute (e.g. "status", "available")
     * @param int          $onValue   value treated as the "on" state
     * @param string       $url       toggle-status endpoint for this row
     */
    public static function statusSwitch(ActiveRecord $model, string $attribute, int $onValue, string $url): string
    {
        $checked = (int)$model->getAttribute($attribute) === $onValue;

        $input = Html::checkbox('', $checked, [
            'class'   => 'form-check-input js-status-switch',
            'role'    => 'switch',
            'data-url' => $url,
            'aria-label' => 'Toggle status',
        ]);

        return Html::tag('div', $input, ['class' => 'form-check form-switch']);
    }

    /**
     * Renders a game's title as a link to its admin view page. Use anywhere a
     * game name appears in a list so it's clickable. Falls back to the raw id
     * (or an em dash) when the title/game is missing.
     *
     * @param Game|null $game related game (eager-loaded), if available
     * @param int|null  $gameId game id, used for the link and as fallback label
     */
    public static function gameLink(?Game $game, ?int $gameId): string
    {
        $id = $game?->id ?? $gameId;

        if ($id === null) {
            return '—';
        }

        $label = $game?->title ?: ('#' . $id);

        return Html::a(Html::encode($label), Url::to(['/admin/game/view', 'id' => $id]));
    }

    /** Number of store offers awaiting review — drives the sidebar/dashboard badge. */
    public static function pendingOffersCount(): int
    {
        return (int)GameOffer::find()->where(['status' => GameOffer::STATUS_REVIEW])->count();
    }

    /** Coloured pill for a simple active(1)/inactive(0) status. */
    public static function activePill(?int $status, string $onLabel = 'Active', string $offLabel = 'Inactive'): string
    {
        $isOn = (int)$status === 1;

        return Html::tag(
            'span',
            $isOn ? $onLabel : $offLabel,
            ['class' => 'admin-pill admin-pill--' . ($isOn ? 'active' : 'inactive')],
        );
    }

    /** Coloured pill for a Game status value. */
    public static function gameStatusPill(int $status): string
    {
        [$label, $modifier] = match ($status) {
            Game::STATUS_ACTIVE        => ['Active', 'active'],
            Game::STATUS_WAIT_TO_SYNC  => ['Wait to sync', 'review'],
            Game::STATUS_SUCCESS_FALSE => ['Sync failed', 'inactive'],
            default                    => ['Inactive', 'inactive'],
        };

        return Html::tag('span', $label, ['class' => "admin-pill admin-pill--$modifier"]);
    }

    /** Coloured pill for a GameOffer status value. */
    public static function offerStatusPill(int $status): string
    {
        [$label, $modifier] = match ($status) {
            GameOffer::STATUS_ACTIVE   => ['Active', 'active'],
            GameOffer::STATUS_REVIEW   => ['Review', 'review'],
            default                    => ['Inactive', 'inactive'],
        };

        return Html::tag('span', $label, ['class' => "admin-pill admin-pill--$modifier"]);
    }
}
