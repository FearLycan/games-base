<?php

declare(strict_types=1);

namespace frontend\modules\user\models;

use common\models\User;
use yii\base\Model;

/**
 * Content-visibility preferences for a signed-in account. Currently the two 18+
 * (adult) switches: whether to show mature games in the public catalogue and in
 * the account's own library/wishlist/achievements. Both default to off — adult
 * content is hidden until the user explicitly turns it on.
 */
class PreferencesForm extends Model
{
    public bool $show_adult = false;
    public bool $show_adult_owned = false;

    private User $user;

    public function __construct(User $user, array $config = [])
    {
        $this->user = $user;
        $this->show_adult = (bool)$user->show_adult;
        $this->show_adult_owned = (bool)$user->show_adult_owned;
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['show_adult', 'show_adult_owned'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'show_adult'       => 'Show 18+ games while browsing',
            'show_adult_owned' => 'Show 18+ games in my library, wishlist and achievements',
        ];
    }

    /**
     * Persists the preferences onto the account.
     */
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->user->show_adult = $this->show_adult;
        $this->user->show_adult_owned = $this->show_adult_owned;

        return $this->user->save(false, ['show_adult', 'show_adult_owned']);
    }
}
