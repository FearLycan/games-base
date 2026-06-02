<?php

namespace frontend\modules\user\models;

use common\models\Game;
use common\models\GameOffer;
use common\models\Review;
use common\models\UserAchievement;
use common\models\UserGame;
use common\models\UserWishlist;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

/**
 * Aggregated profile statistics for the dashboard, computed from the user's
 * catalogued library + achievements. The headline aggregates run as a single
 * grouped query and are cached per user; the top-N lists are small, eager-loaded
 * queries. Keeps the view logic-free.
 */
class ProfileStats
{
    private const int CACHE_TTL = 1800;

    /** Recommendations must have at least this many Steam reviews (reputation floor). */
    private const int MIN_RECOMMEND_REVIEWS = 500;

    /** @var array<string,int>|null memoised headline aggregates */
    private ?array $agg = null;

    public function __construct(private readonly int $userId)
    {
    }

    /**
     * One grouped pass over the user's catalogued games. Cached per user.
     *
     * @return array<string,int>
     */
    private function aggregates(): array
    {
        return $this->agg ??= Yii::$app->cache->getOrSet(
            ['user-profile-stats', $this->userId],
            function (): array {
                $row = (new Query())
                    ->select([
                        'games'        => 'COUNT(*)',
                        'playtime'     => 'COALESCE(SUM(ug.playtime_minutes), 0)',
                        'played'       => 'SUM(CASE WHEN ug.playtime_minutes > 0 THEN 1 ELSE 0 END)',
                        'ach_games'    => 'SUM(CASE WHEN ug.ach_total > 0 THEN 1 ELSE 0 END)',
                        'perfect'      => 'SUM(CASE WHEN ug.ach_total > 0 AND ug.ach_unlocked >= ug.ach_total THEN 1 ELSE 0 END)',
                        'ach_unlocked' => 'COALESCE(SUM(ug.ach_unlocked), 0)',
                        'ach_total'    => 'COALESCE(SUM(CASE WHEN ug.ach_total > 0 THEN ug.ach_total ELSE 0 END), 0)',
                    ])
                    ->from(['ug' => UserGame::tableName()])
                    ->innerJoin(['g' => Game::tableName()], 'g.id = ug.game_id')
                    ->where(['ug.user_id' => $this->userId, 'g.status' => Game::STATUS_ACTIVE])
                    ->andWhere(['not', ['g.title' => null]])
                    ->one();

                return array_map('intval', $row ?: []);
            },
            self::CACHE_TTL
        );
    }

    public function gamesCount(): int
    {
        return $this->aggregates()['games'] ?? 0;
    }

    public function playtimeHours(): int
    {
        return (int)round(($this->aggregates()['playtime'] ?? 0) / 60);
    }

    public function playedCount(): int
    {
        return $this->aggregates()['played'] ?? 0;
    }

    public function neverPlayedCount(): int
    {
        return max(0, $this->gamesCount() - $this->playedCount());
    }

    public function achievementsUnlocked(): int
    {
        return $this->aggregates()['ach_unlocked'] ?? 0;
    }

    public function perfectCount(): int
    {
        return $this->aggregates()['perfect'] ?? 0;
    }

    public function achievementGamesCount(): int
    {
        return $this->aggregates()['ach_games'] ?? 0;
    }

    /** Overall achievement completion across games that have achievements (0–100). */
    public function completionPercent(): int
    {
        $total = $this->aggregates()['ach_total'] ?? 0;
        if ($total <= 0) {
            return 0;
        }

        return (int)round(($this->aggregates()['ach_unlocked'] ?? 0) / $total * 100);
    }

