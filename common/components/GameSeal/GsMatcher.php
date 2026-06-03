<?php

namespace common\components\GameSeal;

/**
 * Matches one of our games against GameSeal search hits.
 *
 * GameSeal hits carry no Steam appid, so matching is by title only. Like Instant
 * Gaming and Gamivo, we require the *normalized* title to be exactly equal —
 * never a substring — so a base game never silently matches an edition or sequel.
 *
 * The catch: GameSeal bakes the platform, delivery method and region into the
 * product name, always in the same shape:
 *     "<title> (<platform>) <delivery> - <REGION>"
 *   e.g. "007 First Light (PC) Steam Account - GLOBAL"
 * We strip those *structured* trailing tokens (region from the region badge,
 * delivery from the platform badge, then a trailing "(…)" platform group) to
 * recover the bare title before comparing.
 *
 * Region matters: a Global key is a clean match (high confidence), while a
 * region-locked one (Europe, …) is flagged for review. Among equally-good
 * matches we keep the cheapest buyable one.
 */
class GsMatcher
{
    public const string CONFIDENCE_HIGH = 'high';
    public const string CONFIDENCE_LOW  = 'low';

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
            // Steam only, like Instant Gaming / Gamivo — GameSeal also lists
            // GOG / Epic / Ubisoft keys for the same title, which we don't want
            // surfaced as a Steam-game offer.
            if (!$this->isSteam($hit) || !$this->isBuyable($hit)) {
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

        // Cheapest buyable Global → high confidence; otherwise cheapest buyable
        // region-locked SKU → low (needs a review). Mirrors IG's "Global = clean".
        $global = array_filter($exact, fn(array $h): bool => $this->isGlobal($h));
        $pick = $this->cheapest($global ?: $exact);

        return [
            'hit'        => $pick,
            'confidence' => $this->isGlobal($pick) ? self::CONFIDENCE_HIGH : self::CONFIDENCE_LOW,
        ];
    }

    /** A hit is buyable when it carries a positive price (suggest only lists in-stock SKUs). */
    public function isBuyable(array $hit): bool
    {
        return (float)($hit['price'] ?? 0) > 0;
    }

    /** Steam delivery only ("Steam Account" / "Steam Gift"); not GOG/Epic/etc. */
    private function isSteam(array $hit): bool
    {
        return str_starts_with(strtolower(trim((string)($hit['delivery'] ?? ''))), 'steam');
    }

    private function isGlobal(array $hit): bool
    {
        return strtolower(trim((string)($hit['region'] ?? ''))) === 'global';
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
     * Candidate bare titles for a hit: the name and the title attribute, each
     * with the trailing region, delivery and "(platform)" group stripped.
     * Comparing against both is defensive in case one is formatted differently.
     *
     * @param array<string, mixed> $hit
     * @return array<int, string>
     */
    private function candidateTitles(array $hit): array
    {
        $region   = (string)($hit['region'] ?? '');
        $delivery = (string)($hit['delivery'] ?? '');

        $candidates = [];
        foreach ([$hit['title'] ?? '', $hit['name'] ?? ''] as $name) {
            $bare = $this->stripTrailingRegion((string)$name, $region);
            $bare = $this->stripSuffixWord($bare, $delivery);
            $bare = $this->stripTrailingParens($bare);
            if ($bare !== '') {
                $candidates[$bare] = true;
            }
        }

        return array_keys($candidates);
    }

    /**
     * Removes the trailing " - <region>" segment. The region label is taken from
     * the badge (e.g. "Global"), but the name often uppercases it ("GLOBAL"), so
     * the match is case-insensitive. Falls back to stripping any " - WORD(S)"
     * tail when the badge is empty.
     */
    private function stripTrailingRegion(string $value, string $region): string
    {
        $value = trim($value);
        $region = trim($region);

        if ($region !== '') {
            $needle = ' - ' . mb_strtolower($region);
            if (str_ends_with(mb_strtolower($value), $needle)) {
                return trim(mb_substr($value, 0, mb_strlen($value) - mb_strlen($needle)));
            }
        }

        // Fallback: a trailing " - REGIONWORDS" in caps (e.g. "- LATIN AMERICA").
        return trim(preg_replace('/\s+-\s+[A-Z][A-Z \/]*$/u', '', $value) ?? $value);
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

    /** Removes a single trailing "(…)" group, e.g. the "(PC)" platform marker. */
    private function stripTrailingParens(string $value): string
    {
        return trim(preg_replace('/\s*\([^)]*\)\s*$/u', '', trim($value)) ?? $value);
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
