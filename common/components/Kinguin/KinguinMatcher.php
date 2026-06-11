<?php

namespace common\components\Kinguin;

/**
 * Matches one of our games against Kinguin search hits.
 *
 * Kinguin is the one store that hands us a Steam appid per product (the `steam`
 * field), so — unlike Instant Gaming / Gamivo / GameSeal, which can only compare
 * titles — we lead with the appid and use the title as a guard. This matters
 * because Kinguin's product names are far messier than the other stores' (region,
 * version, language, alt-name and year tokens all baked in, e.g. "Resident Evil 4
 * / Biohazard 4 (2005) RoW PC Steam CD Key"), so strict title equality alone
 * would miss a lot — while the appid is exact.
 *
 * Two facts shape the rules:
 *   - Different games have different appids: Hades (1145360) vs Hades II
 *     (1145350), RE4 2005 (254700) vs RE4 2023 (2050650). So an appid that
 *     differs from ours is a hard reject — it weeds out sequels, remakes and DLC.
 *   - Editions SHARE the base appid: "Hogwarts Legacy Deluxe Edition" carries the
 *     same 990080 as the base game. So the appid alone can't tell a Deluxe/GOTY/
 *     bundle apart from the base — we reject any hit whose name adds an
 *     edition/bundle/DLC keyword our title doesn't have.
 *
 * A hit qualifies when it is a buyable Steam key (shared "account" SKUs are
 * skipped — they massively undercut and misrepresent the real key price) AND:
 *   - its `steam` appid equals ours and our title appears in its name, OR
 *   - it carries no appid but its bare title equals ours exactly (the strict
 *     fallback used for the other stores),
 *   and in neither case does it add an edition/bundle keyword we lack.
 *
 * Region matters for confidence: a region-free key is a clean match (high), while
 * a region-locked one (Europe, NA, RoW, …) is flagged for review. Among equally
 * good matches we keep the cheapest.
 */
class KinguinMatcher
{
    public const string CONFIDENCE_HIGH = 'high';
    public const string CONFIDENCE_LOW  = 'low';

    /**
     * Markers that turn a base title into a different (richer / multi-item) SKU.
     * A hit is rejected when its name contains one of these as a word and our own
     * title does not — that's how a Deluxe/GOTY/bundle/DLC sharing the base appid
     * is kept from masquerading as the base game. Deliberately conservative: it
     * omits words like "definitive"/"remastered"/"enhanced" that are routinely the
     * base product itself, to avoid rejecting a legitimate match.
     */
    private const array EDITION_MARKERS = [
        'deluxe', 'gold', 'ultimate', 'premium', 'complete', 'goty',
        'collection', 'bundle', 'pack', 'upgrade', 'dlc',
    ];

    /**
     * Multi-word edition phrases, checked as substrings of the normalized name.
     */
    private const array EDITION_PHRASES = [
        'game of the year', 'season pass', 'digital deluxe',
    ];

    /**
     * @param array<int, array<string, mixed>> $hits
     * @param int|null $appid our game's Steam appid (the strong key); null when unknown
     * @return array{hit: array<string, mixed>, confidence: string}|null
     */
    public function match(string $title, ?int $appid, array $hits): ?array
    {
        $needle = $this->normalize($title);
        if ($needle === '') {
            return null;
        }
        $titleWords = explode(' ', $needle);

        $candidates = [];
        foreach ($hits as $hit) {
            if (!$this->isSteamKey($hit) || !$this->isBuyable($hit)) {
                continue;
            }

            $hitAppid = $this->steamAppid($hit);
            $nameNorm = $this->normalize((string)($hit['name'] ?? ''));

            if ($appid !== null && $hitAppid !== null) {
                // We both know the appid: it must agree (weeds out sequels/remakes/
                // DLC), and our title must appear in the product name (guards
                // against a mislabeled appid pointing at an unrelated product).
                if ($hitAppid !== $appid || !$this->containsWords($nameNorm, $titleWords)) {
                    continue;
                }
            } elseif (!$this->bareTitleEquals($hit, $needle)) {
                // No appid to lean on — fall back to the strict exact-title rule
                // the other stores use, so "Hades" never matches "Hades II".
                continue;
            }

            // Either path: never let an edition/bundle/DLC that shares the appid
            // (or merely contains the title) stand in for the base game.
            if ($this->addsEditionMarker($nameNorm, $needle)) {
                continue;
            }

            $candidates[] = $hit;
        }

        if (!$candidates) {
            return null;
        }

        // Prefer region-free; within that pool prefer an appid-confirmed hit;
        // then the cheapest. Mirrors the other stores' "global = confident" rule.
        $regionFree = array_filter($candidates, fn(array $h): bool => $this->isRegionFree($h));
        $pool = $regionFree ?: $candidates;

        $confirmed = $appid !== null
            ? array_filter($pool, fn(array $h): bool => $this->steamAppid($h) === $appid)
            : [];
        $pick = $this->cheapest($confirmed ?: $pool);

        return [
            'hit'        => $pick,
            'confidence' => $this->isRegionFree($pick) ? self::CONFIDENCE_HIGH : self::CONFIDENCE_LOW,
        ];
    }

