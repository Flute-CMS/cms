<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;
use Flute\Core\Support\FileUploader;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

trait HandlesModuleDownload
{
    /**
     * @throws Exception
     */
    public function downloadModule(array $module): array
    {
        $this->keepProcessAlive();
        $this->ensureMarketplaceWorkspace();

        if (empty($module['downloadUrl'])) {
            throw new Exception(__('admin-marketplace.messages.download_failed') . ': URL не указан');
        }

        $this->acquireMarketplaceLock();

        try {
            $downloadUrl = $module['downloadUrl'];
            $this->moduleArchivePath = $this->tempDir . '/' . $module['slug'] . '-' . time() . '.zip';

            if (is_file($this->moduleArchivePath)) {
                @unlink($this->moduleArchivePath);
            }

            if (!str_starts_with($downloadUrl, 'http')) {
                $api = new \Flute\Core\Services\FluteApiClient();
                $downloadUrl = rtrim($api->getActiveBaseUrl(), '/') . '/' . ltrim($downloadUrl, '/');
            }

            $requestUrl = $this->buildDownloadRequestUrl($downloadUrl);
            $this->assertDownloadUrlAllowed($requestUrl);

            $response = $this->client->get($requestUrl, [
                'sink' => $this->moduleArchivePath,
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => false,
                    'referer' => false,
                    'track_redirects' => true,
                    'on_redirect' => function (
                        RequestInterface $request,
                        ResponseInterface $response,
                        UriInterface $uri,
                    ): void {
                        $this->assertDownloadUrlAllowed((string) $uri);
                    },
                ],
            ]);

            if ($response->getStatusCode() === 401) {
                $this->cleanupFailedDownloadArtifact();
                throw new Exception('MARKETPLACE_BAD_REQUEST');
            }
            if ($response->getStatusCode() !== 200) {
                $this->cleanupFailedDownloadArtifact();
                throw new Exception(__('admin-marketplace.messages.download_failed'));
            }

            clearstatcache(true, $this->moduleArchivePath);
            $this->assertValidZipFile($this->moduleArchivePath);

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.download_success'),
                'path' => $this->moduleArchivePath,
            ];
        } catch (GuzzleException $e) {
            $this->cleanupFailedDownloadArtifact();
            throw new Exception(__('admin-marketplace.messages.download_failed') . ': ' . $e->getMessage());
        } catch (Throwable $e) {
            $this->cleanupFailedDownloadArtifact();
            throw $e;
        }
    }

    protected function buildDownloadRequestUrl(string $downloadUrl): string
    {
        $key = (string) config('app.flute_key', '');
        $separator = str_contains($downloadUrl, '?') ? '&' : '?';

        return $downloadUrl . $separator . 'accessKey=' . rawurlencode($key);
    }

    /**
     * @return array<string,bool>
     */
    protected function getTrustedMarketplaceDownloadHostMap(): array
    {
        $map = [];
        $primary = rtrim((string) config('app.flute_market_url', 'https://flute-cms.com'), '/');
        $mirrors = config('app.flute_market_mirrors', []);
        $extra = config('app.flute_market_download_hosts', []);

        foreach ([$primary, ...( is_array($mirrors) ? $mirrors : [] )] as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }
            $parsed = parse_url($url);
            if (!empty($parsed['host'])) {
                $map[strtolower((string) $parsed['host'])] = true;
            }
        }

        foreach (is_array($extra) ? $extra : [] as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }
            if (str_contains($item, '://')) {
                $parsed = parse_url($item);
                if (!empty($parsed['host'])) {
                    $map[strtolower((string) $parsed['host'])] = true;
                }
            } else {
                $map[strtolower($item)] = true;
            }
        }

        foreach (self::TRUSTED_FLUTE_DOWNLOAD_HOSTS as $host) {
            $map[strtolower($host)] = true;
        }

        return $map;
    }

    protected function assertDownloadUrlAllowed(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new Exception(__('admin-marketplace.messages.download_url_not_allowed'));
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);

        $allowHttp = function_exists('is_debug') && is_debug() || (bool) config('app.development_mode', false);
        if ($scheme !== 'https' && !( $scheme === 'http' && $allowHttp )) {
            throw new Exception(__('admin-marketplace.messages.download_url_not_allowed'));
        }

        $trusted = $this->getTrustedMarketplaceDownloadHostMap();
        if ($trusted === [] || !isset($trusted[$host])) {
            throw new Exception(__('admin-marketplace.messages.download_url_not_allowed'));
        }
    }

    protected function cleanupFailedDownloadArtifact(): void
    {
        if ($this->moduleArchivePath !== null && is_file($this->moduleArchivePath)) {
            @unlink($this->moduleArchivePath);
        }
    }

    protected function assertValidZipFile(string $path): void
    {
        if (!is_file($path)) {
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        $size = filesize($path);
        if ($size === false || $size < 22) {
            @unlink($path);
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            @unlink($path);
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        $magic = fread($handle, 4);
        fclose($handle);

        if (
            $magic !== self::ZIP_MAGIC_LOCAL
            && $magic !== self::ZIP_MAGIC_EMPTY
            && $magic !== self::ZIP_MAGIC_SPANNED
        ) {
            @unlink($path);
            throw new Exception(__('admin-marketplace.messages.invalid_zip'));
        }

        app(FileUploader::class)->inspectZipArchive($path, [
            'max_entries' => 5000,
            'max_total_size' => 100 * 1024 * 1024,
            'min_compression_ratio' => 0.001,
        ]);
    }
}
