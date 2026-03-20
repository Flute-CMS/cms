<?php

namespace Flute\Core\Services;

use Noodlehaus\Config;
use RuntimeException;
use Throwable;

class ConfigurationService
{
    protected string $configsPath;

    protected Config $configuration;

    public function __construct(string $configsPath = '/config')
    {
        $configsPath = file_exists(BASE_PATH . 'config-dev') ? BASE_PATH . 'config-dev' : BASE_PATH . 'config';

        $this->configsPath = rtrim($configsPath, DIRECTORY_SEPARATOR);

        $this->loadConfigurations();
    }

    public function loadCustomConfig(string $path, string $name): void
    {
        $this->configuration->set($name, require $path);
    }

    public function get(string $key, $default = null): mixed
    {
        try {
            return $this->configuration->get($key, $default);
        } catch (Throwable $e) {
            if (function_exists('is_debug') && is_debug()) {
                throw $e;
            }

            return $default;
        }
    }

    public function set(string $key, $value): void
    {
        $this->configuration->set($key, $value);
    }

    public function save(): void
    {
        $writtenFiles = [];

        $existingConfigs = $this->getConfigFiles();

        foreach ($existingConfigs as $configName => $configData) {
            if (in_array($configName, ['view', 'cache', 'logging'], true)) {
                continue;
            }

            $configData = $this->configuration->get($configName);

            if ($configData === null) {
                continue;
            }

            $filePath = $this->configsPath . DIRECTORY_SEPARATOR . $configName . '.php';
            $content = "<?php\n\nreturn " . var_export($configData, true) . ";\n";

            if (file_put_contents($filePath, $content) === false) {
                throw new RuntimeException("Failed to write configuration to {$filePath}");
            }

            $writtenFiles[] = $filePath;
        }

        if (function_exists('opcache_invalidate')) {
            foreach ($writtenFiles as $file) {
                opcache_invalidate($file, /* force */ true);
            }
        }

        $this->invalidateCompiledCache();

        $this->loadConfigurations();
    }

    public function getConfiguration(): Config
    {
        return $this->configuration;
    }

    public function getConfigsPath(): string
    {
        return $this->configsPath;
    }

    /**
     * Remove the compiled config cache so fresh values are picked up on next load.
     */
    public function invalidateCompiledCache(): void
    {
        $compiledPath = $this->getCompiledConfigPath();
        if ($compiledPath !== null && file_exists($compiledPath)) {
            @unlink($compiledPath);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($compiledPath, true);
            }
        }
    }

    public function setConfigsPath(string $configsPath): void
    {
        $this->configsPath = rtrim($configsPath, DIRECTORY_SEPARATOR);

        $this->invalidateCompiledCache();

        $this->loadConfigurations();
    }

    public function toArray(): array
    {
        return $this->configuration->all();
    }

    protected function loadConfigurations(): void
    {
        $this->configuration = new Config([]);

        foreach ($this->getConfigFiles() as $configName => $configFile) {
            $this->configuration->set($configName, $configFile);
        }
    }

    protected function getConfigFiles(): array
    {
        $compiledPath = $this->getCompiledConfigPath();

        if ($compiledPath !== null && file_exists($compiledPath)) {
            $compiled = require $compiledPath;
            if (is_array($compiled)) {
                return $compiled;
            }
        }

        $configFiles = $this->scanConfigFiles();

        if ($compiledPath !== null) {
            $this->writeCompiledConfig($compiledPath, $configFiles);
        }

        return $configFiles;
    }

    protected function scanConfigFiles(): array
    {
        $finder = finder();
        $finder->files()->in($this->configsPath)->name('*.php');

        $configFiles = [];
        foreach ($finder as $file) {
            $configName = $file->getBasename('.php');
            $configFiles[$configName] = require $file->getRealPath();
        }

        return $configFiles;
    }

    private function getCompiledConfigPath(): ?string
    {
        if (!defined('BASE_PATH')) {
            return null;
        }

        $appConfig = $this->configsPath . DIRECTORY_SEPARATOR . 'app.php';
        if (file_exists($appConfig)) {
            $cfg = @include $appConfig;
            if (is_array($cfg) && !empty($cfg['debug'])) {
                return null;
            }
        }

        return (
            BASE_PATH
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'cache'
            . DIRECTORY_SEPARATOR
            . 'config_compiled.php'
        );
    }

    private function writeCompiledConfig(string $path, array $configFiles): void
    {
        try {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0o755, true);
            }

            $tmp = $path . '.' . uniqid('cfg', true) . '.tmp';
            $content = '<?php return ' . var_export($configFiles, true) . ';';
            if (@file_put_contents($tmp, $content, LOCK_EX) !== false) {
                @rename($tmp, $path);
            }
        } catch (Throwable $e) {
        }
    }
}
