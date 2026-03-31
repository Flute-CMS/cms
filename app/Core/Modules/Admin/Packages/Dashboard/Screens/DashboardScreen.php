<?php

namespace Flute\Admin\Packages\Dashboard\Screens;

use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Admin\Packages\Dashboard\Services\AttentionService;
use Flute\Admin\Packages\Dashboard\Services\DashboardService;
use Flute\Admin\Packages\Dashboard\Services\SetupChecklistService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Services\IoncubeService;
use Throwable;

class DashboardScreen extends Screen
{
    /**
     * Screen title
     */
    public ?string $name = 'admin-dashboard.labels.home';

    /**
     * Screen description
     */
    public ?string $description = 'admin-dashboard.descriptions.key_metrics';

    public $vars;

    public ?array $ioncubeDownload = null;

    public ?string $ioncubeDownloadError = null;

    protected $dashboardService;

    /**
     * Mount the screen
     */
    public function mount(): void
    {
        breadcrumb()->add(__('admin-dashboard.labels.home'));

        $this->dashboardService = app(DashboardService::class);

        $this->vars = $this->dashboardService->getVars();
    }

    public function commandBar(): array
    {
        $buttons = [];

        if (cookie()->get('admin_onboarding_done')) {
            $buttons[] = Button::make(__('admin-dashboard.onboarding.restart'))
                ->type(Color::OUTLINE_SECONDARY)
                ->icon('ph.bold.compass-bold')
                ->method('restartOnboarding');
        }

        return $buttons;
    }

    public function restartOnboarding(): void
    {
        cookie()->remove('admin_onboarding_done');

        $this->flashMessage(__('admin-dashboard.onboarding.restart_success'), 'success');
    }

    /**
     * Get the layout elements
     */
    public function layout(): array
    {
        $layouts = [];

        $basePathResolved = realpath(BASE_PATH) ?: rtrim(BASE_PATH, DIRECTORY_SEPARATOR);
        $modulesFullPath = rtrim(
            $basePathResolved . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Modules',
            DIRECTORY_SEPARATOR,
        );

        if (user()->can('admin.boss')) {
            /** @var IoncubeService $ioncube */
            $ioncube = app(IoncubeService::class);

            if (!$ioncube->isLoaded()) {
                $layouts[] = LayoutFactory::view('admin-dashboard::components.ioncube-missing', [
                    'ioncube' => $ioncube,
                    'download' => $this->ioncubeDownload,
                    'download_error' => $this->ioncubeDownloadError,
                ]);
            } elseif (!$this->isIoncubeConfiguredForModules($modulesFullPath)) {
                $iniLine = 'ioncube.loader.encoded_paths="' . $modulesFullPath . '"';
                $layouts[] = LayoutFactory::view('admin-dashboard::components.ioncube-notice', [
                    'modules_full_path' => $modulesFullPath,
                    'ini_line' => $iniLine,
                ]);
            }
        }

        $attention = app(AttentionService::class);
        $checklist = app(SetupChecklistService::class);

        if ($attention->hasItems() || !$checklist->isAllDone()) {
            $layouts[] = LayoutFactory::view('admin-dashboard::components.dashboard-notices', [
                'attention' => $attention,
                'attentionItems' => $attention->getItems(),
                'checklist' => $checklist,
                'checklistItems' => $checklist->getItems(),
            ]);
        }

        $layouts[] = LayoutFactory::tabs($this->dashboardService->getTabs()->all())
            ->slug('dashboard_tabs')
            ->pills()
            ->morph(false)
            ->lazyload(true);

        return $layouts;
    }

    public function downloadIoncubeLoaders(): void
    {
        $this->ioncubeDownload = null;
        $this->ioncubeDownloadError = null;

        /** @var IoncubeService $ioncube */
        $ioncube = app(IoncubeService::class);

        try {
            $targetDir = storage_path('app/ioncube');
            $this->ioncubeDownload = $ioncube->downloadLoaders($targetDir);

            $this->flashMessage(__('admin-dashboard.ioncube.download_success'), 'success');
        } catch (Throwable $e) {
            $this->ioncubeDownloadError = $e->getMessage();
            logs()->error($e);
            $this->flashMessage(__('admin-dashboard.ioncube.download_failed') . ': ' . $e->getMessage(), 'error');
        }
    }

    protected function isIoncubeConfiguredForModules(string $modulesFullPath): bool
    {
        if (!extension_loaded('ionCube Loader')) {
            return false;
        }

        $encodedPaths = ini_get('ioncube.loader.encoded_paths');

        if (!$encodedPaths || !is_string($encodedPaths)) {
            return false;
        }

        $paths = array_filter(array_map('trim', explode(PATH_SEPARATOR, $encodedPaths)));

        foreach ($paths as $path) {
            if (AboutSystemHelper::ioncubeEncodedPathMatchesModulesDir($path, $modulesFullPath)) {
                return true;
            }
        }

        return false;
    }
}
