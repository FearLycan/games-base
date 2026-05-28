<?php

namespace common\schema\factory;

use common\models\Game;

final class GameOfferSchemaFactory
{
    /**
     * Steam prices are stored in cents and are USD on this catalog.
     * Returns a single Offer node, or null when there is no price to show.
     */
    public static function fromGame(Game $game, string $productUrl): ?array
    {
        if ((int)$game->is_free === 1) {
            return [
                '@type'         => 'Offer',
                'price'         => '0.00',
                'priceCurrency' => 'USD',
                'availability'  => 'https://schema.org/InStock',
                'url'           => $productUrl,
            ];
        }

        $priceCents = (int)$game->steam_price_final;
        if ($priceCents <= 0) {
            return null;
        }

        return [
            '@type'         => 'Offer',
            'price'         => number_format($priceCents / 100, 2, '.', ''),
            'priceCurrency' => 'USD',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $productUrl,
        ];
    }
}
