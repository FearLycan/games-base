<?php

namespace common\components\steam;

use Yii;
use yii\httpclient\Client as HttpClient;

/**
 * Thin wrapper over the public Steam Web API used for per-user data (profile,
 * owned games). All calls need our Web API key (params `steamkey`) and only
 * return data for profiles whose privacy allows it — a private profile yields
 * an empty payload, not an error. Network/HTTP failures return null so callers
 * can distinguish "couldn't fetch" from "fetched, nothing there".
 */
class SteamApi
{
    public const int VISIBILITY_PRIVATE = 1;
    public const int VISIBILITY_PUBLIC  = 3;

    private const string SUMMARY_URL  = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';
    private const string OWNED_URL    = 'https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/';
    private const string ACH_URL      = 'https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/';
    private const string WISHLIST_URL = 'https://api.steampowered.com/IWishlistService/GetWishlist/v1/';

    private ?string $key;

    public function __construct(?string $key = null)
    {
        $this->key = $key ?? (Yii::$app->params['steamkey'] ?? null);
    }

    public function hasKey(): bool
    {
        return !empty($this->key);
    }

    /**
     * Public profile summary (personaname, avatarfull, profileurl,
     * communityvisibilitystate), or null on failure / missing key.
     *
     * @return array<string, mixed>|null
     */
    public function getPlayerSummary(string $steamId): ?array
    {
        $data = $this->get(self::SUMMARY_URL, ['steamids' => $steamId]);

        return $data['response']['players'][0] ?? null;
    }

    /**
     * Owned games for a SteamID64. Returns `['game_count' => int, 'games' => []]`
     * (games carry appid, name, playtime_forever in minutes, img_icon_url,
     * rtime_last_played). A private "game details" profile returns an empty
     * payload — i.e. game_count 0 and no games. Null on request failure.
     *
     * @return array{game_count:int, games:array<int,array<string,mixed>>}|null
     */
    public function getOwnedGames(string $steamId): ?array
    {
        $data = $this->get(self::OWNED_URL, [
            'steamid'                  => $steamId,
            'include_appinfo'          => 1,
            'include_played_free_games' => 1,
        ]);
        if ($data === null) {
            return null;
        }

        $response = $data['response'] ?? [];

        return [
            'game_count' => (int)($response['game_count'] ?? 0),
            'games'      => $response['games'] ?? [],
        ];
    }

    /**
     * A user's wishlist: a list of {appid, priority, date_added}. Empty array
     * when the wishlist is private/empty; null on request failure.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getWishlist(string $steamId): ?array
    {
        $data = $this->get(self::WISHLIST_URL, ['steamid' => $steamId]);
        if ($data === null) {
            return null;
        }

        return $data['response']['items'] ?? [];
    }

    /**
     * A user's achievements for one game: a list of {apiname, achieved (0/1),
     * unlocktime}. Returns:
     *   - the achievements array on success (may be empty),
     *   - [] when the game has no stats or the profile hides them (Steam answers
     *     success:false, often with a non-2xx status but a JSON body),
     *   - null only on a transport failure worth retrying.
     * No schema is needed — the count of achieved entries gives completion %.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getPlayerAchievements(string $steamId, int $appid): ?array
    {
        if (!$this->hasKey()) {
            return null;
        }

        try {
            $response = (new HttpClient())
                ->createRequest()
                ->setMethod('GET')
                ->setUrl(self::ACH_URL)
                ->setData(['key' => $this->key, 'steamid' => $steamId, 'appid' => $appid, 'l' => 'english', 'format' => 'json'])
                ->send();
        } catch (\Throwable) {
            return null;
        }

        // Steam returns the success flag in the body even on a 400/403, so read
        // data regardless of status; only a missing body is a real failure.
        $stats = is_array($response->data) ? ($response->data['playerstats'] ?? []) : null;
        if ($stats === null) {
            return $response->isOk ? [] : null;
        }

        if (empty($stats['success'])) {
            return [];
        }

        return $stats['achievements'] ?? [];
    }

    /**
     * GETs a Steam API endpoint with the key applied, returning decoded data or
     * null on a missing key, transport error, or non-2xx response.
     *
     * @param array<string, scalar> $params
     * @return array<string, mixed>|null
     */
    private function get(string $url, array $params): ?array
    {
        if (!$this->hasKey()) {
            return null;
        }

        try {
            $response = (new HttpClient())
                ->createRequest()
                ->setMethod('GET')
                ->setUrl($url)
                ->setData(['key' => $this->key, 'format' => 'json'] + $params)
                ->send();
        } catch (\Throwable) {
            return null;
        }

        return $response->isOk ? ($response->data ?? null) : null;
    }
}