    /** A hit is buyable when it has stock and a positive price. */
    public function isBuyable(array $hit): bool
    {
        return (int)($hit['qty'] ?? 0) > 0 && (float)($hit['price'] ?? 0) > 0;
    }

    /**
     * Steam keys/gifts only — not GOG/Epic/Xbox/PSN, and not shared-"account"
     * SKUs (a different, much cheaper product class that would misrepresent the
     * real key price).
     */
    private function isSteamKey(array $hit): bool
    {
        if (strcasecmp(trim((string)($hit['platform'] ?? '')), 'Steam') !== 0) {
            return false;
        }

        return !preg_match('/\baccount\b/i', (string)($hit['name'] ?? ''));
    }

    /** Our game's Steam appid as declared by the hit, or null when absent/garbage. */
    private function steamAppid(array $hit): ?int
    {
        $value = $hit['steam'] ?? null;
        if ($value === null || !is_numeric($value) || (int)$value <= 0) {
            return null;
        }

        return (int)$value;
    }

    /**
     * A region-free key (Kinguin labels it "REGION FREE"). Anything else — Europe,
     * North America, RoW, a country list, "OTHER" — is region-locked.
     */
    private function isRegionFree(array $hit): bool
    {
        $region = strtolower(trim((string)($hit['regionalLimitations'] ?? '')));

        return $region === 'region free' || $region === 'region-free' || $region === 'regionfree';
    }

    /**
     * @param array<int, array<string, mixed>> $hits
     * @return array<string, mixed>
     */
    private function cheapest(array $hits): array
    {
        usort($hits, fn(array $a, array $b): int => (float)($a['price'] ?? 0) <=> (float)($b['price'] ?? 0));

        return $hits[0];
    }

    /**
     * Whether $words appears as a contiguous run inside the normalized name's word
     * list — token-wise so "4" doesn't match inside "45". Used on the appid path,
     * where the appid already pins the game and this only rules out a mislabeled
     * appid on a wholly unrelated product.
     *
     * @param array<int, string> $words
     */
    private function containsWords(string $nameNorm, array $words): bool
    {
        if ($words === [] || $nameNorm === '') {
            return false;
        }

        $haystack = explode(' ', $nameNorm);
        $need = count($words);
        $last = count($haystack) - $need;
        for ($i = 0; $i <= $last; $i++) {
            if (array_slice($haystack, $i, $need) === $words) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strict exact-title fallback for hits with no appid: strip the trailing
     * platform / delivery / region tokens Kinguin appends and compare the bare
     * title for equality, like the IG/Gamivo/GameSeal matchers do.
     */
    private function bareTitleEquals(array $hit, string $needle): bool
    {
        return $this->normalize($this->stripTrailingTokens((string)($hit['name'] ?? ''))) === $needle;
    }

    /**
     * Removes the trailing region / platform / delivery cruft Kinguin tacks onto
     * a product name (e.g. "… EU PC Steam CD Key", "… RoW Steam Altergift"),
     * leaving the bare title. Conservative: it only strips a contiguous run of
     * known tail tokens, so words that are part of the real title survive.
     */
    private function stripTrailingTokens(string $name): string
    {
        $tail = '(?:steam|epic|origin|uplay|ubisoft|gog|pc|cd|key|gift|altergift|account|'
            . 'eu|na|uk|us|row|global|europe|other|vpn|required|edition|languages|only|v\d+|'
            . 'region|free|rest|of|the|world|north|america|latin)';
        $stripped = preg_replace('/(?:\s+' . $tail . ')+\s*$/iu', '', trim($name)) ?? $name;

        return trim($stripped);
    }

    /**
     * True when the product name carries an edition/bundle/DLC marker that our own
     * title lacks — i.e. it's a richer SKU than the base game we're pricing.
     */
    private function addsEditionMarker(string $nameNorm, string $titleNorm): bool
    {
        foreach (self::EDITION_PHRASES as $phrase) {
            if (str_contains($nameNorm, $phrase) && !str_contains($titleNorm, $phrase)) {
                return true;
            }
        }

        $nameWords  = explode(' ', $nameNorm);
        $titleWords = explode(' ', $titleNorm);
        foreach (self::EDITION_MARKERS as $marker) {
            if (in_array($marker, $nameWords, true) && !in_array($marker, $titleWords, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lowercase, strip trademark marks and punctuation, collapse whitespace.
     * Intentionally does NOT strip edition words: {@see addsEditionMarker()} needs
     * them intact to tell a Deluxe/bundle apart from the base game.
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
