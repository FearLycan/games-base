<?php

namespace frontend\modules\user\models;

use common\enums\AchievementRarity;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Filter + sort for the user's achievement collection. Bound from the query
 * string (no form-name prefix → ?q=&game=&sort=). The query it's applied to is
 * a UserAchievement query aliased `ua`, inner-joined to `game_achievement` and
 * `game`.
 */
class AchievementsFilter extends Model
{
    public const array SORT_OPTIONS = [
        'recent' => 'Recently unlocked',
        'rarest' => 'Rarest first',
        'common' => 'Most common',
    ];

    public string $q = '';
    public string $game = '';
    public string $rarity = '';
    public string $sort = 'recent';

    public function rules(): array
    {
        return [
            ['q', 'trim'],
            ['q', 'string', 'max' => 255],
            ['game', 'match', 'pattern' => '/^\d*$/'],
            ['rarity', 'in', 'range' => AchievementRarity::values()],
            ['sort', 'in', 'range' => array_keys(self::SORT_OPTIONS)],
        ];
    }

    /**
     * Rarity dropdown options (value => label), prepended with "Any rarity".
     *
     * @return array<string,string>
     */
    public static function rarityOptions(): array
    {
        return ['' => 'Any rarity'] + AchievementRarity::options();
    }

    public function formName(): string
    {
        return '';
    }

    public function isActive(): bool
    {
        return $this->q !== '' || $this->game !== '' || $this->rarity !== '';
    }

    public function apply(ActiveQuery $query): ActiveQuery
    {
        if (!$this->validate()) {
            $this->q = $this->hasErrors('q') ? '' : $this->q;
            $this->game = $this->hasErrors('game') ? '' : $this->game;
            $this->rarity = $this->hasErrors('rarity') ? '' : $this->rarity;
            $this->sort = $this->hasErrors('sort') ? 'recent' : $this->sort;
        }

        if ($this->q !== '') {
            $query->andWhere(['or',
                              ['like', 'game_achievement.name', $this->q],
                              ['like', 'game.title', $this->q],
            ]);
        }

        if ($this->game !== '') {
            $query->andWhere(['ua.game_id' => (int)$this->game]);
        }

        // Rarity tier — thresholds + NULL-is-common handling live in the enum,
        // shared with GameAchievement and the game's achievements page.
        if ($this->rarity !== '') {
            $query->andWhere(AchievementRarity::from($this->rarity)->condition('game_achievement.percent'));
        }

        return match ($this->sort) {
            // Unknown rarity (percent NULL) always sinks to the bottom.
            'rarest' => $query->orderBy(new Expression('game_achievement.percent IS NULL, game_achievement.percent ASC')),
            'common' => $query->orderBy(new Expression('game_achievement.percent IS NULL, game_achievement.percent DESC')),
            default => $query->orderBy(['ua.unlocked_at' => SORT_DESC]),
        };
    }
}
