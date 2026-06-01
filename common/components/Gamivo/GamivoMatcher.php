<?php

namespace common\components\Gamivo;

/**
 * Matches one of our games against Gamivo search hits.
 *
 * Gamivo hits carry no Steam appid, so matching is by title only. Like Instant
 * Gaming, we require the *normalized* title to be exactly equal — never a
 * substring — so a base game never silently matches an edition or a sequel.
 *
 * The catch: Gamivo bakes the region and platform (and sometimes a language
 * list) into the product `name`, e.g. "007: First Light EU Steam" or
 * "Baldur's Gate 3 EN/DE/FR Global Steam". We strip those *structured* trailing
 * tokens (region.region + platform.title, then a language token) to recover the
 * bare title before comparing. The slug is deliberately NOT used: the same game
 * appears under inconsistent slugs ("baldurs-gate-iii" vs "baldurs-gate-3-…",
 * "stalker-2" vs "s-t-a-l-k-e-r-2"), so it can't be trusted for matching.
 *
 * Region matters: a Global key is a clean match (high confidence), while a
 * region-locked one (EU, ROW, Latin America, …) is flagged for review.
 */
class GamivoMatcher
{
    public const string CONFIDENCE_HIGH = 'high';
    public const string CONFIDENCE_LOW  = 'low';

    /**
     * Uppercase language codes Gamivo appends to names. Used to strip a trailing
     * single-code token (multi-code lists like "EN/DE/FR" are handled by the
     * slash rule). Kept to real language codes so sequel/edition markers such as
     * "II", "VR" or "HD" are never stripped — that would cause false matches.
     */
    private const array LANGUAGE_CODES = [
        'EN', 'DE', 'FR', 'IT', 'ES', 'PL', 'RU', 'PT', 'JA', 'JP', 'KO', 'ZH',
        'NL', 'CS', 'TR', 'SV', 'DA', 'NO', 'FI', 'AR', 'EL', 'HU', 'UK', 'RO',
        'BG', 'HR', 'SK', 'TH', 'VI', 'ID',
    ];

    /**
     * @param array<int, array<string, mixed>> $hits
     * @return array{hit: array<string, mixed>, confidence: string}|null
     */
    public function match(string $title, array $hits): ?array
    {
        $needle = $this->normalize($title);
        if ($needle === '') {
            return null;
        }

        $exact = [];
        foreach ($hits as $hit) {
            // Defence in depth: the search already filters these, but never let
            // a gift/account/DLC variant through if the query ever changes.
            if (($hit['platform']['slug'] ?? '') !== 'steam'
                || ($hit['productType']['slug'] ?? '') !== 'games') {
                continue;
            }

            foreach ($this->candidateTitles($hit) as $candidate) {
                if ($this->normalize($candidate) === $needle) {
                    $exact[] = $hit;
                    break;
                }
            }
        }

        if (!$exact) {
            return null;
        }

        // Pick order, preferring an offer the buyer can actually purchase:
        //   1. buyable Global    → high confidence (clean, region-free key)
        //   2. buyable elsewhere → low (region-locked, needs a review)
        //   3. any Global        → high (out of stock; caller skips, but keep it)
        //   4. anything matched  → low
        // This keeps IG's "Global = confident" rule while still surfacing a
        // region-locked SKU when the Global one has no active offers.
        $buyableGlobal = $buyableOther = $anyGlobal = null;
        foreach ($exact as $hit) {
            $isGlobal = strtolower((string)($hit['region']['slug'] ?? '')) === 'global';
            $buyable = $this->isBuyable($hit);

            if ($isGlobal && $buyable) {
                $buyableGlobal ??= $hit;
            } elseif ($buyable) {
                $buyableOther ??= $hit;
            }
            if ($isGlobal) {
                $anyGlobal ??= $hit;
            }
        }

        if ($buyableGlobal !== null) {
            return ['hit' => $buyableGlobal, 'confidence' => self::CONFIDENCE_HIGH];
        }
        if ($buyableOther !== null) {
            return ['hit' => $buyableOther, 'confidence' => self::CONFIDENCE_LOW];
        }
        if ($anyGlobal !== null) {
            return ['hit' => $anyGlobal, 'confidence' => self::CONFIDENCE_HIGH];
        }

        return ['hit' => $exact[0], 'confidence' => self::CONFIDENCE_LOW];
    }

    /** A hit is buyable when it's in stock and has a positive price. */
    private function isBuyable(array $hit): bool
    {
        return (bool)($hit['inStock'] ?? false) && (float)($hit['lowestPrice'] ?? 0) > 0;
    }

    /**
     * Candidate bare titles for a hit: the name (and displayName) with the
     * trailing platform + region stripped, plus a variant with a trailing
     * language token also removed. Comparing against all of them lets a game
     * match whether or not Gamivo tagged the SKU with a language list.
     *
     * @param array<string, mixed> $hit
     * @return array<int, string>
     */
    private function candidateTitles(array $hit): array
    {
        $platform = (string)($hit['platform']['title'] ?? '');
        $region   = (string)($hit['region']['region'] ?? '');

        $candidates = [];
        foreach ([$hit['name'] ?? '', $hit['displayName'] ?? ''] as $name) {
            $bare = $this->stripSuffixWord((string)$name, $platform);
            $bare = $this->stripSuffixWord($bare, $region);

            $candidates[$bare] = true;

            $noLang = $this->stripLanguage($bare);
            if ($noLang !== $bare) {
                $candidates[$noLang] = true;
            }
        }

        return array_keys($candidates);
    }

    /** Removes a trailing " $suffix" (case-insensitive) when present. */
    private function stripSuffixWord(string $value, string $suffix): string
    {
        $value = trim($value);
        $suffix = trim($suffix);
        if ($suffix === '') {
            return $value;
        }

        $needle = ' ' . mb_strtolower($suffix);
        if (str_ends_with(mb_strtolower($value), $needle)) {
            return trim(mb_substr($value, 0, mb_strlen($value) - mb_strlen($needle)));
        }

        return $value;
    }

    /**
     * Removes a trailing language token: either a slash-joined list
     * ("EN/DE/FR/IT") or a single known language code ("EN"). Anything that
     * isn't clearly a language is left in place so distinct products never
     * collapse onto the same title.
     */
    private function stripLanguage(string $value): string
    {
        $value = trim($value);

        // Multi-language list, e.g. "EN/DE/FR/IT/PL/RU/ZH/ES" — unambiguous.
        $value = preg_replace('/\s+[A-Z]{2,}(?:\/[A-Z]{2,})+$/u', '', $value) ?? $value;
        $value = trim($value);

        // Single trailing language code from the whitelist.
        if (preg_match('/\s+([A-Z]{2,})$/u', $value, $m)
            && in_array($m[1], self::LANGUAGE_CODES, true)) {
            $value = trim(mb_substr($value, 0, mb_strlen($value) - mb_strlen($m[0])));
        }

        return $value;
    }

    /**
     * Lowercase, strip trademark marks and punctuation, collapse whitespace.
     * Intentionally does NOT strip edition words: doing so would let different
     * products collapse onto the same key and create false matches.
     */
    public function normalize(string $title): string
    {
        $title = mb_strtolower(trim($title));
        $title = str_replace(['™', '®', '©'], '', $title);
        // Punctuation and separators → spaces.
        $title = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title) ?? '';

        return trim(preg_replace('/\s+/', ' ', $title) ?? '');
    }
}
