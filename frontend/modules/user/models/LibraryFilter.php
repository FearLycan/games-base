<?php

namespace frontend\modules\user\models;

use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Filter + sort state for the user's library list, bound straight from the
 * query string (no form-name prefix, so URLs read ?q=&played=&sort=).
 */
class LibraryFilter extends Model
{
    public const array PLAYED_OPTIONS = [
        ''         => 'All games',
        'played'   => 'Played',
        'unplayed' => 'Not played',
    ];

    public const array SORT_OPTIONS = [
        'added'      => 'Recently added',
        'playtime'   => 'Most played',
        'recent'     => 'Recently played',
        'completion' => 'Completion %',
        'name'       => 'Name (A–Z)',
        'name_desc'  => 'Name (Z–A)',
    ];

    public const array ACHIEVEMENTS_OPTIONS = [
        ''           => 'Any achievements',
        'has'        => 'With achievements',
        'incomplete' => 'Not 100%',
        'perfect'    => '100% complete',
    ];

    public string $q = '';
    public string $played = '';
    public string $achievements = '';
    public string $sort = 'recent';

    public function rules(): array
    {
        return [
            ['q', 'trim'],
            ['q', 'string', 'max' => 255],
            ['played', 'in', 'range' => array_keys(self::PLAYED_OPTIONS)],
            ['achievements', 'in', 'range' => array_keys(self::ACHIEVEMENTS_OPTIONS)],
            ['sort', 'in', 'range' => array_keys(self::SORT_OPTIONS)],
        ];
    }

    /**
     * Bind from the top-level query params (q, played, sort, …) — no prefix.
     */
    public function formName(): string
    {
        return '';
    }

    /** True when any narrowing filter (not just sort) is active. */
    public function isActive(): bool
    {
        return $this->q !== '' || $this->played !== '' || $this->achievements !== '';
    }

    /**
     * Applies the (validated) filters and sort to a UserGame query. Invalid
     * values are dropped to their defaults so a hand-edited URL can't break it.
     */
    public function apply(ActiveQuery $query): ActiveQuery
    {
        if (!$this->validate()) {
            $this->q = $this->hasErrors('q') ? '' : $this->q;
            $this->played = $this->hasErrors('played') ? '' : $this->played;
            $this->achievements = $this->hasErrors('achievements') ? '' : $this->achievements;
            $this->sort = $this->hasErrors('sort') ? 'added' : $this->sort;
        }

        if ($this->q !== '') {
            $query->andWhere(['like', 'user_game.name', $this->q]);
        }

        if ($this->played === 'played') {
            $query->andWhere(['>', 'user_game.playtime_minutes', 0]);
        } elseif ($this->played === 'unplayed') {
            $query->andWhere(['user_game.playtime_minutes' => 0]);
        }

        match ($this->achievements) {
            'has'        => $query->andWhere(['>', 'user_game.ach_total', 0]),
            'incomplete' => $query->andWhere('user_game.ach_total > 0 AND user_game.ach_unlocked < user_game.ach_total'),
            'perfect'    => $query->andWhere('user_game.ach_total > 0 AND user_game.ach_unlocked >= user_game.ach_total'),
            default      => null,
        };

        return match ($this->sort) {
            'playtime'   => $query->orderBy(['user_game.playtime_minutes' => SORT_DESC, 'user_game.name' => SORT_ASC]),
            'recent'     => $query->orderBy(['user_game.last_played_at' => SORT_DESC, 'user_game.playtime_minutes' => SORT_DESC]),
            // Games with achievements ranked by unlocked ratio; the rest sink below.
            'completion' => $query->orderBy('(CASE WHEN user_game.ach_total > 0 THEN user_game.ach_unlocked / user_game.ach_total ELSE -1 END) DESC, user_game.playtime_minutes DESC'),
            'name'       => $query->orderBy(['user_game.name' => SORT_ASC]),
            'name_desc'  => $query->orderBy(['user_game.name' => SORT_DESC]),
            default      => $query->orderBy(['user_game.created_at' => SORT_DESC, 'user_game.id' => SORT_DESC]),
        };
    }
}
