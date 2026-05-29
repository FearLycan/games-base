<?php

namespace common\components\InstantGaming;

/**
 * Matches one of our games against Instant Gaming search hits.
 *
 * IG hits carry no Steam appid, so matching is by title only. To stay on the
 * safe side (the user wants only confident matches auto-published), we require
 * the *normalized* title to be exactly equal — never a substring. That way
 * "Elden Ring" won't silently match "Elden Ring Nightreign" or an edition; such
 * cases simply produce no match and are left for a future manual pass.
 *
 * Region matters: a Worldwide key is a clean match (high confidence), while a
 * region-locked one (Europe, Latin America, ...) is flagged for review.
 */
class IgMatcher
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
            $names = [$hit['name'] ?? '', $hit['small_name'] ?? ''];
            foreach ($names as $name) {
                if ($this->normalize((string)$name) === $needle) {
                    $exact[] = $hit;
                    break;
                }
            }
        }

        if (!$exact) {
            return null;
        }

        foreach ($exact as $hit) {
            if (strtolower((string)($hit['region'] ?? '')) === 'worldwide') {
                return ['hit' => $hit, 'confidence' => self::CONFIDENCE_HIGH];
            }
        }

        return ['hit' => $exact[0], 'confidence' => self::CONFIDENCE_LOW];
    }

    /**
     * Lowercase, strip trademark marks and punctuation, collapse whitespace.
     * Intentionally does NOT strip edition/region words: doing so would let
     * different products collapse onto the same key and create false matches.
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
