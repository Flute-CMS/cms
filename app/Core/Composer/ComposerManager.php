<?php

namespace Flute\Core\Composer;

use Composer\Console\Application;
use Exception;
use FilesystemIterator;
use GuzzleHttp\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    private const MAINTENANCE_FLAG = 'storage/app/.maintenance-composer';

    private const MAINTENANCE_FLAG_PUBLIC = 'public/.maintenance-composer';

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
            ]
        );
    }

    public function update()
    {
        return $this->runComposer(
            'update',
            [
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
            return $this->runComposerSafely($app, $input, $output, $command);
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

    private function runComposerSafely(Application $app, ArrayInput $input, BufferedOutput $output, string $command): string
    {
        $lockHandle = null;
        $rollback = null;
        $hadMaintenanceFlag = false;
        $shouldDisableMaintenanceFlag = true;
        $maintenancePayload = [];
        $vendorBackup = null;

        try {
            $lockHandle = $this->acquireComposerLock();
            $hadMaintenanceFlag = is_file($this->maintenanceFlagPath());
            $maintenancePayload = $this->enableMaintenanceFlag();
            $rollback = $this->createRollbackState();
            $vendorBackup = $this->createVendorBackupIfEnabled();

            [$exitCode, $outputContent] = $this->suppressDeprecationsDuring(static function () use ($app, $input, $output) {
                $exitCode = $app->run($input, $output);

                return [$exitCode, $output->fetch()];
            });

            if ($exitCode !== 0) {
                throw new Exception('Composer command failed: "' . $command . '" (exit code ' . $exitCode . '). Output: ' . $outputContent);
            }

            $this->cleanupRollbackState($rollback);
            $this->cleanupVendorBackup($vendorBackup);

            return $outputContent;
        } catch (Exception $e) {
            $vendorRestoreMessage = null;
            if (is_array($vendorBackup) && !empty($vendorBackup['path'])) {
                $vendorRestoreMessage = $this->restoreVendorBackup($vendorBackup['path']);
                if ($vendorRestoreMessage !== 'vendor restored') {
                    $shouldDisableMaintenanceFlag = false;
                    $this->markMaintenanceAsForced($maintenancePayload, 'vendor rollback failed');
                }
            }

            if ($rollback !== null) {
                $restoreMessage = $this->restoreRollbackState($rollback);
                $repairMessage = null;
                if ($vendorRestoreMessage === null || $vendorRestoreMessage !== 'vendor restored') {
                    $repairMessage = $this->attemptRepairInstall($rollback);
                }

                $message = $e->getMessage();
                if ($restoreMessage !== null) {
                    $message .= ' | rollback: ' . $restoreMessage;
                }
                if ($vendorRestoreMessage !== null) {
                    $message .= ' | vendor: ' . $vendorRestoreMessage;
                }
                if ($repairMessage !== null) {
                    $message .= ' | repair: ' . $repairMessage;
                }
                $message .= ' | backup: ' . $rollback['dir'];

                throw new Exception($message);
            }

            throw $e;
        } finally {
            if (!$hadMaintenanceFlag && $shouldDisableMaintenanceFlag) {
                $this->disableMaintenanceFlag();
            }

            if (is_resource($lockHandle)) {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
            }
        }
    }

    private function attemptRepairInstall(array $rollback): ?string
    {
        try {
            $this->bootstrapEnv();

            $app = new Application();
            $app->setAutoExit(false);

            $input = new ArrayInput(
                [
                    'command' => 'install',
                    '--working-dir' => $this->workingDir(),
                    '--no-interaction' => true,
                    '--no-ansi' => true,
                    '--no-progress' => true,
                    '--optimize-autoloader' => true,
                    '-v' => true,
                    '--ignore-platform-reqs' => true,
                    '--no-scripts' => true,
                ]
            );

            $output = new BufferedOutput();

            [$exitCode, $outputContent] = $this->suppressDeprecationsDuring(static function () use ($app, $input, $output) {
                $exitCode = $app->run($input, $output);

                return [$exitCode, $output->fetch()];
            });

            if ($exitCode !== 0) {
                return 'install exit code ' . $exitCode;
            }

            return 'install ok';
        } catch (Exception $e) {
            return $e->getMessage();
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

    private function maintenanceFlagPath(): string
    {
        $base = rtrim(str_replace('\\', '/', $this->workingDir()), '/');

        return $base . '/' . self::MAINTENANCE_FLAG;
    }

    private function publicMaintenanceFlagPath(): string
    {
        $base = rtrim(str_replace('\\', '/', $this->workingDir()), '/');

        return $base . '/' . self::MAINTENANCE_FLAG_PUBLIC;
    }

    private function enableMaintenanceFlag(): array
    {
        $path = $this->maintenanceFlagPath();
        $publicPath = $this->publicMaintenanceFlagPath();
        $dir = dirname($path);
        $publicDir = dirname($publicPath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0o775, true);
        }
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0o775, true);
        }

        if (is_file($path)) {
            $existing = $this->readMaintenancePayload($path);
            @file_put_contents($publicPath, '1');

            return $existing;
        }

        $payload = [
            'title' => 'Maintenance',
            'message' => 'Update in progress, please try again shortly.',
            'started_at' => date(DATE_ATOM),
            'pid' => getmypid(),
            'force' => false,
        ];

        @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @file_put_contents($publicPath, '1');

        return $payload;
    }

    private function disableMaintenanceFlag(): void
    {
        $path = $this->maintenanceFlagPath();
        if (is_file($path)) {
            $payload = $this->readMaintenancePayload($path);
            if (!empty($payload['force'])) {
                return;
            }

            @unlink($path);
        }

        $publicPath = $this->publicMaintenanceFlagPath();
        if (is_file($publicPath)) {
            @unlink($publicPath);
        }
    }

    private function readMaintenancePayload(string $path): array
    {
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function markMaintenanceAsForced(array $payload, string $reason): void
    {
        $payload['force'] = true;
        $payload['force_reason'] = $reason;
        $payload['updated_at'] = date(DATE_ATOM);

        @file_put_contents($this->maintenanceFlagPath(), json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @file_put_contents($this->publicMaintenanceFlagPath(), '1');
    }

    private function createVendorBackupIfEnabled(): ?array
    {
        if (!config('app.create_backup') || !config('app.backup_vendor_on_composer')) {
            return null;
        }

        $workingDir = $this->workingDir();
        $vendorDir = $workingDir . '/vendor';
        if (!is_dir($vendorDir)) {
            return null;
        }

        $backupDir = storage_path('backup/vendor/' . date('Y-m-d-His') . '-' . bin2hex(random_bytes(4)));
        $vendorBackupDir = $backupDir . '/vendor';

        if (!is_dir($vendorBackupDir)) {
            @mkdir($vendorBackupDir, 0o775, true);
        }

        $this->copyDirectory($vendorDir, $vendorBackupDir);
        $this->pruneVendorBackups(1);

        return [
            'dir' => $backupDir,
            'path' => $vendorBackupDir,
        ];
    }

    private function cleanupVendorBackup(?array $vendorBackup): void
    {
        if (!is_array($vendorBackup) || empty($vendorBackup['dir']) || !is_string($vendorBackup['dir'])) {
            return;
        }
    }

    private function restoreVendorBackup(string $backupVendorDir): string
    {
        $workingDir = $this->workingDir();
        $vendorDir = $workingDir . '/vendor';

        if (!is_dir($backupVendorDir)) {
            return 'backup missing';
        }

        $this->removeDirectory($vendorDir);

        if (is_dir($vendorDir)) {
            return 'failed to remove current vendor';
        }

        @mkdir($vendorDir, 0o775, true);
        $this->copyDirectory($backupVendorDir, $vendorDir);

        if (!is_file($vendorDir . '/autoload.php')) {
            return 'restore incomplete';
        }

        return 'vendor restored';
    }

    private function pruneVendorBackups(int $keep): void
    {
        $base = storage_path('backup/vendor');
        if (!is_dir($base)) {
            return;
        }

        $dirs = [];
        $items = @scandir($base);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $base . '/' . $item;
            if (is_dir($full)) {
                $dirs[] = $full;
            }
        }

        usort($dirs, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $toDelete = array_slice($dirs, $keep);
        foreach ($toDelete as $dir) {
            $this->removeDirectory($dir);
        }
    }

    private function copyDirectory(string $source, string $destination): void
    {
        $source = rtrim($source, '/');
        $destination = rtrim($destination, '/');

        if (!is_dir($destination)) {
            @mkdir($destination, 0o775, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen(str_replace('\\', '/', $source)) + 1);
            $target = $destination . '/' . $relative;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    @mkdir($target, 0o775, true);
                }

                continue;
            }

            $targetDir = dirname($target);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0o775, true);
            }

            @copy($item->getPathname(), $target);
        }
    }

    /**
     * Create rollback state for composer.json / composer.lock.
     *
     * @return array{dir:string,composer_json:?string,composer_lock:?string,composer_lock_existed:bool}
     */
    private function createRollbackState(): array
    {
        $workingDir = $this->workingDir();
        $backupDir = storage_path('backup/composer/' . date('Y-m-d-His') . '-' . bin2hex(random_bytes(4)));

        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0o775, true);
        }

        $composerJson = $workingDir . '/composer.json';
        $composerLock = $workingDir . '/composer.lock';

        $composerJsonBackup = null;
        $composerLockBackup = null;
        $composerLockExisted = is_file($composerLock);

        if (is_file($composerJson)) {
            $composerJsonBackup = $backupDir . '/composer.json';
            @copy($composerJson, $composerJsonBackup);
        }

        if ($composerLockExisted) {
            $composerLockBackup = $backupDir . '/composer.lock';
            @copy($composerLock, $composerLockBackup);
        }

        return [
            'dir' => $backupDir,
            'composer_json' => $composerJsonBackup,
            'composer_lock' => $composerLockBackup,
            'composer_lock_existed' => $composerLockExisted,
        ];
    }

    private function restoreRollbackState(array $rollback): ?string
    {
        $workingDir = $this->workingDir();
        $messages = [];

        if (!empty($rollback['composer_json']) && is_file($rollback['composer_json'])) {
            $ok = @copy($rollback['composer_json'], $workingDir . '/composer.json');
            $messages[] = $ok ? 'composer.json restored' : 'composer.json restore failed';
        }

        if (!empty($rollback['composer_lock']) && is_file($rollback['composer_lock'])) {
            $ok = @copy($rollback['composer_lock'], $workingDir . '/composer.lock');
            $messages[] = $ok ? 'composer.lock restored' : 'composer.lock restore failed';
        } elseif (($rollback['composer_lock_existed'] ?? true) === false) {
            $path = $workingDir . '/composer.lock';
            if (is_file($path)) {
                $ok = @unlink($path);
                $messages[] = $ok ? 'composer.lock removed' : 'composer.lock remove failed';
            }
        }

        if ($messages === []) {
            return null;
        }

        return implode(', ', $messages);
    }

    private function cleanupRollbackState(array $rollback): void
    {
        $dir = $rollback['dir'] ?? null;
        if (!is_string($dir) || $dir === '' || !is_dir($dir)) {
            return;
        }

        $this->removeDirectory($dir);
    }

    private function acquireComposerLock()
    {
        $lockPath = storage_path('composer/lock');
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return null;
        }

        $start = microtime(true);
        $timeout = 30.0;

        while (true) {
            if (@flock($handle, LOCK_EX | LOCK_NB)) {
                return $handle;
            }

            if ((microtime(true) - $start) >= $timeout) {
                @fclose($handle);

                throw new Exception('Composer lock timeout (' . (int)$timeout . 's): ' . $lockPath);
            }

            usleep(200000);
        }

        // Unreachable.
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = @scandir($directory);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
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
