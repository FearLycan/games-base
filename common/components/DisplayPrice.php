<?php

namespace common\components;

/**
 * The resolved price to surface on game cards and lists. Built by
 * {@see \common\models\Game::getDisplayPrice()} so views stay logic-free:
 * they only read the already-formatted labels and flags.
 *
 * Amounts arrive pre-formatted (currency symbol included) from
 * {@see \common\models\GameOfferPrice} or the Steam price helpers.
 */
final readonly class DisplayPrice
{
    /** Free-to-play game — no purchase price. */
    public const string SOURCE_FREE = 'free';
    /** Cheapest matching store offer in the visitor's currency. */
    public const string SOURCE_OFFER = 'offer';
    /** Steam store price, used when there are no store offers. */
    public const string SOURCE_STEAM = 'steam';

    public function __construct(
        public string $final,
        public ?string $initial,
        public int $discount,
        public bool $free,
        public string $source,
        /** Name of the store this price comes from (e.g. "Steam", "Gamivo"), or null. */
        public ?string $store = null,
        /** Slug of that store, for logos/links, or null. */
        public ?string $storeSlug = null,
        /** Whether that store is a first-party storefront rather than a keyshop. */
        public bool $storeOfficial = false,
        /** Whether this is the lowest price ever recorded for the game (and it was once higher). */
        public bool $historicalLow = false,
    ) {
    }

    /** Whether there is a struck-through pre-discount price to render. */
    public function isDiscounted(): bool
    {
        return $this->discount > 0 && $this->initial !== null;
    }
}
