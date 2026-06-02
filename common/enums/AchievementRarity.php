<?php

namespace common\enums;

/**
 * Single source of truth for achievement rarity tiers, derived from the global
 * Steam unlock percentage. Used by {@see \common\models\GameAchievement} (per
 * achievement), the game achievements page, and the user's achievement
 * collection filter — so the thresholds and labels never drift apart.
 *
 * Tiers (by global unlock %): ultra < 5, rare < 20, uncommon < 50, common ≥ 50.
 * An unknown rate (null percent) counts as common.
 */
enum AchievementRarity: string
{
    case Ultra = 'ultra';
    case Rare = 'rare';
    case Uncommon = 'uncommon';
    case Common = 'common';

    public function label(): string
    {
        return match ($this) {
            self::Ultra    => 'Ultra rare',
            self::Rare     => 'Rare',
            self::Uncommon => 'Uncommon',
            self::Common   => 'Common',
        };
    }

    /**
     * The tier for a global unlock percentage. Null (unknown rate) is common.
     */
    public static function fromPercent(?float $percent): self
    {
        if ($percent === null) {
            return self::Common;
        }

        return match (true) {
            $percent < 5  => self::Ultra,
            $percent < 20 => self::Rare,
            $percent < 50 => self::Uncommon,
            default       => self::Common,
        };
    }

    /**
     * A Yii query condition selecting rows of this tier by a percent column.
     * Common includes NULL (unknown rarity), matching {@see fromPercent()}.
     *
     * @return array
     */
    public function condition(string $column): array
    {
        return match ($this) {
            self::Ultra    => ['<', $column, 5],
            self::Rare     => ['and', ['>=', $column, 5], ['<', $column, 20]],
            self::Uncommon => ['and', ['>=', $column, 20], ['<', $column, 50]],
            self::Common   => ['or', ['>=', $column, 50], [$column => null]],
        };
    }

    /** @return string[] backing values, e.g. for validation ranges */
    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }

    /**
     * value => label map for dropdowns.
     *
     * @return array<string,string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
