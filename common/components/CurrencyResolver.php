<?php

namespace common\components;

use GeoIp2\Database\Reader;
use Yii;

/**
 * Picks the currency to show store-offer prices in:
 *   Poland → PLN, rest of Europe → EUR, everywhere else → USD (configurable).
 *
 * Resolution order, first hit wins:
 *   1. The visitor's explicit choice (currency switcher, stored in a cookie).
 *   2. Their country, from the MaxMind GeoLite2 database (`params['geoip_db']`).
 *   3. The fallback currency (`params['offerCurrency']`).
 *
 * Everything degrades gracefully: a missing GeoLite2 file or the geoip2 library
 * not being installed simply falls through to the fallback currency — the page
 * never errors over currency detection.
 */
class CurrencyResolver
{
    /** Currencies we actually store prices in (see {@see \common\models\GameOfferPrice}). */
    public const array SUPPORTED = ['EUR', 'USD', 'PLN'];

    private const string COOKIE = 'currency';

    /**
     * European country codes that should see EUR. Poland is handled separately
     * (PLN). Non-euro European countries are included on purpose: we only stock
     * EUR/USD/PLN, and EUR is the closest fit for them.
     */
    private const array EUROPE = [
        'AD', 'AL', 'AT', 'BA', 'BE', 'BG', 'BY', 'CH', 'CY', 'CZ', 'DE', 'DK',
        'EE', 'ES', 'FI', 'FO', 'FR', 'GB', 'GG', 'GI', 'GR', 'HR', 'HU', 'IE',
        'IM', 'IS', 'IT', 'JE', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME', 'MK',
        'MT', 'NL', 'NO', 'PT', 'RO', 'RS', 'SE', 'SI', 'SK', 'SM', 'UA', 'VA',
        'XK',
    ];

    private static ?string $resolved = null;

    /**
     * The currency to display for the current visitor. Memoized per request.
     */
    public static function forVisitor(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $choice = self::userChoice();
        if ($choice !== null) {
            return self::$resolved = $choice;
        }

        return self::$resolved = self::fromCountry(self::detectCountry());
    }

    /**
     * The visitor's explicit currency choice from the switcher, or null.
     *
     * Read straight from $_COOKIE (not the validated cookie collection) because
     * the switcher sets it client-side; it's a non-sensitive preference.
     */
    public static function userChoice(): ?string
    {
        $value = strtoupper((string)($_COOKIE[self::COOKIE] ?? ''));

        return in_array($value, self::SUPPORTED, true) ? $value : null;
    }

    /**
     * Maps an ISO country code to a currency, falling back to `offerCurrency`.
     */
    public static function fromCountry(?string $country): string
    {
        $country = strtoupper((string)$country);

        if ($country === 'PL') {
            return 'PLN';
        }

        if (in_array($country, self::EUROPE, true)) {
            return 'EUR';
        }

        return self::fallback();
    }

    public static function fallback(): string
    {
        $default = strtoupper((string)(Yii::$app->params['offerCurrency'] ?? 'USD'));

        return in_array($default, self::SUPPORTED, true) ? $default : 'USD';
    }

    /**
     * ISO country code for the visitor's IP via GeoLite2, or null when it can't
     * be determined (missing DB/library, private/invalid IP, lookup miss).
     */
    private static function detectCountry(): ?string
    {
        $dbPath = Yii::getAlias((string)(Yii::$app->params['geoip_db'] ?? ''), false);
        $ip = Yii::$app->request->userIP ?? null;

        if (!$dbPath || !is_file($dbPath) || !$ip) {
            return null;
        }

        try {
            $reader = new Reader($dbPath);

            return $reader->country($ip)->country->isoCode;
        } catch (\Throwable) {
            return null;
        }
    }
}
