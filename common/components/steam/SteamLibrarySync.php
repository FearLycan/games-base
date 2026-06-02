<?php

namespace common\components\steam;

use common\models\Game;
use common\models\User;
use common\models\UserGame;

/**
 * Syncs one user's owned-games library from Steam's public Web API into
 * {@see UserGame}.
 *
 * Steam OpenID only proves identity — it grants no token — so the library is
 * read with our own key and only works while the user's "game details" profile
 * is public. A private profile yields an empty payload; we record that
 * (steam_visibility) so the UI can explain the empty library instead of looking
 * broken.
 *
 * Owned appids are matched to the catalogue by {@see Game::$steam_appid}. Appids
 * we don't have yet are queued as WAIT_TO_SYNC stubs (capped per run) so the
 * catalogue grows from real libraries; the user_game row keeps the Steam name
 * until the stub is synced and the link resolves on a later pass.
 */
class SteamLibrarySync
{
    public const string RESULT_OK      = 'ok';
    public const string RESULT_PRIVATE = 'private';
    public const string RESULT_NO_KEY  = 'no_key';
    public const string RESULT_FAILED  = 'failed';

    /** Most new catalogue stubs to queue from a single user's library per run. */
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

        $summary = $this->api->getPlayerSummary($this->user->steam_id);
        if ($summary !== null && isset($summary['communityvisibilitystate'])) {
            $this->user->steam_visibility = (int)$summary['communityvisibilitystate'];
        }

        $owned = $this->api->getOwnedGames($this->user->steam_id);
        if ($owned === null) {
            // Transport/HTTP failure — leave steam_synced_at untouched so the
            // cron retries this user on the next run.
            return self::RESULT_FAILED;
        }

        // Empty payload from a non-public profile means "can't see", not "owns
        // nothing": keep existing rows, just record the state.
        if ($owned['games'] === []
            && $this->user->steam_visibility !== null
            && $this->user->steam_visibility !== SteamApi::VISIBILITY_PUBLIC
        ) {
            $this->finish();
            return self::RESULT_PRIVATE;
        }

        $this->reconcile($owned['games']);
        $this->finish();

        return self::RESULT_OK;
    }

    /**
     * Brings the user's UserGame rows in line with the owned-games payload:
     * upserts owned titles, drops no-longer-owned ones, and queues catalogue
     * stubs for appids we don't have.
     *
     * @param array<int, array<string, mixed>> $games owned-games payload
     */
    private function reconcile(array $games): void
    {
        // Normalise to appid => {name, playtime, lastPlayed}.
        $byAppid = [];
        foreach ($games as $g) {
            $appid = (int)($g['appid'] ?? 0);
            if ($appid <= 0) {
                continue;
            }
            $byAppid[$appid] = [
                'name'     => isset($g['name']) && $g['name'] !== '' ? (string)$g['name'] : null,
                'playtime' => (int)($g['playtime_forever'] ?? 0),
                'last'     => (int)($g['rtime_last_played'] ?? 0),
            ];
        }
        $ownedAppids = array_keys($byAppid);

        // Map owned appids to catalogue game ids in one query.
        $gameIdByAppid = [];
        if ($ownedAppids !== []) {
            foreach (
                Game::find()->select(['id', 'steam_appid'])
                    ->where(['steam_appid' => $ownedAppids])->asArray()->all() as $row
            ) {
                $gameIdByAppid[(int)$row['steam_appid']] = (int)$row['id'];
            }
        }

        // Queue stubs for appids missing from the catalogue (linked on a later sync).
        $this->queueCatalogStubs(array_values(array_diff($ownedAppids, array_keys($gameIdByAppid))));

        // Upsert: reuse existing rows, insert new ones.
        $existing = UserGame::find()
            ->where(['user_id' => $this->user->id])
            ->indexBy('steam_appid')
            ->all();

        foreach ($byAppid as $appid => $data) {
            $row = $existing[$appid] ?? new UserGame([
                'user_id'     => $this->user->id,
                'steam_appid' => $appid,
            ]);
            $row->game_id          = $gameIdByAppid[$appid] ?? null;
            $row->name             = $data['name'] ?? $row->name;
            $row->playtime_minutes = $data['playtime'];
            $row->last_played_at   = $data['last'] > 0 ? date('Y-m-d H:i:s', $data['last']) : null;
            $row->save(false);
        }

        // Drop games the user no longer owns (or all rows when the library is
        // now empty for a public profile).
        if ($ownedAppids === []) {
            UserGame::deleteAll(['user_id' => $this->user->id]);
        } else {
            UserGame::deleteAll(['and', ['user_id' => $this->user->id], ['not in', 'steam_appid', $ownedAppids]]);
        }
    }

    /**
     * Inserts WAIT_TO_SYNC catalogue stubs (steam_appid only) for owned appids
     * we don't have, capped per run so a huge first library can't flood the
     * sync queue in one go. Mirrors Game::queueDlcSync()'s stub pattern.
     *
     * @param int[] $appids appids known to be absent from the catalogue
     */
    private function queueCatalogStubs(array $appids): void
    {
        foreach (array_slice($appids, 0, self::MAX_NEW_STUBS) as $appid) {
            $stub = new Game();
            $stub->steam_appid = $appid;
            // save(false): a bare stub doesn't pass validation yet; status
            // defaults to STATUS_WAIT_TO_SYNC (0), same as the coming-soon importer.
            $stub->save(false);
        }
    }

    private function finish(): void
    {
        $this->user->steam_synced_at = date('Y-m-d H:i:s');
        $this->user->save(false, ['steam_synced_at', 'steam_visibility']);
    }
}
