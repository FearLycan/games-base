<?php

namespace common\components\steam;

use common\models\Game;
use common\models\User;
use common\models\UserWishlist;

/**
 * Syncs one user's Steam wishlist into {@see UserWishlist}, mirroring
 * {@see SteamLibrarySync}: match appids to the catalogue, queue stubs for the
 * rest (capped), upsert priority/added date, and drop entries the user removed.
 * Needs a public profile — a private wishlist returns an empty payload.
 */
class SteamWishlistSync
{
    public const string RESULT_OK      = 'ok';
    public const string RESULT_PRIVATE = 'private';
    public const string RESULT_NO_KEY  = 'no_key';
    public const string RESULT_FAILED  = 'failed';

    private const int MAX_NEW_STUBS = 100;

    private SteamApi $api;

    public function __construct(private User $user, ?SteamApi $api = null)
    {
        $this->api = $api ?? new SteamApi();
    }

    public function sync(): string
    {
        if (!$this->user->steam_id) {
            return self::RESULT_FAILED;
        }
        if (!$this->api->hasKey()) {
            return self::RESULT_NO_KEY;
        }

        $items = $this->api->getWishlist($this->user->steam_id);
        if ($items === null) {
            return self::RESULT_FAILED;
        }

        // Empty from a non-public profile means "can't see" — keep existing rows.
        if ($items === []
            && $this->user->steam_visibility !== null
            && $this->user->steam_visibility !== SteamApi::VISIBILITY_PUBLIC
        ) {
            return self::RESULT_PRIVATE;
        }

        $this->reconcile($items);

        return self::RESULT_OK;
    }

    /**
     * @param array<int, array<string, mixed>> $items GetWishlist items
     */
    private function reconcile(array $items): void
    {
        $byAppid = [];
        foreach ($items as $item) {
            $appid = (int)($item['appid'] ?? 0);
            if ($appid <= 0) {
                continue;
            }
            $byAppid[$appid] = [
                'priority' => (int)($item['priority'] ?? 0),
                'added'    => (int)($item['date_added'] ?? 0),
                'name'     => isset($item['name']) && $item['name'] !== '' ? (string)$item['name'] : null,
            ];
        }
        $appids = array_keys($byAppid);

        $gameIdByAppid = [];
        if ($appids !== []) {
            foreach (
                Game::find()->select(['id', 'steam_appid'])
                    ->where(['steam_appid' => $appids])->asArray()->all() as $row
            ) {
                $gameIdByAppid[(int)$row['steam_appid']] = (int)$row['id'];
            }
        }

        $this->queueCatalogStubs(array_values(array_diff($appids, array_keys($gameIdByAppid))));

        $existing = UserWishlist::find()
            ->where(['user_id' => $this->user->id])
            ->indexBy('steam_appid')
            ->all();

        foreach ($byAppid as $appid => $data) {
            $row = $existing[$appid] ?? new UserWishlist([
                'user_id'     => $this->user->id,
                'steam_appid' => $appid,
            ]);
            $row->game_id  = $gameIdByAppid[$appid] ?? null;
            $row->priority = $data['priority'];
            $row->added_at = $data['added'] > 0 ? date('Y-m-d H:i:s', $data['added']) : null;
            if ($data['name'] !== null) {
                $row->name = $data['name'];
            }
            $row->save(false);
        }

        if ($appids === []) {
            UserWishlist::deleteAll(['user_id' => $this->user->id]);
        } else {
            UserWishlist::deleteAll(['and', ['user_id' => $this->user->id], ['not in', 'steam_appid', $appids]]);
        }
    }

    /**
     * @param int[] $appids appids absent from the catalogue
     */
    private function queueCatalogStubs(array $appids): void
    {
        foreach (array_slice($appids, 0, self::MAX_NEW_STUBS) as $appid) {
            $stub = new Game();
            $stub->steam_appid = $appid;
            $stub->save(false);
        }
    }
}
