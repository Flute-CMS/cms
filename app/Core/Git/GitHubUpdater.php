<?php

namespace Flute\Core\Git;

use Exception;
use Flute\Core\Git\Exceptions\AlreadyInstalledException;
use Flute\Core\Git\Exceptions\FailedToExtractException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ZipArchive;

class GitHubUpdater
{
    protected string $repoOwner;

    protected string $repoName;

    protected ?string $currentVersion;

    protected ?string $downloadDir;

    protected Client $httpClient;

    public function __construct(string $repoOwner, string $repoName, ?string $currentVersion = null, ?string $downloadDir = null)
    {
        $this->repoOwner = $repoOwner;
        $this->repoName = $repoName;
        $this->currentVersion = $currentVersion;

        if ($downloadDir) {
            $this->downloadDir = rtrim($downloadDir, '/') . '/';
        }

        // Initialize Guzzle HTTP Client
        $this->httpClient = new Client([
            'base_uri' => 'https://api.github.com/',
            'timeout' => 60.0,
            'headers' => [
                'User-Agent' => 'PHP',
            ],
        ]);
    }

    public function getLatestRelease()
    {
        return cache()->callback('flute.git.' . $this->repoOwner . $this->repoName, function () {
            $url = "repos/{$this->repoOwner}/{$this->repoName}/releases";

            try {
                $response = $this->httpClient->get($url);
                $decode = json_decode($response->getBody()->getContents(), true);

                return $decode[0] ?? $decode;
            } catch (RequestException $e) {
                logs()->error("Failed to fetch latest release: {$e->getMessage()}");

                return null;
            }
        }, 300);
    }

    public function getLatestVersion()
    {
        $release = $this->getLatestRelease();

        return isset($release['tag_name']) ? str_replace('v', '', $release['tag_name']) : '';
    }

    public function update(array $foldersToExtract = ["*"])
    {
        $release = $this->getLatestRelease();

        if (!$release) {
            throw new Exception('Unable to get the latest release.');
        }

        $latestVersion = str_replace('v', '', $release['tag_name']);

        if (!version_compare($this->currentVersion, $latestVersion, '<')) {
            throw new AlreadyInstalledException();
        }

        $zipUrl = $release['zipball_url'];
        $zipFile = $this->downloadDir . $this->repoName . '-' . $latestVersion . '.zip';

        $this->downloadFile($zipUrl, $zipFile);
        $this->extractZip($zipFile, $this->downloadDir, $foldersToExtract);

        $this->currentVersion = $latestVersion;

        try {
            fs()->remove($zipFile);
        } catch (Exception $e) {
            logs()->warning($e);
        }

        return true;
    }

    protected function downloadFile($url, $path)
    {
        set_time_limit(0);

        try {
            $response = $this->httpClient->get($url, ['sink' => $path]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception("Failed to download file: HTTP " . $response->getStatusCode());
            }
        } catch (RequestException $e) {
            logs()->error("Failed to download file: {$e->getMessage()}");

            throw $e;
        }
    }

    protected function extractZip($zipFile, $extractTo, array $foldersToExtract)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            $foldersToExtract = array_map(static fn ($folder) => rtrim($folder, '/') . '/', $foldersToExtract);

            $rootFolder = $zip->getNameIndex(0);
            $rootFolder = substr($rootFolder, 0, strpos($rootFolder, '/') + 1);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                foreach ($foldersToExtract as $folder) {
                    $relativePath = substr($filename, strlen($rootFolder));
                    if ($folder === '*' || strpos($relativePath, $folder) === 0) {
                        $targetPath = $extractTo . $relativePath;

                        if ($zip->extractTo($extractTo, $filename)) {
                            $extractedPath = $extractTo . $filename;

                            if (is_dir($extractedPath)) {
                                if (!file_exists($targetPath)) {
                                    mkdir($targetPath, 0o777, true);
                                }
                            } else {
                                if (!is_dir(dirname($targetPath))) {
                                    mkdir(dirname($targetPath), 0o777, true);
                                }
                                rename($extractedPath, $targetPath);
                            }
                        }
                    }
                }
            }

            $zip->close();

            $this->deleteDirectory($extractTo . $rootFolder);
        } else {
            throw new FailedToExtractException($zipFile);
        }
    }

    protected function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            (is_dir("{$dir}/{$file}")) ? $this->deleteDirectory("{$dir}/{$file}") : unlink("{$dir}/{$file}");
        }

        return rmdir($dir);
    }
}
