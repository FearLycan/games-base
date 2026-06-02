<?php

namespace common\components\auth;

use common\components\steam\SteamApi;
use yii\authclient\OpenId;

/**
 * "Sign in through Steam" client.
 *
 * Steam authenticates with OpenID 2.0: the redirect proves ownership of a
 * SteamID64 and nothing more — it grants no API access token. Profile details
 * (persona name, avatar, visibility) are pulled separately from the public Web
 * API with our own key, hence {@see fetchPlayerSummary()}.
 *
 * Steam advertises neither AX nor SReg and only supports identifier_select, so
 * the attribute lists stay empty and {@see buildAuthUrl()} forces it.
 */
class SteamOpenId extends OpenId
{
    public $authUrl = 'https://steamcommunity.com/openid';

    public function init()
    {
        parent::init();
        $this->requiredAttributes = [];
        $this->optionalAttributes = [];
    }

    /**
     * Steam only authenticates via identifier_select; force it regardless of
     * what Yadis discovery reports for the endpoint.
     */
    public function buildAuthUrl($identifierSelect = null)
    {
        return parent::buildAuthUrl(true);
    }

    protected function defaultName()
    {
        return 'steam';
    }

    protected function defaultTitle()
    {
        return 'Steam';
    }

    /**
     * The validated SteamID64 from the claimed identifier
     * (e.g. https://steamcommunity.com/openid/id/76561198000000000), or null
     * when the response carries no recognizable Steam identity. Only trust this
     * after {@see validate()} has returned true.
     */
    public function getSteamId(): ?string
    {
        $claimedId = (string)$this->getClaimedId();
        if (preg_match('#/id/(7656\d{10,})$#', $claimedId, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Public profile summary for a SteamID64 (personaname, avatarfull,
     * profileurl, communityvisibilitystate), or null when the key is missing or
     * the call fails. Errors are swallowed — a missing summary must not abort
     * sign-in (we already have a verified identity).
     *
     * @return array<string, mixed>|null
     */
    public function fetchPlayerSummary(string $steamId): ?array
    {
        return (new SteamApi())->getPlayerSummary($steamId);
    }
}