    /**
     * @return UserGame[]
     */
    public function topPlayed(int $limit = 6): array
    {
        return $this->cataloguedGames()
            ->andWhere(['>', 'user_game.playtime_minutes', 0])
            ->orderBy(['user_game.playtime_minutes' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * @return UserGame[]
     */
    public function recentlyPlayed(int $limit = 6): array
    {
        return $this->cataloguedGames()
            ->andWhere(['not', ['user_game.last_played_at' => null]])
            ->orderBy(['user_game.last_played_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * @return UserGame[]
     */
    public function perfectGames(int $limit = 6): array
    {
        return $this->cataloguedGames()
            ->andWhere('user_game.ach_total > 0 AND user_game.ach_unlocked >= user_game.ach_total')
            ->orderBy(['user_game.playtime_minutes' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Top genres across the user's catalogued games.
     *
     * @return array<int, array{name:string, count:int}>
     */
    public function topGenres(int $limit = 8): array
    {
        return Yii::$app->cache->getOrSet(
            ['user-profile-genres', $this->userId, $limit],
            fn(): array => (new Query())
                ->select(['name' => 'genre.name', 'count' => 'COUNT(*)'])
                ->from(['ug' => UserGame::tableName()])
                ->innerJoin(['g' => Game::tableName()], 'g.id = ug.game_id AND g.status = ' . Game::STATUS_ACTIVE)
                ->innerJoin('{{%game_genre}} gg', 'gg.game_id = g.id')
                ->innerJoin('{{%genre}} genre', 'genre.id = gg.genre_id')
                ->where(['ug.user_id' => $this->userId])
                ->groupBy('genre.id')
                ->orderBy(['count' => SORT_DESC])
                ->limit($limit)
                ->all(),
            self::CACHE_TTL
        );
    }

    /** The rarest achievement the user has unlocked (lowest global %), or null. */
    public function rarestAchievement(): ?UserAchievement
    {
        return UserAchievement::find()
            ->alias('ua')
            ->innerJoinWith('achievement')
            ->innerJoinWith('game')
            ->where(['ua.user_id' => $this->userId, 'game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['not', ['game_achievement.percent' => null]])
            ->orderBy(['game_achievement.percent' => SORT_ASC])
            ->one();
    }

    /** Average playtime (hours) across games the user has actually played. */
    public function avgPlaytimeHours(): int
    {
        $played = $this->playedCount();
        if ($played <= 0) {
            return 0;
        }

        return (int)round(($this->aggregates()['playtime'] ?? 0) / $played / 60);
    }

    /** Share of the library never launched (0–100). */
    public function backlogPercent(): int
    {
        $games = $this->gamesCount();
        return $games > 0 ? (int)round($this->neverPlayedCount() / $games * 100) : 0;
    }

    /**
     * Counts of unlocked achievements by rarity tier.
     *
     * @return array{ultra:int,rare:int}
     */
    public function rarityCounts(): array
    {
        return Yii::$app->cache->getOrSet(['user-rarity', $this->userId], function (): array {
            $row = (new Query())
                ->select([
                    'ultra' => 'SUM(CASE WHEN ga.percent < 5 THEN 1 ELSE 0 END)',
                    'rare'  => 'SUM(CASE WHEN ga.percent >= 5 AND ga.percent < 20 THEN 1 ELSE 0 END)',
                ])
                ->from(['ua' => UserAchievement::tableName()])
                ->innerJoin(['ga' => 'game_achievement'], 'ga.game_id = ua.game_id AND ga.api_name = ua.api_name')
                ->where(['ua.user_id' => $this->userId])
                ->andWhere(['not', ['ga.percent' => null]])
                ->one();

            return ['ultra' => (int)($row['ultra'] ?? 0), 'rare' => (int)($row['rare'] ?? 0)];
        }, self::CACHE_TTL);
    }

    /**
     * Total estimated Steam value (USD cents) of the catalogued library.
     */
    public function libraryValueCents(): int
    {
        return Yii::$app->cache->getOrSet(['user-lib-value', $this->userId], fn(): int => (int)(new Query())
            ->select(new Expression('COALESCE(SUM(g.steam_price_final), 0)'))
            ->from(['ug' => UserGame::tableName()])
            ->innerJoin(['g' => Game::tableName()], 'g.id = ug.game_id')
            ->where(['ug.user_id' => $this->userId, 'g.status' => Game::STATUS_ACTIVE])
            ->scalar(), self::CACHE_TTL);
    }

    /** Formatted library value, e.g. "$1,240" (whole dollars), or null when zero. */
    public function libraryValueLabel(): ?string
    {
        $cents = $this->libraryValueCents();
        return $cents > 0 ? '$' . number_format($cents / 100) : null;
    }

    /**
     * Top genres by total playtime.
     *
     * @return array<int, array{name:string, minutes:int}>
     */
    public function topGenresByPlaytime(int $limit = 8): array
    {
        return Yii::$app->cache->getOrSet(['user-genres-hours', $this->userId, $limit], fn(): array => (new Query())
            ->select(['name' => 'genre.name', 'minutes' => 'SUM(ug.playtime_minutes)'])
            ->from(['ug' => UserGame::tableName()])
            ->innerJoin(['g' => Game::tableName()], 'g.id = ug.game_id AND g.status = ' . Game::STATUS_ACTIVE)
            ->innerJoin('{{%game_genre}} gg', 'gg.game_id = g.id')
            ->innerJoin('{{%genre}} genre', 'genre.id = gg.genre_id')
            ->where(['ug.user_id' => $this->userId])
            ->andWhere(['>', 'ug.playtime_minutes', 0])
            ->groupBy('genre.id')
            ->orderBy(['minutes' => SORT_DESC])
            ->limit($limit)
            ->all(), self::CACHE_TTL);
    }

    /**
     * The user's most recently unlocked achievements (with game + schema).
     *
     * @return UserAchievement[]
     */
    public function recentUnlocks(int $limit = 10): array
    {
        return UserAchievement::find()
            ->alias('ua')
            ->innerJoinWith('achievement')
            ->innerJoinWith('game')
            ->where(['ua.user_id' => $this->userId, 'game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['not', ['ua.unlocked_at' => null]])
            ->orderBy(['ua.unlocked_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Wishlisted catalogue games that are currently discounted on Steam, deepest
     * discount first, with offers eager-loaded for the price tile.
     *
     * @return UserWishlist[]
     */
    public function wishlistDeals(int $limit = 8): array
    {
        return UserWishlist::find()
            ->alias('uw')
            ->innerJoinWith('game')
            ->where(['uw.user_id' => $this->userId, 'game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['>', 'game.steam_price_initial', 0])
            ->andWhere('game.steam_price_final < game.steam_price_initial')
            ->orderBy('(game.steam_price_initial - game.steam_price_final) / game.steam_price_initial DESC')
            ->limit($limit)
            ->with(['game.gameOffers' => fn($q) => $q->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])->with(['store', 'prices'])])
            ->all();
    }

    /**
     * Games to buy, matched to the user's **tag taste profile** (Steam tags
     * weighted by how much they play games carrying them) and excluding ones
     * they already own. Ranked by how many of the user's top tags a game shares
     * (taste overlap), then by popularity. Tags + priced offers eager-loaded.
     *
     * @return Game[]
     */
    public function recommendedGames(int $limit = 8): array
    {
        $tagIds = array_keys($this->topTagsMap());
        if ($tagIds === []) {
            return [];
        }

        $owned = (new Query())
            ->select('steam_appid')
            ->from(UserGame::tableName())
            ->where(['user_id' => $this->userId]);

        return Game::find()
            ->alias('g')
            ->innerJoin('{{%game_tag}} gt', 'gt.game_id = g.id')
            ->leftJoin(['r' => Review::tableName()], 'r.game_id = g.id')
            ->where(['g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME, 'gt.tag_id' => $tagIds])
            ->andWhere(['>', 'g.steam_price_final', 0])
            ->andWhere(['not in', 'g.steam_appid', $owned])
            ->groupBy('g.id')
            // Keep recommendations reputable, not just tag-matchy: require a
            // minimum review count so niche games with many matching tags but a
            // tiny audience don't outrank well-loved ones.
            ->having(new Expression('MAX(r.total_reviews) >= ' . self::MIN_RECOMMEND_REVIEWS))
            // Most shared taste-tags first; popularity breaks ties.
            ->orderBy(new Expression('COUNT(DISTINCT gt.tag_id) DESC, MAX(r.total_reviews) DESC'))
            ->limit($limit)
            ->with(['tags', 'gameOffers' => fn($q) => $q->andWhere(['game_offer.status' => GameOffer::STATUS_ACTIVE])->with(['store', 'prices'])])
            ->all();
    }

    /**
     * Why a game is recommended: the specific tags it shares with the user's
     * taste profile, most-played first (up to $max), e.g. ["Open World",
     * "Story Rich", "RPG"]. Empty when there's no overlap.
     *
     * @return string[]
     */
    public function recommendationReasons(Game $game, int $max = 3): array
    {
        $taste = $this->topTagsMap();
        $gameTagIds = array_map(static fn($t): int => (int)$t->id, $game->tags);

        $shared = [];
        foreach ($taste as $tagId => $name) {
            if (in_array($tagId, $gameTagIds, true)) {
                $shared[] = $name;
            }
            if (count($shared) >= $max) {
                break;
            }
        }

        return $shared;
    }

    /**
     * The user's taste profile: tag id => name. Ranked TF-IDF-style — a tag
     * scores high when the user pours playtime into games carrying it AND it's
     * relatively uncommon in the catalogue (tag.games_count). That surfaces the
     * distinctive tags ("MOBA", "Extraction Shooter", "Roguelike") over generic
     * ones ("Action", "Singleplayer", "Indie"). The +50 smoothing keeps a single
     * ultra-rare tag from dominating. Cached.
     *
     * @return array<int,string>
     */
    private function topTagsMap(int $limit = 20): array
    {
        return Yii::$app->cache->getOrSet(['user-taste-tags', $this->userId, $limit], function () use ($limit): array {
            $rows = (new Query())
                ->select(['id' => 'gt.tag_id', 'name' => 'tag.name'])
                ->from(['ug' => UserGame::tableName()])
                ->innerJoin(['g' => Game::tableName()], 'g.id = ug.game_id AND g.status = ' . Game::STATUS_ACTIVE)
                ->innerJoin('{{%game_tag}} gt', 'gt.game_id = g.id')
                ->innerJoin('{{%tag}} tag', 'tag.id = gt.tag_id')
                ->where(['ug.user_id' => $this->userId])
                ->andWhere(['>', 'ug.playtime_minutes', 0])
                ->groupBy(['gt.tag_id', 'tag.name'])
                ->orderBy(new Expression('SUM(ug.playtime_minutes) / (GREATEST(tag.games_count, 1) + 50) DESC'))
                ->limit($limit)
                ->all();

            $map = [];
            foreach ($rows as $row) {
                $map[(int)$row['id']] = (string)$row['name'];
            }

            return $map;
        }, self::CACHE_TTL);
    }

    public function hasData(): bool
    {
        return $this->gamesCount() > 0;
    }

    /**
     * Base query: this user's rows whose game is in the catalogue, game eager-loaded.
     */
    private function cataloguedGames(): \yii\db\ActiveQuery
    {
        return UserGame::find()
            ->where(['user_game.user_id' => $this->userId])
            ->innerJoinWith('game')
            ->andWhere(['game.status' => Game::STATUS_ACTIVE])
            ->andWhere(['not', ['game.title' => null]]);
    }
}
