<?php

namespace common\schema\builder;

use common\models\Game;
use common\schema\factory\GameOfferSchemaFactory;
use common\schema\factory\VideoGameSchemaFactory;

final class GamePageSchemaBuilder
{
    /**
     * @return array<int, array<string, mixed>> Nodes for the JSON-LD @graph.
     */
    public static function build(Game $game, string $productUrl): array
    {
        $offer = GameOfferSchemaFactory::fromGame($game, $productUrl);
        $videoGame = VideoGameSchemaFactory::fromGame($game, $productUrl, $offer);

        return [
            $videoGame,
        ];
    }
}
