<?php

namespace Flute\Core\Update\Services\Concerns;

use Flute\Core\App;
use Flute\Core\Services\FluteApiClient;
use Flute\Core\Support\FileUploader;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;

trait HandlesUpdateDownload
{
    /**
     * @return string|null Path to downloaded file or null on failure
     */
    public function downloadUpdate(string $type, ?string $identifier = null, ?string $version = null): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            $updates = $this->getFreshUpdatesForDownload();

            $downloadUrl = null;
            $latestVersion = null;

            if ($type === 'cms') {
                $downloadUrl = $updates['cms']['download_url'] ?? null;
                $latestVersion = $updates['cms']['version'] ?? null;
            } elseif (!empty($identifier)) {
                $downloadUrl = $updates[$type . 's'][$identifier]['download_url'] ?? null;
                $latestVersion = $updates[$type . 's'][$identifier]['version'] ?? null;
            }

            if (empty($downloadUrl)) {
                logs()->error("Download URL not found for {$type} " . ( $identifier ?? '' ));

                return null;
            }

            $tempDir = storage_path('app/temp/updates');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0o755, true);
            }

            $fileName =
                $tempDir
                . '/'
                . $this->sanitizeDownloadPart($identifier ?? 'cms')
                . '-'
                . $this->sanitizeDownloadPart($version ?? $latestVersion ?? 'latest')
                . '.zip';

            $api = new FluteApiClient(timeout: 120, connectTimeout: 10);

            $isAbsoluteUrl = (bool) preg_match('/^https?:\/\//', $downloadUrl);
            $parsedDownloadUrl = parse_url($downloadUrl);
            $queryParams = [];
            if (isset($parsedDownloadUrl['query'])) {
                parse_str($parsedDownloadUrl['query'], $queryParams);
            }

            $hasJwtToken = !empty($queryParams['token']);

            if ($isAbsoluteUrl) {
                $fullUrl = $downloadUrl;
            } else {
                $path = $parsedDownloadUrl['path'] ?? $downloadUrl;
                $fullUrl = rtrim($api->getActiveBaseUrl(), '/') . '/' . ltrim($path, '/');
            }

            $this->assertDownloadUrlAllowed($fullUrl);

            $guzzleOptions = [
                'headers' => [
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                ],
                'sink' => $fileName,
                'http_errors' => false,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => false,
                    'track_redirects' => true,
                    'on_redirect' => function ($request, $response, $uri): void {
                        $this->assertDownloadUrlAllowed((string) $uri);
                    },
                ],
            ];

            if (!$isAbsoluteUrl) {
                $requestQuery = [];
                if ($hasJwtToken) {
                    $requestQuery['token'] = $queryParams['token'];
                } else {
                    $requestQuery['accessKey'] = config('app.flute_key');
                    $requestQuery['versionId'] = $version ?? $latestVersion;
                }
                $guzzleOptions['query'] = $requestQuery;
            }

            $response = $api->getClient()->request('GET', $fullUrl, $guzzleOptions);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $body = is_file($fileName) ? (string) @file_get_contents($fileName, false, null, 0, 512) : '';
                logs()->error("Update download returned HTTP {$statusCode} for {$type}/{$identifier}: {$body}", [
                    'url' => $fullUrl,
                ]);
                @unlink($fileName);

                return null;
            }

            if (!$this->isSafeUpdateZipArchive($fileName, $type)) {
                $contentType = $response->getHeaderLine('Content-Type');
                logs()->error("Downloaded file is not a valid ZIP archive: {$fileName} (Content-Type: {$contentType})");
                @unlink($fileName);

                return null;
            }

            return $fileName;
        } catch (GuzzleException $e) {
            logs()->error('Failed to download update: ' . $e->getMessage());

            \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'update.download']);

            if (is_debug()) {
                throw $e;
            }
        } catch (Throwable $e) {
            logs()->error('Error processing update download: ' . $e->getMessage());

            \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'update.download']);

            if (is_debug()) {
                throw $e;
            }
        }

        return null;
    }

    /**
     * Download a specific CMS version from the engine catalog.
     */
    public function downloadVersionFromCatalog(string $version): ?string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        try {
            $apiKey = config('app.flute_key');

            if (empty($apiKey)) {
                logs()->error('Flute API key is empty, cannot download version');

                return null;
            }

            $api = new FluteApiClient(timeout: 120, connectTimeout: 10);

            $downloadUrl = $this->findCatalogVersionDownloadUrl($version) ?? '/api/engine/download';
            $parsedUrl = parse_url($downloadUrl);
            $urlQuery = [];
            if (isset($parsedUrl['query']) && is_string($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $urlQuery);
            }

            $isAbsoluteUrl = (bool) preg_match('/^https?:\/\//', $downloadUrl);
            $hasJwtToken = !empty($urlQuery['token']);

            if ($isAbsoluteUrl) {
                $fullUrl = $downloadUrl;
            } else {
                $path = $parsedUrl['path'] ?? '/api/engine/download';
                $fullUrl = rtrim($api->getActiveBaseUrl(), '/') . '/' . ltrim($path, '/');
            }

            $this->assertDownloadUrlAllowed($fullUrl);

            $tempDir = storage_path('app/temp/updates');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0o755, true);
            }

            $safeVersion = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $version);
            $fileName = $tempDir . '/cms-' . $safeVersion . '.zip';

            $guzzleOptions = [
                'headers' => [
                    'User-Agent' => 'Flute-CMS/' . App::VERSION,
                ],
                'sink' => $fileName,
                'http_errors' => false,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => false,
                    'track_redirects' => true,
                    'on_redirect' => function ($request, $response, $uri): void {
                        $this->assertDownloadUrlAllowed((string) $uri);
                    },
                ],
            ];

            if (!$isAbsoluteUrl) {
                $requestQuery = [];
                if ($hasJwtToken) {
                    $requestQuery['token'] = $urlQuery['token'];
                } else {
                    $requestQuery = array_merge($urlQuery, [
                        'version' => $version,
                        'accessKey' => $apiKey,
                    ]);
                }
                $guzzleOptions['query'] = $requestQuery;
            }

            $response = $api->getClient()->request('GET', $fullUrl, $guzzleOptions);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $body = is_file($fileName) ? (string) @file_get_contents($fileName, false, null, 0, 512) : '';
                logs()->error("Catalog download returned HTTP {$statusCode} for version {$version}: {$body}", [
                    'url' => $fullUrl,
                ]);
                @unlink($fileName);

                return null;
            }

            if (!$this->isSafeUpdateZipArchive($fileName, 'cms')) {
                $contentType = $response->getHeaderLine('Content-Type');
                logs()->error("Downloaded catalog file is not a valid ZIP: {$fileName} (Content-Type: {$contentType})");
                @unlink($fileName);

                return null;
            }

            return $fileName;
        } catch (\Throwable $e) {
            logs()->error('Failed to download version from catalog: ' . $e->getMessage());

            \Flute\Core\Services\CrashReportService::capture($e, ['source' => 'update.catalog.download']);

            if (is_debug()) {
                throw $e;
            }
        }

        return null;
    }

    private function isValidZipArchive(string $fileName): bool
    {
        if (!is_file($fileName) || !is_readable($fileName)) {
            return false;
        }

        $size = @filesize($fileName);
        if (!is_int($size) || $size < 22) {
            return false;
        }

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            $status = $zip->open($fileName, \ZipArchive::CHECKCONS);
            if ($status === true) {
                $zip->close();

                return true;
            }
        }

        $handle = @fopen($fileName, 'rb');
        if ($handle === false) {
            return false;
        }

        $signature = (string) fread($handle, 4);
        fclose($handle);

        return in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true);
    }

    private function isSafeUpdateZipArchive(string $fileName, string $type): bool
    {
        if (!$this->isValidZipArchive($fileName)) {
            return false;
        }

        $maxTotalSize = match ($type) {
            'cms' => 350 * 1024 * 1024,
            'module', 'theme' => 150 * 1024 * 1024,
            default => 250 * 1024 * 1024,
        };

        try {
            app(FileUploader::class)->inspectZipArchive($fileName, [
                'max_entries' => 12000,
                'max_total_size' => $maxTotalSize,
                'min_compression_ratio' => 0.001,
            ]);

            return true;
        } catch (Throwable $e) {
            logs()->error('Downloaded update ZIP failed safety inspection: ' . $e->getMessage(), [
                'file' => $fileName,
                'type' => $type,
            ]);

            return false;
        }
    }

    private function sanitizeDownloadPart(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $value) ?? '';

        return $value !== '' ? $value : 'package';
    }

    private function assertDownloadUrlAllowed(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \RuntimeException('Update download URL is not allowed.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $allowHttp = function_exists('is_debug') && is_debug() || (bool) config('app.development_mode', false);

        if ($scheme !== 'https' && !( $scheme === 'http' && $allowHttp )) {
            throw new \RuntimeException('Update download URL is not allowed.');
        }

        $trustedHosts = $this->getTrustedDownloadHosts();
        if (!isset($trustedHosts[$host])) {
            throw new \RuntimeException('Update download URL is not allowed.');
        }
    }

    /**
     * @return array<string,bool>
     */
    private function getTrustedDownloadHosts(): array
    {
        $hosts = [];
        $sources = [
            config('app.flute_market_url', 'https://flute-cms.com'),
            ...( is_array(config('app.flute_market_mirrors', [])) ? config('app.flute_market_mirrors', []) : [] ),
            ...(
                is_array(config('app.flute_market_download_hosts', []))
                    ? config('app.flute_market_download_hosts', [])
                    : []
            ),
        ];

        foreach ($sources as $source) {
            if (!is_string($source) || $source === '') {
                continue;
            }

            $host = $source;
            if (str_contains($source, '://')) {
                $parsed = parse_url($source);
                $host = is_array($parsed) && !empty($parsed['host']) ? (string) $parsed['host'] : '';
            }

            if ($host !== '') {
                $hosts[strtolower($host)] = true;
            }
        }

        foreach (self::TRUSTED_FLUTE_DOWNLOAD_HOSTS as $host) {
            $hosts[strtolower($host)] = true;
        }

        return $hosts;
    }
}
