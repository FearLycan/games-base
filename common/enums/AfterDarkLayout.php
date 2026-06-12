<?php

declare(strict_types=1);

namespace common\enums;

/**
 * The visual theme a member picks for the "After Dark" (18+) area. Each case is
 * a self-contained look the {@see \frontend\views\layouts\afterdark} layout
 * swaps in by redefining the Tailwind @theme tokens — so every token-driven
 * partial (game tiles, pager, chips) re-skins automatically.
 *
 * String-backed to match the `user.after_dark_layout` column and keep URLs/markup
 * readable. {@see NeonPlum} is the default for a freshly opted-in account.
 */
enum AfterDarkLayout: string
{
    /** Dark plum/bordeaux canvas, magenta-rose neon glow. */
    case NeonPlum = 'neon';
    /** Light rosé canvas close to the main site, wine/rose accent. */
    case LightRose = 'rose';
    /** Black canvas with shimmering gold accents — premium boudoir. */
    case BlackGold = 'gold';

    public static function default(): self
    {
        return self::NeonPlum;
    }

    /** Short display name for the switcher. */
    public function label(): string
    {
        return match ($this) {
            self::NeonPlum  => 'Neon Plum',
            self::LightRose => 'Light Rosé',
            self::BlackGold => 'Black & Gold',
        };
    }

    /** One-line description shown under the label in the switcher. */
    public function tagline(): string
    {
        return match ($this) {
            self::NeonPlum  => 'Dark, magenta neon glow',
            self::LightRose => 'Bright, soft rosé accent',
            self::BlackGold => 'Black & shimmering gold',
        };
    }

    /** CSS gradient used as the colour swatch on the switcher chips. */
    public function swatch(): string
    {
        return match ($this) {
            self::NeonPlum  => 'linear-gradient(135deg, #ff2d78, #b026ff)',
            self::LightRose => 'linear-gradient(135deg, #ff5c93, #8e1346)',
            self::BlackGold => 'linear-gradient(135deg, #f1d57a, #b8902b)',
        };
    }

    /** @return string[] backing values, e.g. for validation ranges. */
    public static function values(): array
    {
        return array_map(static fn(self $c): string => $c->value, self::cases());
    }

    /** Safe resolve from a stored/posted value, falling back to the default. */
    public static function fromValue(?string $value): self
    {
        return ($value !== null ? self::tryFrom($value) : null) ?? self::default();
    }
}
