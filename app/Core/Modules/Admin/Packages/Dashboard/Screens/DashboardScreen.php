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
        return [
            LayoutFactory::tabs($this->dashboardService->getTabs()->all())
                ->slug('dashboard_tabs')
                ->pills()
                ->morph(false)
                ->lazyload(true),
        ];
    }
}
