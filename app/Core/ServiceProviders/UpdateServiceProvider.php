<?php

namespace Flute\Core\ServiceProviders;

use DI\Container;
use Flute\Core\Support\AbstractServiceProvider;
use Flute\Core\Update\Services\UpdateService;
use Throwable;

class UpdateServiceProvider extends AbstractServiceProvider
{
    public function register(\DI\ContainerBuilder $containerBuilder): void
    {
        // No special bindings required; UpdateService is resolved via container
    }

    public function boot(Container $container): void
    {
        if (!config('app.cron_mode')) {
            return;
        }

        if (!config('app.auto_update')) {
            return;
        }

        scheduler()->call(static function () use ($container) {
            try {
                /** @var UpdateService $updateService */
                $updateService = $container->get(UpdateService::class);

                // Respect saved channel
                $savedChannel = config('app.update_channel', 'stable');
                $updateService->setChannel($savedChannel);

                $updateService->clearCache();
                $updates = $updateService->getAvailableUpdates(true);

                $successful = 0;
                $total = 0;

                // CMS
                if (!empty($updates['cms'])) {
                    $total++;

                    try {
                        $packageFile = $updateService->downloadUpdate('cms', null, $updates['cms']['version'] ?? null);
                        if ($packageFile && file_exists($packageFile)) {
                            $ok = (new \Flute\Core\Update\Updaters\CmsUpdater())->update(['package_file' => $packageFile]);
                            if ($ok) {
                                $successful++;
                            }
                            if (file_exists($packageFile)) {
                                @unlink($packageFile);
                            }
                        }
                    } catch (Throwable $e) {
                        logs('cron')->error('Auto-update CMS failed: ' . $e->getMessage());
                    }
                }

                // Modules
                if (!empty($updates['modules'])) {
                    foreach ($updates['modules'] as $moduleId => $moduleUpdate) {
                        $total++;

                        try {
                            $packageFile = $updateService->downloadUpdate('module', $moduleId, $moduleUpdate['version'] ?? null);
                            if ($packageFile && file_exists($packageFile)) {
                                $module = app(\Flute\Core\ModulesManager\ModuleManager::class)->getModule($moduleId);
                                if ($module) {
                                    $ok = (new \Flute\Core\Update\Updaters\ModuleUpdater($module))->update(['package_file' => $packageFile]);
                                    if ($ok) {
                                        $successful++;
                                    }
                                }
                                if (file_exists($packageFile)) {
                                    @unlink($packageFile);
                                }
                            }
                        } catch (Throwable $e) {
                            logs('cron')->error("Auto-update module {$moduleId} failed: " . $e->getMessage());
                        }
                    }
                }

                // Themes
                if (!empty($updates['themes'])) {
                    foreach ($updates['themes'] as $themeId => $themeUpdate) {
                        $total++;

                        try {
                            $packageFile = $updateService->downloadUpdate('theme', $themeId, $themeUpdate['version'] ?? null);
                            if ($packageFile && file_exists($packageFile)) {
                                $themeManager = app(\Flute\Core\Theme\ThemeManager::class);
                                $themeData = $themeManager->getThemeData($themeId);
                                $theme = \Flute\Core\Database\Entities\Theme::findOne(['key' => $themeId]);
                                $ok = (new \Flute\Core\Update\Updaters\ThemeUpdater($theme, $themeData))->update(['package_file' => $packageFile]);
                                if ($ok) {
                                    $successful++;
                                }
                                if (file_exists($packageFile)) {
                                    @unlink($packageFile);
                                }
                            }
                        } catch (Throwable $e) {
                            logs('cron')->error("Auto-update theme {$themeId} failed: " . $e->getMessage());
                        }
                    }
                }

                try {
                    $updateService->clearCache();
                    app(\Flute\Core\ModulesManager\ModuleManager::class)->clearCache();

                    if (function_exists('opcache_reset')) {
                        @opcache_reset();
                    }
                } catch (Throwable $e) {
                    logs('cron')->error('Auto-update post-clean failed: ' . $e->getMessage());
                }

                logs('cron')->info("Auto-update finished: {$successful}/{$total}");
            } catch (Throwable $e) {
                logs('cron')->error('Auto-update job failed: ' . $e->getMessage());
            }
        })->daily();
    }
}
