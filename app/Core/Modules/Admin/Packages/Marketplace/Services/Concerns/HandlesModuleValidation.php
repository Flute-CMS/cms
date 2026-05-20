<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;
use Flute\Core\App;
use Flute\Core\ModulesManager\ModuleManager;

trait HandlesModuleValidation
{
    /**
     * @throws Exception
     */
    public function validateModule(array $module): array
    {
        if (empty($this->moduleExtractPath) || !is_dir($this->moduleExtractPath)) {
            throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Распакованные файлы не найдены');
        }

        $moduleJsonPath = $this->moduleExtractPath;

        if ($this->moduleKey) {
            $moduleJsonPath .= '/' . $this->moduleKey . '/module.json';
        } else {
            $moduleJsonPath .= '/module.json';
        }

        if (!file_exists($moduleJsonPath)) {
            throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Отсутствует файл module.json');
        }

        $moduleJson = json_decode(file_get_contents($moduleJsonPath), true);

        if (empty($moduleJson)) {
            throw new Exception(__('admin-marketplace.messages.validate_failed') . ': Неверный формат module.json');
        }

        $this->moduleInfo = $moduleJson;

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['php'])) {
            $requiredPhp = $moduleJson['requires']['php'];

            if (!$this->checkPhpVersion($requiredPhp)) {
                throw new Exception(
                    __('admin-marketplace.messages.validate_failed') . ': Требуется PHP ' . $requiredPhp,
                );
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['flute'])) {
            $requiredFlute = $moduleJson['requires']['flute'];

            if (!$this->checkFluteVersion($requiredFlute)) {
                throw new Exception(
                    __('admin-marketplace.messages.validate_failed') . ': Требуется Flute ' . $requiredFlute,
                );
            }
        }

        if (!empty($moduleJson['requires']) && !empty($moduleJson['requires']['modules'])) {
            $requiredModules = $moduleJson['requires']['modules'];
            $missingModules = $this->checkModuleDependencies($requiredModules);

            if (!empty($missingModules)) {
                throw new Exception(
                    __('admin-marketplace.messages.validate_failed') . ': Отсутствуют модули: '
                        . implode(', ', $missingModules),
                );
            }
        }

        return [
            'success' => true,
            'message' => __('admin-marketplace.messages.validate_success'),
            'moduleInfo' => $moduleJson,
        ];
    }

    protected function checkPhpVersion(string $requiredVersion): bool
    {
        return version_compare(PHP_VERSION, $requiredVersion, '>=');
    }

    protected function checkFluteVersion(string $requiredVersion): bool
    {
        return version_compare(App::VERSION, $requiredVersion, '>=');
    }

    protected function checkModuleDependencies(array $requiredModules): array
    {
        /** @var ModuleManager $moduleManager */
        $moduleManager = app(ModuleManager::class);
        $missingModules = [];

        foreach ($requiredModules as $moduleKey => $moduleVersion) {
            if (!$moduleManager->issetModule($moduleKey)) {
                $missingModules[] = $moduleKey;

                continue;
            }

            $moduleInfo = $moduleManager->getModule($moduleKey);

            if ($moduleInfo->status !== ModuleManager::ACTIVE) {
                $missingModules[] = $moduleKey;

                continue;
            }

            if (!empty($moduleVersion) && version_compare($moduleInfo->version ?? '1.0.0', $moduleVersion, '<')) {
                $missingModules[] = "{$moduleKey} (требуется версия {$moduleVersion})";
            }
        }

        return $missingModules;
    }
}
