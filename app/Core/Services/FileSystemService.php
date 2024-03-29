<?php

namespace Flute\Core\Services;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class FileSystemService extends Filesystem
{
    /**
     * Import helpers in the file system.
     * 
     * @return void
     */
    public function importHelpers(): void
    {
        // hardcode, sorry
        $helpersPath = BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Helpers';
        $this->importDirectory($helpersPath);
    }

    /**
     * Import a directory
     *
     * @param string $dir
     *
     * @return void
     */
    public function importDirectory(string $dir): void
    {
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(true)->in($dir)->sortByName();

        foreach ($finder as $file) {
            $path = $file->getRealPath();

            if ($file->isFile()) {
                // обработка файла
                require_once $path;
            }
        }
    }

    /**
     * Update a PHP configuration file
     *
     * @param string $filePath Full path to the configuration file
     * @param array $newConfig New configuration array to write to the file
     *
     * @return void
     * @throws Exception
     */
    public function updateConfig(string $filePath, array $newConfig): void
    {
        if (!is_writable($filePath)) {
            throw new Exception(sprintf('Configuration file "%s" is not writable.', $filePath));
        }

        $configString = "<?php\n\nreturn " . var_export($newConfig, true) . ";";

        $this->dumpFile($filePath, $configString);

        if( function_exists('opcache_reset') ) opcache_reset();
    }
}