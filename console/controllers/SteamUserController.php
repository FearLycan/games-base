<?php

namespace console\controllers;

use common\components\steam\SteamAchievementSync;
use common\components\steam\SteamApi;
use common\components\steam\SteamLibrarySync;
use common\components\steam\SteamWishlistSync;
use common\models\User;
use common\models\UserGame;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\mutex\FileMutex;

/**
 * Per-user Steam sync (library for now; wishlist/achievements later).
 *
 * Runs on a paced cron like SteamController — never in the request cycle.
 * Each run takes a bounded batch of users, syncs them with a short randomised
 * delay between calls to stay friendly to the Steam Web API, and is guarded by
 * a file mutex so overlapping cron ticks don't double up.
 *
 * Queue order: users never synced / explicitly re-queued (steam_synced_at NULL)
 * first, then libraries that have gone stale.
 */
class SteamUserController extends Controller
{
    private const string LOCK_SYNC = 'steam-user/sync';
    private const string LOCK_ACH  = 'steam-user/achievements';

    private const int SYNC_DELAY_MIN = 2;
    private const int SYNC_DELAY_MAX = 4;

    /** Achievement calls are one-per-game, so keep the gap short. */
    private const int ACH_DELAY_MIN = 1;
    private const int ACH_DELAY_MAX = 2;

    /** Re-sync an already-synced library after this many days. */
    private const int STALE_AFTER_DAYS = 7;

    /** Re-sync an already-synced game's achievements after this many days. */
    private const int ACH_STALE_AFTER_DAYS = 30;

    public bool $verbose = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if (in_array($actionID, ['sync', 'sync-achievements'], true)) {
            $options[] = 'verbose';
        }

