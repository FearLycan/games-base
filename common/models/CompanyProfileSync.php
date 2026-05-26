<?php

namespace common\models;

/**
 * Keeps {{%developer}}.profile_id and {{%publisher}}.profile_id in sync for
 * companies that exist under the same name in both tables.
 *
 * Strategy (mirrored for each direction):
 *   - If the saved row has profile_id set, propagate it to the counterpart row
 *     with the same name that has no profile yet.
 *   - If the saved row was just inserted with a null profile, try to inherit
 *     a profile_id from a counterpart row that already has one.
 *
 * Updates go through updateAll / updateAttributes, which do not fire afterSave,
 * so there is no recursion across the two models.
 */
final class CompanyProfileSync
{
    public static function syncFromDeveloper(Developer $dev, bool $insert, array $changedAttributes): void
    {
        self::sync(
            saved: $dev,
            counterpartClass: Publisher::class,
            insert: $insert,
            changedAttributes: $changedAttributes,
        );
    }

    public static function syncFromPublisher(Publisher $pub, bool $insert, array $changedAttributes): void
    {
        self::sync(
            saved: $pub,
            counterpartClass: Developer::class,
            insert: $insert,
            changedAttributes: $changedAttributes,
        );
    }

    /**
     * @param Developer|Publisher                   $saved
     * @param class-string<Developer|Publisher>     $counterpartClass
     */
    private static function sync(
        Developer|Publisher $saved,
        string $counterpartClass,
        bool $insert,
        array $changedAttributes,
    ): void {
        if (empty($saved->name)) {
            return;
        }

        // Propagate this profile_id to a counterpart row that has none.
        if ($saved->profile_id !== null
            && ($insert || array_key_exists('profile_id', $changedAttributes))
        ) {
            $counterpartClass::updateAll(
                ['profile_id' => $saved->profile_id],
                ['name' => $saved->name, 'profile_id' => null],
            );
            return;
        }

        // On insert with no profile yet, try to inherit one from the counterpart.
        if ($insert && $saved->profile_id === null) {
            $inherited = $counterpartClass::find()
                ->select('profile_id')
                ->where(['name' => $saved->name])
                ->andWhere(['not', ['profile_id' => null]])
                ->scalar();

            if ($inherited) {
                $saved->updateAttributes(['profile_id' => (int)$inherited]);
            }
        }
    }
}
