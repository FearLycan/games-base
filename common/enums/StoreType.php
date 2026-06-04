<?php

namespace common\enums;

/**
 * Whether a store is a first-party storefront or a third-party CD-key reseller.
 * Drives trust signalling on price badges (Official vs Keyshop) and lets the
 * price comparison group/filter offers by kind. Int-backed to match the
 * `store.type` column.
 */
enum StoreType: int
{
    /** First-party / authorized storefront (Steam, Epic, GOG, …). */
    case Official = 1;
    /** Third-party CD-key reseller (Instant Gaming, Gamivo, GameSeal, …). */
    case Keyshop = 2;

    public function label(): string
    {
        return match ($this) {
            self::Official => 'Official',
            self::Keyshop  => 'Keyshop',
        };
    }

    /** @return int[] backing values, e.g. for validation ranges */
    public static function values(): array
    {
        return array_map(static fn(self $c): int => $c->value, self::cases());
    }

    /**
     * value => label map for dropdowns.
     *
     * @return array<int,string>
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
