<?php

namespace common\components\steam;

use common\models\User;
use common\models\UserAchievement;
use common\models\UserGame;

/**
 * Syncs one owned game's achievements for a user from GetPlayerAchievements.
 *
 * Stores the per-game completion counts on the {@see UserGame} row (works for
 * any game with achievements — no catalogue schema needed) and one
 * {@see UserAchievement} row per unlocked achievement (with its unlock time) for
 * the rich per-game achievements page. The unlocked set is reconciled each run.
 */
class SteamAchievementSync
{
    private SteamApi $api;

    public function __construct(private User $user, ?SteamApi $api = null)
    {
        $this->api = $api ?? new SteamApi();
    }

    /**
     * Syncs achievements for one owned game.
     *
     * @return bool true when data was applied (including "game has no
     *              achievements"); false on a retryable failure (leave
     *              ach_synced_at untouched so the queue retries).
     */
    public function syncGame(UserGame $userGame): bool
    {
        // Without a catalogue game_id we can't tie unlocks to the schema; the
        // queue already filters these out.
        if (!$this->user->steam_id || $userGame->game_id === null) {
            return false;
        }

        $list = $this->api->getPlayerAchievements($this->user->steam_id, (int)$userGame->steam_appid);
        if ($list === null) {
            return false;
        }

        $unlocked = [];
        foreach ($list as $a) {
            if (empty($a['achieved'])) {
                continue;
            }
            $name = (string)($a['apiname'] ?? '');
            if ($name !== '') {
                $unlocked[$name] = (int)($a['unlocktime'] ?? 0);
            }
        }

        $this->persistUnlocked((int)$userGame->game_id, $unlocked);

        $userGame->ach_total = count($list);
        $userGame->ach_unlocked = count($unlocked);
        $userGame->ach_synced_at = date('Y-m-d H:i:s');
        $userGame->save(false, ['ach_total', 'ach_unlocked', 'ach_synced_at']);

        return true;
    }

    /**
     * Reconciles the stored unlocked rows for this user+game against the fresh
     * set: inserts new unlocks, refreshes changed unlock times, drops rows that
     * are no longer unlocked.
     *
     * @param array<string, int> $unlocked apiname => unlocktime (0 = unknown)
     */
    private function persistUnlocked(int $gameId, array $unlocked): void
    {
        $existing = UserAchievement::find()
            ->where(['user_id' => $this->user->id, 'game_id' => $gameId])
            ->indexBy('api_name')
            ->all();

        foreach ($unlocked as $name => $ts) {
            $at = $ts > 0 ? date('Y-m-d H:i:s', $ts) : null;

            if (isset($existing[$name])) {
                $row = $existing[$name];
                if ($row->unlocked_at !== $at) {
                    $row->unlocked_at = $at;
                    $row->save(false);
                }
                unset($existing[$name]);
            } else {
                (new UserAchievement([
                    'user_id'     => $this->user->id,
                    'game_id'     => $gameId,
                    'api_name'    => $name,
                    'unlocked_at' => $at,
                ]))->save(false);
            }
        }

        // Anything left in $existing is no longer unlocked (rare) — drop it.
        if ($existing !== []) {
            UserAchievement::deleteAll([
                'user_id'  => $this->user->id,
                'game_id'  => $gameId,
                'api_name' => array_keys($existing),
            ]);
        }
    }
}
