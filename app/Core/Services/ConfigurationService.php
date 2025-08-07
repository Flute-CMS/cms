<?php

namespace Flute\Core\Services;

use Noodlehaus\Config;

class ConfigurationService
{
    protected string $configsPath;
    protected Config $configuration;

    public function __construct(string $configsPath = '/config')
    {
        $configsPath = (file_exists(BASE_PATH . 'config-dev')) ? BASE_PATH . 'config-dev' : BASE_PATH . 'config';

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
        } catch (\Throwable $e) {
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
                throw new \RuntimeException("Failed to write configuration to {$filePath}");
            }

            $writtenFiles[] = $filePath;
        }

        if (function_exists('opcache_invalidate')) {
            foreach ($writtenFiles as $file) {
                opcache_invalidate($file, /* force */ true);
            }
        }

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

    public function setConfigsPath(string $configsPath): void
    {
        $this->configsPath = rtrim($configsPath, DIRECTORY_SEPARATOR);
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
        $finder = finder();
        $finder->files()->in($this->configsPath)->name('*.php');

        $configFiles = [];
        foreach ($finder as $file) {
            $configName = $file->getBasename('.php');
            $configFiles[$configName] = require $file->getRealPath();
        }

        return $configFiles;
    }
}
