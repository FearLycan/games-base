<?php

namespace common\schema\factory;

use common\models\Game;
use Yii;
use yii\helpers\Url;

final class ItemListSchemaFactory
{
    /**
     * Build an ItemList node from a page of games.
     *
     * @param iterable<Game> $games
     */
    public static function fromGames(iterable $games, string $listName, int $startPosition = 1, ?int $totalItems = null): array
    {
        $items = [];
        $position = max(1, $startPosition);

        foreach ($games as $game) {
            if (!$game instanceof Game) {
                continue;
            }

            $name = trim((string)$game->title);
            if ($name === '') {
                continue;
            }

            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position,
                'url'      => Url::to(['/game/game/view', 'id' => (int)$game->steam_appid, 'slug' => $game->slug], true),
                'name'     => $name,
            ];
            $position++;
        }

        if ($items === []) {
            return [];
        }

        return [
            '@type'           => 'ItemList',
            '@id'             => '#item-list',
            'name'            => $listName,
            'url'             => Yii::$app->request->absoluteUrl,
            'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems'   => $totalItems ?? count($items),
            'itemListElement' => $items,
        ];
    }
}
