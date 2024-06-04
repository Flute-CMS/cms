<?php

namespace Flute\Core\Git;

use Flute\Core\Git\Exceptions\AlreadyInstalledException;
use Flute\Core\Git\Exceptions\FailedToExtractException;

class GitHubUpdater
{
    protected string $repoOwner;
    protected string $repoName;
    protected ?string $currentVersion;
    protected ?string $downloadDir;

    public function __construct(string $repoOwner, string $repoName, ?string $currentVersion = null, ?string $downloadDir = null)
    {
        $this->repoOwner = $repoOwner;
        $this->repoName = $repoName;
        $this->currentVersion = $currentVersion;
        $this->downloadDir = rtrim($downloadDir, '/') . '/';
    }

    public function getLatestRelease()
    {
        return cache()->callback('flute.git.' . $this->repoOwner . $this->repoName, function () {
            $url = "https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
            $response = curl_exec($ch);
            curl_close($ch);

            $decode = json_decode($response, true);

            return isset($decode[0]) ? $decode[0] : $decode;
        }, 300);
    }

    public function getLatestVersion()
    {
        $release = $this->getLatestRelease();
        return str_replace('v', '', $release['tag_name']);
    }

    public function update(array $foldersToExtract = ["*"])
    {
        $release = $this->getLatestRelease();
        $latestVersion = str_replace('v', '', $release['tag_name']);

        if (!version_compare($this->currentVersion, $latestVersion, '<')) {
            throw new AlreadyInstalledException;
        }

        $zipUrl = $release['zipball_url'];
        $zipFile = $this->downloadDir . $this->repoName . '-' . $latestVersion . '.zip';

        $this->downloadFile($zipUrl, $zipFile);
        $this->extractZip($zipFile, $this->downloadDir, $foldersToExtract);

        $this->currentVersion = $latestVersion;
        return true;
    }

    protected function downloadFile($url, $path)
    {
        set_time_limit(0);
        $fp = fopen($path, 'w+');
        $ch = curl_init(str_replace(" ", "%20", $url));
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    protected function extractZip($zipFile, $extractTo, array $foldersToExtract)
    {
        $zip = new \ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            $foldersToExtract = array_map(function ($folder) {
                return rtrim($folder, '/') . '/';
            }, $foldersToExtract);

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
                                if (!file_exists($targetPath))
                                    mkdir($targetPath, 0777, true);
                            } else {
                                if (!is_dir(dirname($targetPath))) {
                                    mkdir(dirname($targetPath), 0777, true);
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

        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }
}
