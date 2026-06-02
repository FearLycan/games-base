<?php

namespace frontend\modules\user\models;

use yii\base\Model;
use yii\db\ActiveQuery;

/**
 * Filter + sort state for the wishlist list, bound from the query string
 * (no form-name prefix, so URLs read ?q=&sort=).
 */
class WishlistFilter extends Model
{
    public const array SORT_OPTIONS = [
        'priority'  => 'Priority',
        'added'     => 'Recently added',
        'name'      => 'Name (A–Z)',
        'name_desc' => 'Name (Z–A)',
    ];

    public string $q = '';
    public string $sort = 'priority';

    public function rules(): array
    {
        return [
            ['q', 'trim'],
            ['q', 'string', 'max' => 255],
            ['sort', 'in', 'range' => array_keys(self::SORT_OPTIONS)],
        ];
    }

    public function formName(): string
    {
        return '';
    }

    public function isActive(): bool
    {
        return $this->q !== '';
    }

    public function apply(ActiveQuery $query): ActiveQuery
    {
        if (!$this->validate()) {
            $this->q = $this->hasErrors('q') ? '' : $this->q;
            $this->sort = $this->hasErrors('sort') ? 'priority' : $this->sort;
        }

        // The list always inner-joins the catalogue game, and the wishlist API
        // gives no name, so search/sort by name target the catalogue title.
        if ($this->q !== '') {
            $query->andWhere(['like', 'game.title', $this->q]);
        }

        return match ($this->sort) {
            'added'     => $query->orderBy(['user_wishlist.added_at' => SORT_DESC, 'user_wishlist.id' => SORT_DESC]),
            'name'      => $query->orderBy(['game.title' => SORT_ASC]),
            'name_desc' => $query->orderBy(['game.title' => SORT_DESC]),
            // Steam priority: 1 = top, 0 = unranked (sink unranked to the bottom).
            default     => $query->orderBy('(user_wishlist.priority = 0) ASC, user_wishlist.priority ASC, user_wishlist.added_at DESC'),
        };
    }
}
