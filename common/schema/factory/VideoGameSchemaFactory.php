<?php

namespace common\schema\factory;

use common\models\Game;
use DateTimeImmutable;

final class VideoGameSchemaFactory
{
    public static function fromGame(Game $game, string $productUrl, ?array $offer = null): array
    {
        $name = trim((string)$game->title);
        if ($name === '') {
            $name = 'Game';
        }

        $schema = [
            '@type'               => 'VideoGame',
            '@id'                 => '#videogame',
            'name'                => $name,
            'url'                 => $productUrl,
            'applicationCategory' => 'Game',
        ];

        $description = self::resolveDescription($game);
        if ($description !== '') {
            $schema['description'] = $description;
        }

        $images = self::collectImages($game);
        if ($images !== []) {
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }

        $genres = self::names($game->genres);
        if ($genres !== []) {
            $schema['genre'] = $genres;
        }

        $platforms = [];
        foreach ($game->getAvailablePlatforms() as $platform) {
            $platformName = ucfirst(trim((string)$platform->name));
            if ($platformName !== '') {
                $platforms[] = $platformName;
            }
        }
        if ($platforms !== []) {
            $schema['gamePlatform']    = $platforms;
            $schema['operatingSystem'] = $platforms;
        }

        $datePublished = self::resolveDate($game->release_date);
        if ($datePublished !== null) {
            $schema['datePublished'] = $datePublished;
        }

        $publishers = self::organizations($game->publishers);
        if ($publishers !== []) {
            $schema['publisher'] = count($publishers) === 1 ? $publishers[0] : $publishers;
        }

        $developers = self::organizations($game->developers);
        if ($developers !== []) {
            $schema['author'] = count($developers) === 1 ? $developers[0] : $developers;
        }

        $aggregateRating = self::buildAggregateRating($game);
        if ($aggregateRating !== null) {
            $schema['aggregateRating'] = $aggregateRating;
        }

        if ($offer !== null) {
            $schema['offers'] = $offer;
        }

        return $schema;
    }

    private static function resolveDescription(Game $game): string
    {
        $description = trim(strip_tags((string)$game->short_description));
        if ($description === '') {
            return '';
        }

        if (mb_strlen($description) <= 300) {
            return $description;
        }

        return rtrim(mb_substr($description, 0, 300)) . '...';
    }

    /**
     * @return string[]
     */
    private static function collectImages(Game $game): array
    {
        $images = [];

        $header = trim((string)$game->getHeader());
        if ($header !== '') {
            $images[] = $header;
        }

        foreach ($game->getScreenshots() as $screenshot) {
            $url = trim((string)($screenshot->url ?? ''));
            if ($url === '' || in_array($url, $images, true)) {
                continue;
            }

            $images[] = $url;
            if (count($images) >= 4) {
                break;
            }
        }

        return $images;
    }

    /**
     * @param iterable<object> $models
     * @return string[]
     */
    private static function names(iterable $models): array
    {
        $names = [];
        foreach ($models as $model) {
            $name = trim((string)($model->name ?? ''));
            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param iterable<object> $models
     * @return array<int, array{@type:string,name:string}>
     */
    private static function organizations(iterable $models): array
    {
        $organizations = [];
        foreach (self::names($models) as $name) {
            $organizations[] = [
                '@type' => 'Organization',
                'name'  => $name,
            ];
        }

        return $organizations;
    }

    private static function resolveDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function buildAggregateRating(Game $game): ?array
    {
        $review = $game->review;
        if ($review === null) {
            return null;
        }

        $reviewCount = (int)$review->total_reviews;
        if ($reviewCount < 1) {
            return null;
        }

        // Steam exposes a positive-percentage; map it onto a 1–5 scale.
        $ratingValue = round(($review->getPercentsOfPositive() / 100) * 5, 1);
        if ($ratingValue <= 0) {
            return null;
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format($ratingValue, 1, '.', ''),
            'reviewCount' => $reviewCount,
            'bestRating'  => '5',
            'worstRating' => '1',
        ];
    }
}
