<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'steamgriddb_api_key' => '',

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
];
