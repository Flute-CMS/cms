<?php

namespace Flute\Core\Composer;

use Composer\Console\Application;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class ComposerManager
 *
 * Manages Composer package installation, removal, and retrieval.
 */
class ComposerManager
{
    /**
     * Installs a Composer package.
     *
     * @param string $package The name of the package to install.
     *
     * @throws Exception If the installation fails.
     * @return string The output of the Composer command.
     */
    public function installPackage(string $package)
    {
        $this->bootstrapEnv();
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "require",
                'packages' => [$package],
                '--working-dir' => getcwd(),
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
                '--no-scripts' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $app->run($input, $output);
            $outputContent = $output->fetch();

            if (strpos($outputContent, 'Installation failed') !== false) {
                throw new Exception('Installation failed: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new Exception('Composer runtime error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Error during package installation: ' . $e->getMessage());
        }
    }

    public function install()
    {
        $this->bootstrapEnv();
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "install",
                '--working-dir' => getcwd(),
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
                '--no-dev' => true,
                '--no-scripts' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $exitCode = $app->run($input, $output);
            $outputContent = $output->fetch();

            if (
                strpos($outputContent, 'Your requirements could not be resolved') !== false ||
                strpos($outputContent, 'Problem ') !== false ||
                strpos($outputContent, 'Installation failed') !== false ||
                strpos($outputContent, 'Removal failed') !== false ||
                strpos($outputContent, 'error') !== false
            ) {
                throw new Exception('Install failed: ' . $outputContent);
            }

            if ($exitCode !== 0) {
                throw new Exception('Composer exit with error. Exit code: ' . $exitCode . '. Output: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new Exception('Composer runtime error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Error during install: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $this->bootstrapEnv();
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "update",
                '--working-dir' => getcwd(),
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
                '--no-dev' => true,
                '--no-scripts' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $exitCode = $app->run($input, $output);
            $outputContent = $output->fetch();

            if (
                strpos($outputContent, 'Your requirements could not be resolved') !== false ||
                strpos($outputContent, 'Problem ') !== false ||
                strpos($outputContent, 'Installation failed') !== false ||
                strpos($outputContent, 'Removal failed') !== false ||
                strpos($outputContent, 'error') !== false
            ) {
                throw new Exception('Update failed: ' . $outputContent);
            }

            if ($exitCode !== 0) {
                throw new Exception('Composer exit with error. Exit code: ' . $exitCode . '. Output: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new Exception('Composer runtime error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Error during update: ' . $e->getMessage());
        }
    }

    /**
     * Removes a Composer package.
     *
     * @param string $package The name of the package to remove.
     *
     * @throws Exception If the removal fails.
     * @return string The output of the Composer command.
     */
    public function removePackage(string $package)
    {
        $this->bootstrapEnv();
        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(
            [
                'command' => "remove",
                'packages' => [$package],
                '--working-dir' => getcwd(),
                '--no-interaction' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
                '--no-scripts' => true,
            ]
        );

        $output = new BufferedOutput();

        try {
            $app->run($input, $output);
            $outputContent = $output->fetch();

            if (strpos($outputContent, 'Removal failed') !== false) {
                throw new Exception('Removal failed: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            throw new Exception('Composer runtime error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Error during package removal: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves the list of Composer packages.
     *
     * @return array The array of required packages from composer.json.
     */
    public function getPackages()
    {
        $this->bootstrapEnv();
        $packages = json_decode(file_get_contents(getcwd() . '/composer.json'), true);

        return $packages['require'];
    }

    /**
     * Fetches package information from Packagist.
     *
     * @param int|null $page The page number for pagination.
     * @param string|null $search The search query.
     * @param int|null $length The number of results per page.
     *
     * @return array The array of package information.
     */
    public function getPackagistItems(?int $page, ?string $search, ?int $length)
    {
        set_time_limit(0);
        $guzzle = new Client();

        if (!empty($search)) {
            $res = $guzzle->get("https://packagist.org/search.json?q={$search}&per_page={$length}&page={$page}");

            return json_decode($res->getBody()->getContents(), true);
        }
        $res = $guzzle->get("https://packagist.org/explore/popular.json?per_page={$length}&page={$page}");
        $content = json_decode($res->getBody()->getContents(), true);

        return [
            'results' => $content['packages'],
            'total' => $content['total'],
        ];

    }

    /**
     * Bootstrap the environment for Composer (once before calls).
     */
    private function bootstrapEnv(): void
    {
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;

        $base = rtrim(BASE_PATH, " \t\n\r\0\x0B");
        $real = realpath($base);
        $base = $real !== false ? $real : $base;

        @chdir($base);

        @set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '-1');
        }

        $composerDir = storage_path('composer');
        $composerHome = $composerDir . '/home';
        $composerCache = $composerDir . '/cache';
        $tempDir = $composerDir . '/tmp';

        foreach ([$composerHome, $composerCache, $tempDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0o775, true);
            }
        }

        putenv('COMPOSER_HOME=' . $composerHome);
        putenv('COMPOSER_CACHE_DIR=' . $composerCache);
        putenv('COMPOSER_TMP_DIR=' . $tempDir);
        putenv('TMPDIR=' . $tempDir);
        putenv('HOME=' . $base);

        if (function_exists('ini_set') && is_writable($tempDir)) {
            @ini_set('sys_temp_dir', $tempDir);
        }

        putenv('COMPOSER_DISABLE_XDEBUG_WARN=1');
        putenv('COMPOSER_MEMORY_LIMIT=-1');
        putenv('COMPOSER_ALLOW_SUPERUSER=1');
        putenv('COMPOSER_NO_INTERACTION=1');
        putenv('COMPOSER_PROCESS_TIMEOUT=600');
    }
}
