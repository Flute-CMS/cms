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
    private const DEFAULT_TIMEOUT = 600;

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
        return $this->runComposer(
            'require',
            [
                'packages' => [$package],
            ]
        );
    }

    public function install()
    {
        return $this->runComposer(
            'install',
            [
                '--no-dev' => true,
            ]
        );
    }

    public function update()
    {
        return $this->runComposer(
            'update',
            [
                '--no-dev' => true,
            ]
        );
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
        return $this->runComposer(
            'remove',
            [
                'packages' => [$package],
            ]
        );
    }

    /**
     * Retrieves the list of Composer packages.
     *
     * @return array The array of required packages from composer.json.
     */
    public function getPackages()
    {
        $this->bootstrapEnv();
        $packages = json_decode(file_get_contents($this->workingDir() . '/composer.json'), true);

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

    private function runComposer(string $command, array $extraInput = []): string
    {
        $this->bootstrapEnv();
        $this->assertWritableWorkspace();

        $app = new Application();
        $app->setAutoExit(false);

        $input = new ArrayInput(array_merge(
            [
                'command' => $command,
                '--working-dir' => $this->workingDir(),
                '--no-interaction' => true,
                '--no-ansi' => true,
                '--no-progress' => true,
                '--optimize-autoloader' => true,
                '-v' => true,
                '--ignore-platform-reqs' => true,
                '--no-scripts' => true,
            ],
            $extraInput
        ));

        $output = new BufferedOutput();

        try {
            [$exitCode, $outputContent] = $this->suppressDeprecationsDuring(static function () use ($app, $input, $output) {
                $exitCode = $app->run($input, $output);

                return [$exitCode, $output->fetch()];
            });

            if ($exitCode !== 0) {
                throw new Exception('Composer command failed: "' . $command . '" (exit code ' . $exitCode . '). Output: ' . $outputContent);
            }

            return $outputContent;
        } catch (RuntimeException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'Could not delete ') || str_contains($message, 'Could not delete')) {
                $message .= ' (check permissions/ownership for "vendor/" and "composer.lock")';
            }

            throw new Exception('Composer runtime error: ' . $message);
        } catch (Exception $e) {
            throw new Exception('Error during composer "' . $command . '": ' . $e->getMessage());
        }
    }

    private function suppressDeprecationsDuring(callable $callback): mixed
    {
        $oldDisplayErrors = ini_get('display_errors');
        $oldErrorReporting = error_reporting();

        if (function_exists('ini_set')) {
            @ini_set('display_errors', '0');
        }
        error_reporting($oldErrorReporting & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            return $callback();
        } finally {
            if (function_exists('ini_set')) {
                @ini_set('display_errors', (string)$oldDisplayErrors);
            }
            error_reporting($oldErrorReporting);
        }
    }

    private function workingDir(): string
    {
        $base = rtrim(BASE_PATH, " \t\n\r\0\x0B");
        $real = realpath($base);

        return rtrim($real !== false ? $real : $base, DIRECTORY_SEPARATOR);
    }

    private function assertWritableWorkspace(): void
    {
        $workingDir = $this->workingDir();

        $composerJson = $workingDir . '/composer.json';
        if (file_exists($composerJson) && !is_writable($composerJson)) {
            throw new Exception('composer.json is not writable: ' . $composerJson);
        }

        $composerLock = $workingDir . '/composer.lock';
        if (file_exists($composerLock) && !is_writable($composerLock)) {
            throw new Exception('composer.lock is not writable: ' . $composerLock);
        }
        if (!file_exists($composerLock) && !is_writable($workingDir)) {
            throw new Exception('Project directory is not writable (composer.lock cannot be created): ' . $workingDir);
        }

        $vendorDir = $workingDir . '/vendor';
        if (is_dir($vendorDir) && !is_writable($vendorDir)) {
            throw new Exception('vendor directory is not writable: ' . $vendorDir);
        }
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

        $base = $this->workingDir();

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
        putenv('COMPOSER_PROCESS_TIMEOUT=' . self::DEFAULT_TIMEOUT);
    }
}
