<?php

namespace console\controllers;

use common\models\Category;
use common\models\Developer;
use common\models\Game;
use common\models\Genre;
use common\models\Publisher;
use common\models\Tag;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use RuntimeException;
use XMLWriter;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Query;

/**
 * Generates a gzipped XML sitemap set (index + chunked sub-sitemaps) for the
 * single-language frontend. Run via cron after the catalog sync.
 *
 *   yii sitemap/generate
 *   yii sitemap/generate --baseUrl=https://gamentator.com
 */
class SitemapController extends Controller
{
    private const SITEMAP_DIRECTORY_ALIAS = '@frontend/web/sitemap';

    /**
     * Google rejects sitemaps over 50,000 URLs or 50 MB uncompressed.
     * One <url> per logical page here, so 45k keeps a safe margin.
     */
    private const MAX_URLS_PER_CHUNK = 45000;

    public ?string $baseUrl    = null;
    public string  $outputPath = '@frontend/web/sitemap.xml';

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'baseUrl',
            'outputPath',
        ]);
    }

    public function optionAliases(): array
    {
        return [
            'u' => 'baseUrl',
            'o' => 'outputPath',
        ];
    }

    public function actionGenerate(): int
    {
        $configuredBaseUrl = \Yii::$app->params['frontend.baseUrl'] ?? null;
        $baseUrl = $this->normalizeBaseUrl($this->baseUrl ?? (is_string($configuredBaseUrl) ? $configuredBaseUrl : null));
        if ($baseUrl === null) {
            $this->stderr("Missing base URL. Set params['frontend.baseUrl'] or pass --baseUrl=https://gamentator.com\n");

            return ExitCode::USAGE;
        }

        $this->cleanupOldSitemaps();

        $indexEntries = [];
        $totalUrls = 0;

        $totalUrls += $this->writeChunkedSitemap('sitemap-static', $this->iterateStaticEntries($baseUrl), $indexEntries, $baseUrl);
        $totalUrls += $this->writeChunkedSitemap('sitemap-games', $this->iterateGameEntries($baseUrl), $indexEntries, $baseUrl);
        $totalUrls += $this->writeChunkedSitemap('sitemap-genres', $this->iterateGenreEntries($baseUrl), $indexEntries, $baseUrl);
        $totalUrls += $this->writeChunkedSitemap('sitemap-tags', $this->iterateTagEntries($baseUrl), $indexEntries, $baseUrl);
        $totalUrls += $this->writeChunkedSitemap('sitemap-categories', $this->iterateCategoryEntries($baseUrl), $indexEntries, $baseUrl);
        $totalUrls += $this->writeChunkedSitemap('sitemap-companies', $this->iterateCompanyEntries($baseUrl), $indexEntries, $baseUrl);

        $this->writeIndexSitemap($indexEntries);

        $this->stdout('Generated sitemap index with ' . count($indexEntries) . " files and {$totalUrls} URLs in {$this->outputPath}\n");

        return ExitCode::OK;
    }

    // ─── Entry source generators ────────────────────────────────────────────────

    /**
     * @return Generator<int, array{loc:string,lastmod:?string,changefreq:string,priority:string}>
     */
    private function iterateStaticEntries(string $baseUrl): Generator
    {
        $static = [
            ['/',                          'daily',   '1.0'],
            ['/games',                     'daily',   '0.9'],
            ['/games/bestsellers',         'daily',   '0.7'],
            ['/games/new-and-noteworthy',  'daily',   '0.7'],
            ['/games/upcoming',            'daily',   '0.7'],
            ['/genres',                    'weekly',  '0.7'],
            ['/tags',                      'weekly',  '0.6'],
            ['/developers',                'weekly',  '0.6'],
            ['/publishers',                'weekly',  '0.6'],
            ['/how-it-works',              'monthly', '0.4'],
        ];

        foreach ($static as [$path, $changefreq, $priority]) {
            yield [
                'loc'        => $this->buildAbsoluteUrl($baseUrl, $path),
                'lastmod'    => null,
                'changefreq' => $changefreq,
                'priority'   => $priority,
            ];
        }
    }

    private function iterateGameEntries(string $baseUrl): Generator
    {
        $query = Game::find()
            ->select(['steam_appid', 'slug', 'updated_at', 'synchronized_at', 'created_at'])
            ->where(['status' => Game::STATUS_ACTIVE, 'type' => Game::TYPE_GAME])
            ->andWhere(['not', ['slug' => null]])
            ->andWhere(['<>', 'slug', ''])
            ->andWhere(['not', ['steam_appid' => null]])
            ->asArray();

        foreach ($query->batch(1000) as $rows) {
            foreach ($rows as $row) {
                yield [
                    'loc'        => $this->buildAbsoluteUrl($baseUrl, '/game/' . (int)$row['steam_appid'] . '/' . rawurlencode((string)$row['slug'])),
                    'lastmod'    => $this->resolveLastModified($row['updated_at'] ?: ($row['synchronized_at'] ?? null), $row['created_at'] ?? null),
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];
            }
        }
    }

    private function iterateGenreEntries(string $baseUrl): Generator
    {
        $rows = Genre::find()
            ->select(['slug', 'updated_at', 'created_at'])
            ->where(['>', 'games_count', 0])
            ->andWhere(['not', ['slug' => null]])
            ->andWhere(['<>', 'slug', ''])
            ->asArray()
            ->all();

        foreach ($rows as $row) {
            yield [
                'loc'        => $this->buildAbsoluteUrl($baseUrl, '/games/' . rawurlencode((string)$row['slug'])),
                'lastmod'    => $this->resolveLastModified($row['updated_at'] ?? null, $row['created_at'] ?? null),
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }
    }

    private function iterateTagEntries(string $baseUrl): Generator
    {
        $rows = Tag::find()
            ->select(['slug', 'updated_at', 'created_at'])
            ->where(['>', 'games_count', 0])
            ->andWhere(['not', ['slug' => null]])
            ->andWhere(['<>', 'slug', ''])
            ->asArray()
            ->all();

        foreach ($rows as $row) {
            yield [
                'loc'        => $this->buildAbsoluteUrl($baseUrl, '/game/tag/' . rawurlencode((string)$row['slug'])),
                'lastmod'    => $this->resolveLastModified($row['updated_at'] ?? null, $row['created_at'] ?? null),
                'changefreq' => 'weekly',
                'priority'   => '0.5',
            ];
        }
    }

    private function iterateCategoryEntries(string $baseUrl): Generator
    {
        $rows = Category::find()
            ->select(['slug', 'updated_at', 'created_at'])
            ->where(['>', 'games_count', 0])
            ->andWhere(['not', ['slug' => null]])
            ->andWhere(['<>', 'slug', ''])
            ->asArray()
            ->all();

        foreach ($rows as $row) {
            yield [
                'loc'        => $this->buildAbsoluteUrl($baseUrl, '/games/' . rawurlencode((string)$row['slug'])),
                'lastmod'    => $this->resolveLastModified($row['updated_at'] ?? null, $row['created_at'] ?? null),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }
    }

    /**
     * Emit only canonical company URLs, mirroring the 301 logic in the
     * developer/publisher/company controllers:
     *   - name in BOTH developer and publisher tables → /company/<slug>
     *   - developer-only name                         → /developer/<slug>
     *   - publisher-only name                         → /publisher/<slug>
     * Only entities that have at least one active game are included.
     */
    private function iterateCompanyEntries(string $baseUrl): Generator
    {
        $allDeveloperNames = array_flip(Developer::find()
            ->select('name')
            ->andWhere(['not', ['name' => null]])
            ->column());
        $allPublisherNames = array_flip(Publisher::find()
            ->select('name')
            ->andWhere(['not', ['name' => null]])
            ->column());

        $companySlugs = [];

        foreach ($this->companyRowsWithGames('developer') as $row) {
            $name = (string)$row['name'];
            if (isset($allPublisherNames[$name])) {
                $companySlugs[(string)$row['slug']] = true;
                continue;
            }

            yield $this->companyEntry($baseUrl, '/developer/', $row, '0.5');
        }

        foreach ($this->companyRowsWithGames('publisher') as $row) {
            $name = (string)$row['name'];
            if (isset($allDeveloperNames[$name])) {
                $companySlugs[(string)$row['slug']] = true;
                continue;
            }

            yield $this->companyEntry($baseUrl, '/publisher/', $row, '0.5');
        }

        foreach (array_keys($companySlugs) as $slug) {
            if ($slug === '') {
                continue;
            }

            yield [
                'loc'        => $this->buildAbsoluteUrl($baseUrl, '/company/' . rawurlencode($slug)),
                'lastmod'    => null,
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }
    }

    /**
     * @param 'developer'|'publisher' $kind
     * @return array<int, array{name:string,slug:string,updated_at:?string,created_at:?string}>
     */
    private function companyRowsWithGames(string $kind): array
    {
        $pivotTable = '{{%game_' . $kind . '}}';
        $foreignKey = $kind . '_id';
        $modelClass = $kind === 'developer' ? Developer::class : Publisher::class;

        return $modelClass::find()
            ->alias('c')
            ->select(['c.name', 'c.slug', 'c.updated_at', 'c.created_at'])
            ->andWhere(['not', ['c.slug' => null]])
            ->andWhere(['<>', 'c.slug', ''])
            ->andWhere([
                'exists',
                (new Query())
                    ->from($pivotTable . ' pv')
                    ->innerJoin('{{%game}} g', 'g.id = pv.game_id')
                    ->where('pv.' . $foreignKey . ' = c.id')
                    ->andWhere(['g.status' => Game::STATUS_ACTIVE, 'g.type' => Game::TYPE_GAME]),
            ])
            ->asArray()
            ->all();
    }

    /**
     * @param array{name:string,slug:string,updated_at:?string,created_at:?string} $row
     * @return array{loc:string,lastmod:?string,changefreq:string,priority:string}
     */
    private function companyEntry(string $baseUrl, string $pathPrefix, array $row, string $priority): array
    {
        return [
            'loc'        => $this->buildAbsoluteUrl($baseUrl, $pathPrefix . rawurlencode((string)$row['slug'])),
            'lastmod'    => $this->resolveLastModified($row['updated_at'] ?? null, $row['created_at'] ?? null),
            'changefreq' => 'weekly',
            'priority'   => $priority,
        ];
    }

    // ─── Streaming writer ──────────────────────────────────────────────────────

    /**
     * Stream each yielded entry into one or more gzipped sitemap files,
     * rotating to a new chunk before the URL count crosses Google's limit.
     *
     * @param iterable<int, array{loc:string,lastmod:?string,changefreq:string,priority:string}> $entries
     */
    private function writeChunkedSitemap(string $baseName, iterable $entries, array &$indexEntries, string $baseUrl): int
    {
        $totalUrls = 0;
        $urlsInChunk = 0;
        $chunkIndex = 1;
        $writer = null;
        $currentFilePath = null;

        foreach ($entries as $entry) {
            if ($writer === null) {
                $currentFilePath = $this->resolveChunkPath($baseName, $chunkIndex);
                $writer = $this->openSitemapWriter($currentFilePath);
            }

            $written = $this->writeUrlElement($writer, $entry['loc'], $entry['lastmod'] ?? null, $entry['changefreq'], $entry['priority']);
            $urlsInChunk += $written;
            $totalUrls += $written;

            if ($urlsInChunk >= self::MAX_URLS_PER_CHUNK) {
                $this->closeSitemapWriter($writer, $currentFilePath, $indexEntries, $baseUrl);
                $writer = null;
                $urlsInChunk = 0;
                $chunkIndex++;
            }
        }

        if ($writer !== null) {
            $this->closeSitemapWriter($writer, $currentFilePath, $indexEntries, $baseUrl);
        }

        return $totalUrls;
    }

    private function resolveChunkPath(string $baseName, int $chunkIndex): string
    {
        // Always use numbered chunks for predictability — even single-chunk
        // sub-sitemaps get `-1` to keep the index format consistent and avoid
        // collisions if a category later grows past one chunk.
        $fileName = $baseName . '-' . $chunkIndex . '.xml.gz';

        return $this->buildOutputFilePath($fileName);
    }

    private function openSitemapWriter(string $filePath): XMLWriter
    {
        $directory = dirname($filePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create sitemap directory: {$directory}");
        }

        $writer = new XMLWriter();
        // The compress.zlib:// stream wrapper makes XMLWriter pipe directly
        // into a gzip-compressed file — no in-memory buffer needed.
        if (!$writer->openUri('compress.zlib://' . $filePath)) {
            throw new RuntimeException("Failed to open sitemap writer for {$filePath}");
        }

        $writer->setIndent(true);
        $writer->setIndentString(' ');
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return $writer;
    }

    private function closeSitemapWriter(XMLWriter $writer, string $filePath, array &$indexEntries, string $baseUrl): void
    {
        $writer->endElement(); // urlset
        $writer->endDocument();
        $writer->flush();

        $indexEntries[] = [
            'loc'     => $this->buildSitemapFileUrl($filePath, $baseUrl),
            'lastmod' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];
    }

    private function writeUrlElement(XMLWriter $writer, string $loc, ?string $lastmod, string $changefreq, string $priority): int
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $loc);
        if ($lastmod !== null) {
            $writer->writeElement('lastmod', $lastmod);
        }
        $writer->writeElement('changefreq', $changefreq);
        $writer->writeElement('priority', $priority);
        $writer->endElement(); // url

        return 1;
    }

    private function writeIndexSitemap(array $entries): void
    {
        $path = \Yii::getAlias($this->outputPath);
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create sitemap directory: {$directory}");
        }

        $writer = new XMLWriter();
        if (!$writer->openUri($path)) {
            throw new RuntimeException("Failed to open sitemap index writer for {$path}");
        }

        $writer->setIndent(true);
        $writer->setIndentString(' ');
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($entries as $entry) {
            $writer->startElement('sitemap');
            $writer->writeElement('loc', $entry['loc']);
            $writer->writeElement('lastmod', $entry['lastmod']);
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    /**
     * Remove old chunked sitemap files before generating fresh ones so that
     * shrinking categories don't leave stale chunks pointing at gone content.
     */
    private function cleanupOldSitemaps(): void
    {
        $directory = \Yii::getAlias(self::SITEMAP_DIRECTORY_ALIAS);
        if (!is_dir($directory)) {
            return;
        }

        $patterns = [
            $directory . DIRECTORY_SEPARATOR . 'sitemap-*.xml',
            $directory . DIRECTORY_SEPARATOR . 'sitemap-*.xml.gz',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                @unlink($file);
            }
        }
    }

    // ─── URL / path helpers ────────────────────────────────────────────────────

    private function buildOutputFilePath(string $fileName): string
    {
        $sitemapDirectory = \Yii::getAlias(self::SITEMAP_DIRECTORY_ALIAS);

        return rtrim($sitemapDirectory, '/\\') . DIRECTORY_SEPARATOR . $fileName;
    }

    private function buildSitemapFileUrl(string $filePath, string $baseUrl): string
    {
        $frontendWebPath = \Yii::getAlias('@frontend/web');
        $normalizedWebPath = str_replace('\\', '/', rtrim($frontendWebPath, '/\\'));
        $normalizedFilePath = str_replace('\\', '/', $filePath);

        if (!str_starts_with($normalizedFilePath, $normalizedWebPath)) {
            throw new RuntimeException("Sitemap file must be inside @frontend/web: {$filePath}");
        }

        $relativePath = ltrim(substr($normalizedFilePath, strlen($normalizedWebPath)), '/');

        return $this->buildAbsoluteUrl($baseUrl, '/' . $relativePath);
    }

    private function buildAbsoluteUrl(string $baseUrl, string $path): string
    {
        $normalizedPath = '/' . ltrim($path, '/');

        return rtrim($baseUrl, '/') . $normalizedPath;
    }

    private function resolveLastModified(mixed $updatedAt, mixed $createdAt): ?string
    {
        $updated = $this->toSitemapDate($updatedAt);
        if ($updated !== null) {
            return $updated;
        }

        return $this->toSitemapDate($createdAt);
    }

    private function toSitemapDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format(DateTimeInterface::ATOM);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeBaseUrl(?string $baseUrl): ?string
    {
        if ($baseUrl === null) {
            return null;
        }

        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $baseUrl) !== 1) {
            return null;
        }

        return rtrim($baseUrl, '/');
    }
}
