<?php

namespace common\components;

use common\models\CompanyProfile;
use common\models\Game;
use Yii;
use yii\db\Query;

/**
 * Builds the compact, data-driven career timeline shown on developer, publisher
 * and combined company pages: the founding event, the studio's headline
 * releases (one per year, the most-reviewed) and a present/closed marker.
 *
 * Shared by all three controllers so the selection logic lives in one place.
 * The result is capped at $maxReleases release rows, keeping the rail short
 * even for very prolific companies.
 */
class CompanyTimeline
{
    /**
     * How long the (DB-heavy) release rows stay cached. The highlight list
     * barely shifts — new blockbusters are rare and reviews move slowly — so a
     * week keeps the data fresh enough while sparing every sort/role/page
     * variation from recomputing it.
     */
    public const CACHE_DURATION = 604800; // 7 days

    /** Default number of headline releases shown on the timeline. */
    public const MAX_RELEASES = 10;

    /**
     * "At a glance" stat cards for a single-role page (developer or publisher):
     * games count, dominant genre, average rating and the founding year.
     *
     * @param array{games:int,topGenre:?string,avgRating:?int} $stats
     * @return array<int,array{value:string|int,label:string,accent?:bool}>
     */
    public static function statCards(array $stats, ?CompanyProfile $profile, string $gamesLabel): array
    {
        $cards = [['value' => number_format($stats['games']), 'label' => $gamesLabel]];
        if ($stats['topGenre']) {
            $cards[] = ['value' => $stats['topGenre'], 'label' => 'Most common genre', 'accent' => true];
        }
        if ($stats['avgRating'] !== null) {
            $cards[] = ['value' => $stats['avgRating'] . '%', 'label' => 'Avg. Steam rating'];
        }
        if ($profile?->founded_year) {
            $cards[] = ['value' => (int)$profile->founded_year, 'label' => $profile->isClosed() ? 'Founded' : 'Active since'];
        }

        return $cards;
    }

    /**
     * @return array<int,array{type:string,year:int,label:string,appid?:int,slug?:string,image?:string,note?:string}>
     */
    public static function build(?int $developerId, ?int $publisherId, ?CompanyProfile $profile, int $totalCount, int $maxReleases = self::MAX_RELEASES): array
    {
        // Only the release rows are expensive (and stable), so just those are
        // cached; the founding/present markers below are cheap and depend on
        // live profile data, so they're assembled fresh each call.
        $entries = self::releaseRows($developerId, $publisherId, $maxReleases);

        if ($profile?->founded_year) {
            $where = $profile->city ?: $profile->country;
            $entries[] = [
                'type'  => 'founded',
                'year'  => (int)$profile->founded_year,
                'label' => 'Founded' . ($where ? ' in ' . $where : ''),
            ];
        }

        if ($profile?->isClosed()) {
            $entries[] = [
                'type'  => 'closed',
                'year'  => (int)$profile->closed_year,
                'label' => 'Studio winds down operations.',
            ];
        } else {
            $entries[] = [
                'type'  => 'present',
                'year'  => (int)date('Y'),
                'label' => 'Active today — ' . number_format($totalCount) . ($totalCount === 1 ? ' game' : ' games') . ' tracked.',
            ];
        }

        // Chronological; on a year tie keep founding first and present/closed last.
        $rank = ['founded' => 0, 'release' => 1, 'present' => 2, 'closed' => 2];
        usort($entries, static fn($a, $b) => [$a['year'], $rank[$a['type']]] <=> [$b['year'], $rank[$b['type']]]);

        return $entries;
    }

    /**
     * Cached, self-contained release rows (no lazy ActiveRecord left behind):
     * one headline title per year, capped at $maxReleases biggest hits.
     *
     * @return array<int,array{type:string,year:int,label:string,appid:int,slug:string,image:string,note:string}>
     */
    private static function releaseRows(?int $developerId, ?int $publisherId, int $maxReleases): array
    {
        $key = ['company-timeline-releases', $developerId, $publisherId, $maxReleases];

        return Yii::$app->cache->getOrSet($key, static function () use ($developerId, $publisherId, $maxReleases) {
            return self::computeReleaseRows($developerId, $publisherId, $maxReleases);
        }, self::CACHE_DURATION);
    }

    /**
     * @return array<int,array{type:string,year:int,label:string,appid:int,slug:string,image:string,note:string}>
     */
    private static function computeReleaseRows(?int $developerId, ?int $publisherId, int $maxReleases): array
    {
        $ownership = ['or'];
        if ($developerId !== null) {
            $ownership[] = ['game.id' => (new Query())->select('game_id')->from('{{%game_developer}}')->where(['developer_id' => $developerId])];
        }
        if ($publisherId !== null) {
            $ownership[] = ['game.id' => (new Query())->select('game_id')->from('{{%game_publisher}}')->where(['publisher_id' => $publisherId])];
        }

        /** @var Game[] $games most-reviewed released titles across the given role(s) */
        $games = Game::find()
            ->alias('game')
            ->with('review')
            ->innerJoin('{{%review}} rv', 'rv.game_id = game.id')
            ->where(['game.status' => Game::STATUS_ACTIVE, 'game.type' => Game::TYPE_GAME])
            ->andWhere($ownership)
            ->andWhere(['not', ['game.release_date' => null]])
            ->andWhere(['>', 'rv.total_reviews', 0])
            ->orderBy(['rv.total_reviews' => SORT_DESC])
            ->limit(60)
            ->all();

        // Keep one headline release per calendar year — the most reviewed, since
        // the list is already sorted by review count descending.
        $perYear = [];
        foreach ($games as $game) {
            $ts = strtotime((string)$game->release_date);
            if (!$ts) {
                continue;
            }
            $perYear[(int)date('Y', $ts)] ??= $game;
        }

        // Cap to the biggest moments, then restore chronological order.
        if (count($perYear) > $maxReleases) {
            uasort($perYear, static fn(Game $a, Game $b) => (int)$b->review->total_reviews <=> (int)$a->review->total_reviews);
            $perYear = array_slice($perYear, 0, $maxReleases, true);
        }
        ksort($perYear);

        $rows = [];
        foreach ($perYear as $year => $game) {
            $reviews  = (int)$game->review->total_reviews;
            $positive = (int)$game->review->total_positive;
            $rating   = $reviews > 0 ? (int)round($positive / $reviews * 100) : null;
            $rows[] = [
                'type'  => 'release',
                'year'  => $year,
                'label' => (string)$game->title,
                'appid' => (int)$game->steam_appid,
                'slug'  => (string)$game->slug,
                'image' => $game->getHeader(),
                'note'  => number_format($reviews) . ' reviews' . ($rating !== null ? ' · ' . $rating . '%' : ''),
            ];
        }

        return $rows;
    }
}