        return $options;
    }

    /**
     * Syncs up to $limit users' libraries (0 = no cap).
     */
    public function actionSync(int $limit = 50): int
    {
        $mutex = new FileMutex();
        if (!$mutex->acquire(self::LOCK_SYNC)) {
            $this->stdout(self::LOCK_SYNC . ": another run is already in progress — skipping.\n");
            return ExitCode::OK;
        }

        try {
            return $this->runSync($limit);
        } finally {
            $mutex->release(self::LOCK_SYNC);
        }
    }

    private function runSync(int $limit): int
    {
        $userIds = $this->collectUserIds($limit);
        $total = count($userIds);
        if ($this->verbose) {
            $this->stdout("Syncing {$total} user librar(y/ies)\n");
        }

        foreach ($userIds as $i => $userId) {
            $user = User::findOne(['id' => $userId]);
            if ($user === null) {
                continue;
            }

            $position = $i + 1;
            $start = microtime(true);
            try {
                // Library first (it sets steam_visibility, which the wishlist
                // sync reads to tell "private" from "empty").
                $result = (new SteamLibrarySync($user))->sync();
                $wishlist = (new SteamWishlistSync($user))->sync();
                if ($this->verbose) {
                    $elapsed = round(microtime(true) - $start, 2);
                    $this->stdout("[{$position}/{$total}] {$user->username} (#{$userId}): library={$result} wishlist={$wishlist} in {$elapsed}s\n");
                }
            } catch (\Throwable $e) {
                $this->stderr("[{$position}/{$total}] #{$userId} failed: {$e->getMessage()}\n");
            }

            sleep(random_int(self::SYNC_DELAY_MIN, self::SYNC_DELAY_MAX));
        }

        return ExitCode::OK;
    }

    /**
     * Ordered list of user ids to sync this run: never-synced / re-queued first
     * (steam_synced_at NULL), then stale libraries oldest-first. Only active
     * accounts with a linked Steam id are considered.
     *
     * @return int[]
     */
    private function collectUserIds(int $limit): array
    {
        $base = static fn() => User::find()
            ->select('id')
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->andWhere(['not', ['steam_id' => null]]);

        $queued = $base()
            ->andWhere(['steam_synced_at' => null])
            ->orderBy(['id' => SORT_ASC]);

        $staleThreshold = date('Y-m-d H:i:s', strtotime('-' . self::STALE_AFTER_DAYS . ' days'));
        $stale = $base()
            ->andWhere(['<', 'steam_synced_at', $staleThreshold])
            ->orderBy(['steam_synced_at' => SORT_ASC]);

        if ($limit <= 0) {
            return array_merge($queued->column(), $stale->column());
        }

        $ids = $queued->limit($limit)->column();
        if (($remaining = $limit - count($ids)) > 0) {
            $ids = array_merge($ids, $stale->limit($remaining)->column());
        }

        return array_map('intval', $ids);
    }

    /**
     * Syncs up to $limit owned games' achievements (across all users), paced.
     * Each item is one GetPlayerAchievements call. Run this more aggressively
     * than the library sync — there's a lot of (user × game) work to chew
     * through on first fill.
     */
    public function actionSyncAchievements(int $limit = 100): int
    {
        $mutex = new FileMutex();
        if (!$mutex->acquire(self::LOCK_ACH)) {
            $this->stdout(self::LOCK_ACH . ": another run is already in progress — skipping.\n");
            return ExitCode::OK;
        }

        try {
            return $this->runAchievementsSync($limit);
        } finally {
            $mutex->release(self::LOCK_ACH);
        }
    }

    private function runAchievementsSync(int $limit): int
    {
        $userGameIds = $this->collectAchievementUserGameIds($limit);
        $total = count($userGameIds);
        if ($this->verbose) {
            $this->stdout("Syncing achievements for {$total} owned game(s)\n");
        }

        /** @var array<int, User> $users cache by id, reused across this user's games */
        $users = [];

        foreach ($userGameIds as $i => $ugId) {
            $userGame = UserGame::findOne(['id' => $ugId]);
            if ($userGame === null) {
                continue;
            }
            $user = $users[$userGame->user_id] ??= User::findOne(['id' => $userGame->user_id]);
            if ($user === null) {
                continue;
            }

            $position = $i + 1;
            try {
                $applied = (new SteamAchievementSync($user))->syncGame($userGame);
                if ($this->verbose) {
                    $label = $applied ? "{$userGame->ach_unlocked}/{$userGame->ach_total}" : 'retry';
                    $this->stdout("[{$position}/{$total}] {$userGame->getDisplayTitle()} (ug#{$ugId}): {$label}\n");
                }
            } catch (\Throwable $e) {
                $this->stderr("[{$position}/{$total}] ug#{$ugId} failed: {$e->getMessage()}\n");
            }

            sleep(random_int(self::ACH_DELAY_MIN, self::ACH_DELAY_MAX));
        }

        return ExitCode::OK;
    }

    /**
     * Ordered user_game ids whose achievements need syncing: never-synced first
     * (ach_synced_at NULL, which MySQL sorts ahead in ASC), most-played first
     * within that, then stale ones. Limited to in-catalogue games of active,
     * public-profile accounts.
     *
     * @return int[]
     */
    private function collectAchievementUserGameIds(int $limit): array
    {
        $query = UserGame::find()
            ->alias('ug')
            ->select('ug.id')
            ->innerJoin('{{%user}} u', 'u.id = ug.user_id')
            ->where(['u.status' => User::STATUS_ACTIVE, 'u.steam_visibility' => SteamApi::VISIBILITY_PUBLIC])
            ->andWhere(['not', ['u.steam_id' => null]])
            ->andWhere(['not', ['ug.game_id' => null]]);

        $staleThreshold = date('Y-m-d H:i:s', strtotime('-' . self::ACH_STALE_AFTER_DAYS . ' days'));
        $query->andWhere(['or', ['ug.ach_synced_at' => null], ['<', 'ug.ach_synced_at', $staleThreshold]]);

        // NULL ach_synced_at sorts first in ASC; play the most-played first.
        $query->orderBy(['ug.ach_synced_at' => SORT_ASC, 'ug.playtime_minutes' => SORT_DESC]);

        if ($limit > 0) {
            $query->limit($limit);
        }

        return array_map('intval', $query->column());
    }
}
