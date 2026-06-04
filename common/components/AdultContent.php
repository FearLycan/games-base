<?php

declare(strict_types=1);

namespace common\components;

use common\models\User;
use Yii;

/**
 * Single source of truth for 18+ (adult) game visibility.
 *
 * A game is "adult" when its `is_adult` flag is 1 — derived during sync from the
 * Steam content descriptors (ids 3 "Adult Only Sexual Content" and 4 "Frequent
 * Nudity or Sexual Content", the ones Steam itself hard-gates). `required_age` is
 * deliberately not used: explicit adult games often ship with required_age = 0.
 * Adult games are hidden by default and only become visible to a signed-in
 * account that has explicitly opted in via its profile settings — there are two
 * independent switches:
 *
 *   - {@see showCatalog()}  → the public catalogue (browse, search, deal board,
 *                             game pages, company/genre/tag pages). Guests can
 *                             never see adult catalogue games.
 *   - {@see showOwned()}    → the account's own library / wishlist / achievements
 *                             (content it already owns on Steam).
 *
 * Restriction only ever applies on the public frontend. The console has no user
 * component and the admin backend must always see everything, so both flags read
 * as "show" outside the frontend app.
 */
final class AdultContent
{
    /** The frontend application id (see frontend/config/main.php). */
    private const string FRONTEND_APP_ID = 'gamentator-app';

    /** True when 18+ catalogue games should be visible to the current viewer. */
    public static function showCatalog(): bool
    {
        return self::flag('show_adult');
    }

    /** True when 18+ games should show in the viewer's own library/wishlist/achievements. */
    public static function showOwned(): bool
    {
        return self::flag('show_adult_owned');
    }

    /**
     * Excludes adult games from a catalogue query unless the viewer opted in.
     *
     * Works on both {@see \yii\db\ActiveQuery} and {@see \yii\db\Query} (both expose
     * andWhere). $alias is the table alias the query uses for the game table
     * (e.g. 'game', 'g').
     *
     * @param \yii\db\ActiveQuery|\yii\db\Query $query
     */
    public static function filterCatalog($query, string $alias = 'game'): void
    {
        if (!self::showCatalog()) {
            $query->andWhere([$alias . '.is_adult' => 0]);
        }
    }

    /**
     * Excludes adult games from an owned-content query (library/wishlist/achievements)
     * unless the viewer opted in.
     *
     * @param \yii\db\ActiveQuery|\yii\db\Query $query
     */
    public static function filterOwned($query, string $alias = 'game'): void
    {
        if (!self::showOwned()) {
            $query->andWhere([$alias . '.is_adult' => 0]);
        }
    }

    /**
     * Raw SQL boolean condition for the catalogue rule, for queries built from raw
     * SQL strings (no parameter binding needed — no user input is involved). Returns
     * '1=1' when adult content is allowed, so it can always be AND-ed in safely.
     */
    public static function catalogSql(string $alias = 'g'): string
    {
        return self::showCatalog() ? '1=1' : $alias . '.is_adult = 0';
    }

    /**
     * Raw SQL boolean condition for the owned-content rule. Returns '1=1' when
     * adult owned content is allowed, so it can always be AND-ed in safely.
     */
    public static function ownedSql(string $alias = 'g'): string
    {
        return self::showOwned() ? '1=1' : $alias . '.is_adult = 0';
    }

    /**
     * Cache-key fragment for catalogue lists so an opted-in viewer and the default
     * (hidden) audience never share a cached result.
     */
    public static function catalogCacheKey(): string
    {
        return self::showCatalog() ? 'adult-on' : 'adult-off';
    }

    /** Cache-key fragment for owned-content (library/wishlist/achievements) lists. */
    public static function ownedCacheKey(): string
    {
        return self::showOwned() ? 'owned-adult-on' : 'owned-adult-off';
    }

    private static function flag(string $attribute): bool
    {
        $app = Yii::$app;
        if ($app === null || $app->id !== self::FRONTEND_APP_ID) {
            return true;
        }

        $user = $app->has('user') ? $app->user : null;
        if ($user === null || $user->isGuest) {
            return false;
        }

        $identity = $user->identity;

        return $identity instanceof User && (bool)$identity->{$attribute};
    }
}
