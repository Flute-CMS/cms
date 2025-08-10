<?php

namespace Flute\Admin\Packages\Dashboard\Screens;

use Flute\Admin\Packages\Dashboard\Services\DashboardService;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;

class DashboardScreen extends Screen
{
    /**
     * Screen title
     *
     * @var string
     */
    protected ?string $name = 'admin-dashboard.labels.home';

    /**
     * Screen description
     *
     * @var string
     */
    protected ?string $description = 'admin-dashboard.descriptions.key_metrics';

    public $vars;

    protected $dashboardService;

    /**
     * Mount the screen
     *
     * @return void
     */
    public function mount(): void
    {
        breadcrumb()->add(__('admin-dashboard.labels.home'));

        $this->dashboardService = app(DashboardService::class);

        $this->vars = $this->dashboardService->getVars();
    }

    /**
     * Get the layout elements
     *
     * @return array
     */
    public function layout(): array
    {
        $layouts = [];

        $basePathResolved = realpath(BASE_PATH) ?: rtrim(BASE_PATH, DIRECTORY_SEPARATOR);
        $modulesFullPath = rtrim($basePathResolved . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Modules', DIRECTORY_SEPARATOR);

        if (!$this->isIoncubeConfiguredForModules($modulesFullPath)) {
            $iniLine = 'ioncube.loader.encoded_paths="' . $modulesFullPath . '"';
            $layouts[] = LayoutFactory::view('admin-dashboard::components.ioncube-notice', [
                'modules_full_path' => $modulesFullPath,
                'ini_line' => $iniLine,
            ]);
        }

        $layouts[] = LayoutFactory::tabs($this->dashboardService->getTabs()->all())
            ->slug('dashboard_tabs')
            ->pills()
            ->morph(false)
            ->lazyload(true);

        return $layouts;
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

        $normalizedTarget = rtrim(str_replace('\\', '/', $modulesFullPath), '/');

        foreach ($paths as $path) {
            $normalized = rtrim(str_replace('\\', '/', $path), '/');
            if ($normalized === $normalizedTarget) {
                return true;
            }
        }

        return false;
    }
}
