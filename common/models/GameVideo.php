<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%game_video}}".
 *
 * A trailer / preview video attached to a game. Currently sourced from Kinguin's
 * product API (YouTube), but provider-agnostic. The render helpers keep all
 * URL-building out of the views ([[feedback-no-business-logic-in-views]]).
 *
 * @property int         $id
 * @property int         $game_id
 * @property string      $provider
 * @property string      $video_id
 * @property string      $url
 * @property string|null $title
 * @property int         $position
 * @property int         $status
 * @property string      $created_at
 * @property string|null $updated_at
 *
 * @property Game        $game
 */
class GameVideo extends ActiveRecord
{
    public const string PROVIDER_YOUTUBE = 'youtube';

    public const int STATUS_ACTIVE   = 1;
    public const int STATUS_INACTIVE = 0;

    public static function tableName(): string
    {
        return '{{%game_video}}';
    }

    public function rules(): array
    {
        return [
            [['game_id', 'video_id', 'url'], 'required'],
            [['game_id', 'position', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['provider', 'video_id'], 'string', 'max' => 64],
            [['url', 'title'], 'string', 'max' => 255],
            [['game_id'], 'exist', 'skipOnError' => true, 'targetClass' => Game::class, 'targetAttribute' => ['game_id' => 'id']],
        ];
    }

    public function getGame(): ActiveQuery
    {
        return $this->hasOne(Game::class, ['id' => 'game_id']);
    }

    /** Poster frame for the trailer card. */
    public function getThumbnailUrl(): string
    {
        if ($this->provider === self::PROVIDER_YOUTUBE && $this->video_id !== '') {
            return 'https://i.ytimg.com/vi/' . rawurlencode($this->video_id) . '/hqdefault.jpg';
        }

        return '/img/header-default.png';
    }

    /** Privacy-friendly embed URL for the lightbox (autoplay once opened). */
    public function getEmbedUrl(): string
    {
        if ($this->provider === self::PROVIDER_YOUTUBE && $this->video_id !== '') {
            return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($this->video_id) . '?autoplay=1&rel=0';
        }

        return $this->url;
    }
}
