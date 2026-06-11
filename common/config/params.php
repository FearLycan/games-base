<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'steamgriddb_api_key' => '',
    'kinguin_api_key' => '',

    // Fallback currency for store-offer prices (rest of world / when geo and
    // the user's choice are both unavailable). See common\components\CurrencyResolver.
    'offerCurrency' => 'USD',

    // MaxMind GeoLite2 country database used to pick the display currency by
    // visitor location. When the file is missing, currency falls back to
    // `offerCurrency`. Keep it fresh with `php yii geo-ip/update` (cron weekly).
    'geoip_db'          => '@common/data/GeoLite2-Country.mmdb',
    'geoip_edition'     => 'GeoLite2-Country',
    'geoip_license_key' => '', // free MaxMind license key — set in params-local.php

    // Instant Gaming integration. Algolia credentials are the public,
    // search-only keys exposed in IG's own page source. Set `affiliate_query`
    // (e.g. 'igr=yourusername') in params-local.php to monetize outbound links.
    'instant_gaming' => [
        'algolia_app_id'     => 'QKNHP8TC3Y',
        'algolia_search_key' => '93946b91c013211f842ddf1819ea880b',
        'algolia_index'      => 'produits_en',
        'currencies'         => ['EUR', 'USD', 'PLN'],
        'affiliate_query'    => '',
    ],

    // Gamivo integration. The storefront is behind Cloudflare, but its product
    // search is a public Elasticsearch endpoint (search.gamivo.com) and the
    // currency rates feed (/api/currency/list, EUR base) is public too — both
    // sit outside Cloudflare, so no scraping is involved. Set `affiliate_query`
    // (e.g. 'glv=yourid') in params-local.php to monetize outbound links.
    'gamivo' => [
        'elastic_url'     => 'https://search.gamivo.com/',
        'currency_url'    => 'https://www.gamivo.com/api/currency/list',
        'currencies'      => ['EUR', 'USD', 'PLN'],
        'affiliate_query' => '',
        // Cloudflare blocks datacenter IPs (production gets 403). Set a proxy in
        // params-local.php to route the two outbound calls through an allowed IP:
        //   'proxy'      => 'http://user:pass@host:port'  // or socks5://host:1080
        //   'proxy_auth' => 'user:pass'                   // if not in the URL
        'proxy'           => '',
        'proxy_auth'      => '',
        'timeout'         => 20,
    ],

    // GameSeal integration. GameSeal runs on Shopware 6 behind Cloudflare and
    // its Store API is disabled, so there is no JSON search endpoint — we scrape
    // the storefront's AJAX `suggest` dropdown. Prices render in EUR on the
    // default (prefix-less) storefront; we convert to the other currencies with
    // ECB reference rates. Set `affiliate_query` (e.g. 'ref=yourid') in
    // params-local.php to monetize outbound links. If the server's datacenter IP
    // gets a Cloudflare challenge, set a residential `proxy` (see `gameseal`
    // notes mirror the Gamivo ones). See common\components\GameSeal\GsClient.
    'gameseal' => [
        'home_url'        => 'https://gameseal.com/',
        'suggest_url'     => 'https://gameseal.com/suggest',
        'rates_url'       => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
        'currencies'      => ['EUR', 'USD', 'PLN'],
        'affiliate_query' => '',
        'proxy'           => '',
        'proxy_auth'      => '',
        'timeout'         => 20,
    ],

    // Kinguin integration. Kinguin offers a first-party authenticated REST API
    // (the ESA gateway), so there's nothing to scrape and no Cloudflare — every
    // call carries the key from the top-level `kinguin_api_key` param (set in
    // params-local.php). Products expose a `steam` appid, so we match by appid
    // (with a title/edition guard) rather than by title text alone. Prices are
    // EUR; we convert to the other currencies with ECB reference rates. Set
    // `affiliate_query` (e.g. 'ref=yourid') in params-local.php to monetize
    // outbound links. See common\components\Kinguin\KinguinClient.
    'kinguin' => [
        'api_url'         => 'https://gateway.kinguin.net/esa/api/v1',
        'rates_url'       => 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml',
        'currencies'      => ['EUR', 'USD', 'PLN'],
        // Affiliate monetization. Kinguin's program (and the networks Awin / CJ /
        // Admitad / MyLead) uses a tracking-redirect *deeplink* that WRAPS the
        // product URL — not a query param like Instant Gaming. Set
        // `affiliate_deeplink` in params-local.php with a `{url}` placeholder for
        // the URL-encoded destination, e.g.
        //   'https://tracking.affiliateclub.cz/affc?offerid=1464&affid=ME&affsub5={url}'  // Kinguin own
        //   'https://www.awin1.com/cread.php?awinmid=XXXX&awinaffid=YYYY&ued={url}'        // Awin
        //   'https://ad.admitad.com/g/XXXX/?ulp={url}'                                     // Admitad
        // `affiliate_query` is the fallback append-style param (rarely used here).
        // Commission ~2-8%, 30-day cookie; excluded countries: DE, FR, US, AU.
        'affiliate_deeplink' => '',
        'affiliate_query'    => '',
        'proxy'           => '',
        'proxy_auth'      => '',
        'timeout'         => 20,
    ],
];
