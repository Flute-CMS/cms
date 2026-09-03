<?php

namespace Flute\Core\Modules\Installer\Controllers\Concerns;

use Flute\Core\Database\Entities\User;
use Flute\Core\Router\Annotations\Route;
use Flute\Core\Support\FluteRequest;
use Throwable;

trait HandlesLaunchStep
{
    #[Route('/install/6', name: 'installer.step6.save', methods: ['POST'])]
    public function saveAndLaunch(FluteRequest $request): mixed
    {
        if ($this->installerConfig->isInstalled()) {
            return response()->redirect('/');
        }

        if ($denied = $this->guardInstallerAccess($request)) {
            return $denied;
        }

        $errorMessage = null;

        try {
            $appConfig = config('app');
            $appConfig['cron_mode'] = $request->input('cron_mode') === '1';
            $appConfig['maintenance_mode'] = $request->input('maintenance_mode') === '1';
            $appConfig['tips'] = $request->input('tips') === '1';
            $appConfig['share'] = $request->input('share') === '1';
            $appConfig['flute_copyright'] = $request->input('flute_copyright') === '1';
            $appConfig['convert_to_webp'] = $request->input('convert_to_webp') === '1';
            $appConfig['csrf_enabled'] = $request->input('csrf_enabled') === '1';
            $appConfig['change_theme'] = $request->input('change_theme') === '1';
            $appConfig['is_performance'] = $request->input('is_performance') === '1';
            $appConfig['robots'] = $request->input('robots', 'index, follow');
            $appConfig['steam_api'] = $request->input('steam_api', '');
            $appConfig['default_theme'] = $request->input('default_theme', 'dark');

            config()->set('app', $appConfig);
            config()->save();

            $user = User::query()
                ->load('roles.permissions')
                ->where('verified', true)
                ->where(['roles.permissions.name' => 'admin.boss'])
                ->fetchOne();

            if (!$user) {
                return $this->renderStep(6, [
                    'errorMessage' => __('install.launch.error_no_admin'),
                ]);
            }

            $this->installerConfig->markAsInstalled();

            return $this->installerView->render([
                'stepView' => 'installer::yoyo.finish',
                'stepData' => [],
            ], 6);
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
        }

        return $this->renderStep(6, [
            'errorMessage' => $errorMessage,
        ]);
    }

    protected function getSystemCheckData(): array
    {
        return [
            'phpRequirements' => $this->systemRequirements->checkPhpRequirements(),
            'extensionRequirements' => $this->systemRequirements->checkExtensionRequirements(),
            'directoryRequirements' => $this->systemRequirements->checkDirectoryRequirements(),
            'ionCubeCheck' => $this->systemRequirements->checkIonCubeLoader(),
            'allRequirementsMet' => $this->systemRequirements->allRequirementsMet(),
        ];
    }

    protected function getLaunchData(): array
    {
        $appConfig = config('app');

        return [
            'cron_mode' => filter_var($appConfig['cron_mode'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'maintenance_mode' => filter_var($appConfig['maintenance_mode'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'tips' => filter_var($appConfig['tips'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'share' => filter_var($appConfig['share'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'flute_copyright' => filter_var($appConfig['flute_copyright'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'convert_to_webp' => filter_var($appConfig['convert_to_webp'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'csrf_enabled' => filter_var($appConfig['csrf_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'change_theme' => filter_var($appConfig['change_theme'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'is_performance' => filter_var($appConfig['is_performance'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'robots' => $appConfig['robots'] ?? 'index, follow',
            'steam_api' => $appConfig['steam_api'] ?? '',
            'default_theme' => $appConfig['default_theme'] ?? 'dark',
            'errorMessage' => null,
        ];
    }
}
