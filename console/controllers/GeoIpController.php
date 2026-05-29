<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\FileHelper;
use yii\httpclient\Client;

/**
 * Keeps the MaxMind GeoLite2 database (used by {@see \common\components\CurrencyResolver})
 * up to date.
 *
 * Downloads the edition tarball straight from MaxMind, verifies its SHA-256,
 * extracts the .mmdb and atomically swaps it into place. Safe to run on a weekly
 * cron — MaxMind refreshes GeoLite2 a couple of times a week.
 *
 * Needs a free MaxMind license key in `params['geoip_license_key']`
 * (set it in params-local.php, not in the committed params.php).
 *
 *   php yii geo-ip/update
 */
class GeoIpController extends Controller
{
    private const string DOWNLOAD_URL = 'https://download.maxmind.com/app/geoip_download';

    /** Override the destination path (defaults to params['geoip_db']). */
    public ?string $dest = null;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['dest']);
    }

    public function actionUpdate(): int
    {
        $license = (string)(Yii::$app->params['geoip_license_key'] ?? '');
        if ($license === '') {
            $this->stderr("Missing params['geoip_license_key'] — add your free MaxMind license key to params-local.php.\n");
            return ExitCode::CONFIG;
        }

        $edition = (string)(Yii::$app->params['geoip_edition'] ?? 'GeoLite2-Country');
        $dest = Yii::getAlias($this->dest ?? (string)(Yii::$app->params['geoip_db'] ?? ''));
        if (!$dest) {
            $this->stderr("No destination path — set params['geoip_db'] or pass --dest.\n");
            return ExitCode::CONFIG;
        }

        $client = new Client();

        // 1) Download the tarball.
        $archive = $this->download($client, $license, $edition, 'tar.gz');
        if ($archive === null) {
            return ExitCode::TEMPFAIL;
        }
        if (substr($archive, 0, 2) !== "\x1f\x8b") {
            // Not gzip — almost always an auth/error page rather than the DB.
            $this->stderr("Download did not return a gzip archive (check the license key / edition).\n");
            return ExitCode::TEMPFAIL;
        }

        // 2) Verify SHA-256 (MaxMind publishes "<hash>  <filename>").
        $checksum = $this->download($client, $license, $edition, 'tar.gz.sha256');
        if ($checksum !== null) {
            $expected = strtolower(trim(strtok($checksum, " \t\n")));
            $actual = hash('sha256', $archive);
            if ($expected !== '' && !hash_equals($expected, $actual)) {
                $this->stderr("Checksum mismatch — refusing to install (expected {$expected}, got {$actual}).\n");
                return ExitCode::TEMPFAIL;
            }
            $this->stdout("Checksum OK.\n");
        } else {
            $this->stderr("Warning: could not fetch checksum — installing without verification.\n");
        }

        // 3) Extract the .mmdb from the tar.gz.
        $tmpArchive = tempnam(sys_get_temp_dir(), 'geoip_') . '.tar.gz';
        file_put_contents($tmpArchive, $archive);

        try {
            $mmdb = $this->extractMmdb($tmpArchive);
        } catch (\Throwable $e) {
            @unlink($tmpArchive);
            $this->stderr("Failed to extract the database: {$e->getMessage()}\n");
            return ExitCode::SOFTWARE;
        }
        @unlink($tmpArchive);

        if ($mmdb === null) {
            $this->stderr("No .mmdb file found inside the archive.\n");
            return ExitCode::SOFTWARE;
        }

        // 4) Install atomically.
        FileHelper::createDirectory(dirname($dest));
        $tmpDest = $dest . '.tmp';
        if (file_put_contents($tmpDest, $mmdb) === false || !@rename($tmpDest, $dest)) {
            @unlink($tmpDest);
            $this->stderr("Could not write the database to {$dest}.\n");
            return ExitCode::IOERR;
        }

        $this->stdout(sprintf("Updated %s (%s, %.1f KB)\n", $edition, $dest, strlen($mmdb) / 1024));
        return ExitCode::OK;
    }

    /**
     * Downloads one MaxMind artifact (the tarball or its checksum), or null on failure.
     */
    private function download(Client $client, string $license, string $edition, string $suffix): ?string
    {
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl(self::DOWNLOAD_URL)
            ->setData([
                'edition_id'  => $edition,
                'license_key' => $license,
                'suffix'      => $suffix,
            ])
            ->send();

        if (!$response->isOk) {
            $this->stderr("Download failed for suffix '{$suffix}' (HTTP {$response->statusCode}).\n");
            return null;
        }

        return $response->content;
    }

    /**
     * Returns the raw bytes of the first *.mmdb entry inside a tar.gz, or null.
     */
    private function extractMmdb(string $tarGzPath): ?string
    {
        $phar = new \PharData($tarGzPath);

        foreach (new \RecursiveIteratorIterator($phar) as $file) {
            /** @var \PharFileInfo $file */
            if (str_ends_with(strtolower($file->getFilename()), '.mmdb')) {
                return file_get_contents($file->getPathname());
            }
        }

        return null;
    }
}
