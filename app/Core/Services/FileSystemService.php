<?php

namespace Flute\Core\Services;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileSystemService extends Filesystem
{
    /**
     * Import helpers in the file system, using cache if available.
     *
     * @return void
     */
    public function importHelpers() : void
    {
        $cacheFile = BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'helpers.cache';

        if ($this->isCacheValid($cacheFile)) {
            require_once $cacheFile;
        } else {
            $helpersPath = BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Helpers';
            $this->generateHelpersCache($helpersPath, $cacheFile);
        }
    }

    /**
     * Generate helpers cache file from the specified directory.
     *
     * @param string $dir
     * @param string $cacheFile
     *
     * @return void
     */
    private function generateHelpersCache(string $dir, string $cacheFile) : void
    {
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->in($dir)->sortByName();

        $cacheContent = "<?php\n\n";
        $useStatements = [];

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
        $cacheContent = "<?php " . implode("", $uniqueUseStatements) . "" . str_replace('<?php', '', $cacheContent);

        $this->dumpFile($cacheFile, $cacheContent);
        require_once $cacheFile;
    }

    private function minifyWhitespace(string $content) : string
    {
        $content = preg_replace('/\s+/', ' ', $content);
        $content = preg_replace('/\s*([{}();,:<>+-=])\s*/', '$1', $content);

        return $content;
    }

    /**
     * Remove all comments from the content.
     *
     * @param string $content
     *
     * @return string
     */
    private function removeComments(string $content) : string
    {
        return preg_replace([
            '/\/\*.*?\*\//s',
            '/\/\/[^\r\n]*/',
        ], '', $content);
    }

    /**
     * Remove empty lines from the content.
     *
     * @param string $content
     *
     * @return string
     */
    private function removeEmptyLines(string $content) : string
    {
        return preg_replace('/^\s*[\r\n]+/m', '', $content);
    }

    /**
     * Remove PHP opening tags from the content.
     *
     * @param string $content
     * 
     * @return string
     */
    private function removePhpTags(string $content) : string
    {
        return preg_replace('/<\?php\s*/', '', $content);
    }

    /**
     * Extract all "use" statements from the content.
     *
     * @param string $content
     * 
     * @return array
     */
    private function extractUseStatements(string $content) : array
    {
        preg_match_all('/^use\s+[^;]+;/m', $content, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Remove all "use" statements from the content.
     *
     * @param string $content
     * 
     * @return string
     */
    private function removeUseStatements(string $content) : string
    {
        return preg_replace('/^use\s+[^;]+;/m', '', $content);
    }

    /**
     * Check if the cache file is valid.
     *
     * @param string $cacheFile
     *
     * @return bool
     */
    private function isCacheValid(string $cacheFile) : bool
    {
        return file_exists($cacheFile) && is_readable($cacheFile);
    }

    /**
     * Update a PHP configuration file.
     *
     * @param string $filePath Full path to the configuration file
     * @param array $newConfig New configuration array to write to the file
     *
     * @return void
     * @throws Exception
     */
    public function updateConfig(string $filePath, array $newConfig) : void
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
}