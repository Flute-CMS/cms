<?php

namespace Flute\Admin\Packages\Marketplace\Services\Concerns;

use Exception;
use Flute\Core\Composer\ComposerManager;
use Throwable;

trait HandlesComposerUpdate
{
    /**
     * @throws Exception
     */
    public function updateComposerDependencies(): array
    {
        $this->keepProcessAlive();

        if (empty($this->moduleFolder)) {
            return [
                'success' => true,
                'message' => 'No module folder specified for composer update',
            ];
        }

        $composerJsonPath = $this->modulesDir . '/' . $this->moduleFolder . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            return [
                'success' => true,
                'message' => 'No composer dependencies to update',
            ];
        }

        try {
            /** @var ComposerManager $composerManager */
            $composerManager = app(ComposerManager::class);
            $composerManager->install();

            clearstatcache(true, $this->modulesDir . '/' . $this->moduleFolder);
            $this->invalidateComposerAutoloadCaches();
            $modulePath = $this->modulesDir . '/' . $this->moduleFolder;
            if (is_dir($modulePath)) {
                $this->invalidatePhpOpcacheUnder($modulePath);
            }

            return [
                'success' => true,
                'message' => __('admin-marketplace.messages.composer_success'),
            ];
        } catch (Throwable $e) {
            throw new Exception(__('admin-marketplace.messages.composer_failed') . ': ' . $e->getMessage());
        }
    }
}
