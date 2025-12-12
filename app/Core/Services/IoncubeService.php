<?php

declare(strict_types=1);

namespace Flute\Core\Services;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class IoncubeService
{
    public const DOWNLOADS_PAGE_URL = 'https://www.ioncube.com/loaders.php';

    /**
     * Direct download endpoints discovered from ionCube loaders page.
     */
    private const DIRECT_DOWNLOADS = [
        'Linux:x86_64' => 'https://www.ioncube.com/loaders/download.php?dload=ioncube_loaders_lin_x86-64.tar.gz',
        'Darwin:x86_64' => 'https://www.ioncube.com/loaders/download.php?dload=ioncube_loaders_mac_x86-64.tar.gz',
    ];

    public function isLoaded(): bool
    {
        return function_exists('ioncube_loaded') ? ioncube_loaded() : extension_loaded('ionCube Loader');
    }

    public function getOsFamily(): string
    {
        // PHP_OS_FAMILY gives Windows/Darwin/Linux/BSD/Solaris/Unknown
        return defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY : PHP_OS;
    }

    public function getMachineArch(): string
    {
        $arch = strtolower((string) php_uname('m'));
        if ($arch === '') {
            return 'unknown';
        }

        if (str_contains($arch, 'aarch64') || str_contains($arch, 'arm64')) {
            return 'aarch64';
        }

        if ($arch === 'x86_64' || str_contains($arch, 'amd64')) {
            return 'x86_64';
        }

        if (str_contains($arch, 'i386') || str_contains($arch, 'i686') || str_contains($arch, 'x86')) {
            return 'x86';
        }

        return $arch;
    }

    public function getRecommendedDownloadUrl(): string
    {
        return self::DOWNLOADS_PAGE_URL;
    }

    public function getRecommendedDirectDownloadUrl(): ?string
    {
        $key = $this->getOsFamily() . ':' . $this->getMachineArch();

        return self::DIRECT_DOWNLOADS[$key] ?? null;
    }

    /**
     * Downloads (and tries to extract) ionCube loaders into a local directory.
     *
     * @return array{archivePath:string, extractedPath:?string, directUrl:?string}
     */
    public function downloadLoaders(string $targetDir): array
    {
        $directUrl = $this->getRecommendedDirectDownloadUrl();
        if (!$directUrl) {
            throw new RuntimeException('No direct ionCube loaders download is available for this platform. Use: ' . self::DOWNLOADS_PAGE_URL);
        }

        $fs = new Filesystem();
        $fs->mkdir($targetDir);

        $archiveName = $this->guessArchiveNameFromUrl($directUrl) ?? ('ioncube_loaders_' . time());
        $archivePath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $archiveName;

        $this->downloadFile($directUrl, $archivePath);

        $extractedPath = $this->tryExtractArchive($archivePath, $targetDir);

        return [
            'archivePath' => $archivePath,
            'extractedPath' => $extractedPath,
            'directUrl' => $directUrl,
        ];
    }

    private function guessArchiveNameFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $query = $parts['query'] ?? null;
        if (!is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $q);
        $dload = $q['dload'] ?? null;
        if (!is_string($dload) || $dload === '') {
            return null;
        }

        return basename($dload);
    }

    private function downloadFile(string $url, string $destPath): void
    {
        // Prefer curl if available (more reliable than allow_url_fopen setups).
        if (function_exists('curl_init')) {
            $fp = fopen($destPath, 'wb');
            if ($fp === false) {
                throw new RuntimeException('Cannot write to: ' . $destPath);
            }

            $ch = curl_init($url);
            if ($ch === false) {
                fclose($fp);
                throw new RuntimeException('Cannot init curl.');
            }

            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FluteCMS/ioncube-downloader');

            $ok = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            fclose($fp);

            if ($ok !== true || $code >= 400) {
                @unlink($destPath);
                throw new RuntimeException('Failed to download ionCube loaders. HTTP: ' . $code . ($err ? (', error: ' . $err) : ''));
            }

            return;
        }

        $data = @file_get_contents($url);
        if ($data === false) {
            throw new RuntimeException('Failed to download ionCube loaders. Enable curl or allow_url_fopen.');
        }

        if (@file_put_contents($destPath, $data) === false) {
            throw new RuntimeException('Cannot write to: ' . $destPath);
        }
    }

    private function tryExtractArchive(string $archivePath, string $targetDir): ?string
    {
        $lower = strtolower($archivePath);
        $outDir = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'extracted';

        $fs = new Filesystem();
        $fs->mkdir($outDir);

        if (str_ends_with($lower, '.zip') && class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            if ($zip->open($archivePath) === true) {
                $zip->extractTo($outDir);
                $zip->close();

                return $outDir;
            }
        }

        if (str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz')) {
            if (class_exists(\PharData::class)) {
                try {
                    // Decompress to .tar first
                    $tarPath = preg_replace('/\.(tar\.gz|tgz)$/i', '.tar', $archivePath);
                    if (!is_string($tarPath) || $tarPath === '') {
                        return null;
                    }

                    if (!file_exists($tarPath)) {
                        $pharGz = new \PharData($archivePath);
                        $pharGz->decompress();
                    }

                    $phar = new \PharData($tarPath);
                    $phar->extractTo($outDir, null, true);

                    return $outDir;
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return null;
    }
}


