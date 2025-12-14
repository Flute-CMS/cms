<?php

namespace Flute\Core\Services;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Throwable;

class FileSystemService extends Filesystem
{
    /**
     * Import helpers in the file system, using cache if available.
     */
    public function importHelpers(): void
    {
        $cacheDir = BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'helpers.cache.php';
        $metaFile = $cacheDir . DIRECTORY_SEPARATOR . 'helpers.cache.meta.php';
        $lockFile = $cacheDir . DIRECTORY_SEPARATOR . 'helpers.cache.lock';
        $helpersPath = BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Helpers';

        if ($this->tryLoadCache($cacheFile, $metaFile, $helpersPath)) {
            return;
        }

        $this->mkdir($cacheDir);

        $handle = @fopen($lockFile, 'c+');
        if ($handle === false) {
            $this->generateHelpersCache($helpersPath, $cacheFile);
            $this->writeHelpersCacheMeta($helpersPath, $metaFile);

            return;
        }

        $gotLock = @flock($handle, LOCK_EX | LOCK_NB);
        if (!$gotLock) {
            $maxWait = 2.0;
            $waited = 0.0;

            while ($waited < $maxWait) {
                if ($this->tryLoadCache($cacheFile, $metaFile, $helpersPath)) {
                    @fclose($handle);

                    return;
                }

                usleep(100000);
                $waited += 0.1;
            }

            $gotLock = @flock($handle, LOCK_EX);
        }

        try {
            if ($this->tryLoadCache($cacheFile, $metaFile, $helpersPath)) {
                return;
            }

            $this->generateHelpersCache($helpersPath, $cacheFile);
            $this->writeHelpersCacheMeta($helpersPath, $metaFile);
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    /**
     * Update a PHP configuration file.
     *
     * @param string $filePath Full path to the configuration file
     * @param array $newConfig New configuration array to write to the file
     *
     * @throws Exception
     */
    public function updateConfig(string $filePath, array $newConfig): void
    {
        if (!is_writable($filePath)) {
            throw new Exception(sprintf('Configuration file "%s" is not writable.', $filePath));
        }

        $configString = "<?php\n\nreturn " . var_export($newConfig, true) . ";";
        $this->dumpFile($filePath, $configString);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, /* force */ true);
        }
    }

    /**
     * Generate helpers cache file from the specified directory.
     */
    private function generateHelpersCache(string $dir, string $cacheFile): void
    {
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->in($dir)->sortByName();

        $cacheContent = "<?php\n\n";
        $useStatements = [];

        $this->mkdir(dirname($cacheFile));

        foreach ($finder as $file) {
            $fileContent = file_get_contents($file->getRealPath());
            $useStatements = array_merge($useStatements, $this->extractUseStatements($fileContent));
            $fileContent = $this->removePhpTags($fileContent);
            $fileContent = $this->removeComments($fileContent);
            $fileContent = $this->removeEmptyLines($fileContent);
            $fileContent = $this->removeUseStatements($fileContent);
            $fileContent = $this->minifyWhitespace($fileContent);
            $cacheContent .= $fileContent . "\n\n";
        }

        $uniqueUseStatements = array_unique($useStatements);
        $cacheContent = "<?php if(!defined('FLUTE_HELPERS_OK')){define('FLUTE_HELPERS_OK','1');}" . implode("", $uniqueUseStatements) . "" . str_replace('<?php', '', $cacheContent);

        $this->dumpFile($cacheFile, $cacheContent);
        require_once $cacheFile;
    }

    /**
     * Attempt to load helpers cache; rebuild trigger if it looks broken.
     */
    private function tryLoadCache(string $cacheFile, string $metaFile, string $helpersDir): bool
    {
        if (!$this->isCacheValid($cacheFile) || !$this->isHelpersMetaValid($metaFile, $helpersDir)) {
            return false;
        }

        try {
            require_once $cacheFile;
        } catch (Throwable $e) {
            @unlink($cacheFile);
            @unlink($metaFile);

            return false;
        }

        $required = ['is_cli', 'app', 'config'];

        $isHealthy = defined('FLUTE_HELPERS_OK');

        foreach ($required as $function) {
            if (!function_exists($function)) {
                $isHealthy = false;

                break;
            }
        }

        if ($isHealthy) {
            return true;
        }

        @unlink($cacheFile);
        @unlink($metaFile);

        return false;
    }

    private function minifyWhitespace(string $content): string
    {
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s*([{}();,:<>+-=])\s*/', '$1', $content);

        return $content;
    }

    /**
     * Remove all comments from the content.
     */
    private function removeComments(string $content): string
    {
        return preg_replace([
            '/\/\*.*?\*\//s',
            '/\/\/[^\r\n]*/',
        ], '', $content);
    }

    /**
     * Remove empty lines from the content.
     */
    private function removeEmptyLines(string $content): string
    {
        return preg_replace('/^\s*[\r\n]+/m', '', $content);
    }

    /**
     * Remove PHP opening tags from the content.
     */
    private function removePhpTags(string $content): string
    {
        return preg_replace('/<\?php\s*/', '', $content);
    }

    /**
     * Extract all "use" statements from the content.
     */
    private function extractUseStatements(string $content): array
    {
        preg_match_all('/^use\s+[^;]+;/m', $content, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Remove all "use" statements from the content.
     */
    private function removeUseStatements(string $content): string
    {
        return preg_replace('/^use\s+[^;]+;/m', '', $content);
    }

    /**
     * Check if the cache file is valid.
     */
    private function isCacheValid(string $cacheFile): bool
    {
        return file_exists($cacheFile) && is_readable($cacheFile);
    }

    private function isHelpersMetaValid(string $metaFile, string $helpersDir): bool
    {
        if (!is_file($metaFile) || !is_readable($metaFile)) {
            return false;
        }

        try {
            $meta = include $metaFile;
        } catch (Throwable) {
            return false;
        }

        if (!is_array($meta)) {
            return false;
        }

        $expectedFingerprint = $meta['fingerprint'] ?? null;
        if (!is_string($expectedFingerprint) || $expectedFingerprint === '') {
            return false;
        }

        try {
            $currentFingerprint = $this->computeHelpersFingerprint($helpersDir);
        } catch (Throwable) {
            return false;
        }

        return hash_equals($expectedFingerprint, $currentFingerprint);
    }

    private function writeHelpersCacheMeta(string $helpersDir, string $metaFile): void
    {
        try {
            $fingerprint = $this->computeHelpersFingerprint($helpersDir);
        } catch (Throwable) {
            return;
        }

        $meta = [
            'fingerprint' => $fingerprint,
            'written_at' => time(),
        ];

        $this->mkdir(dirname($metaFile));
        $content = '<?php return ' . var_export($meta, true) . ';';
        $tmp = $metaFile . '.tmp';

        @file_put_contents($tmp, $content, LOCK_EX);
        @rename($tmp, $metaFile);
    }

    private function computeHelpersFingerprint(string $helpersDir): string
    {
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->in($helpersDir)->name('*.php')->sortByName();

        $parts = [];

        foreach ($finder as $file) {
            $real = $file->getRealPath() ?: $file->getPathname();
            $parts[] = $real . '|' . $file->getMTime() . '|' . $file->getSize();
        }

        return hash('sha256', implode("\n", $parts));
    }
}
