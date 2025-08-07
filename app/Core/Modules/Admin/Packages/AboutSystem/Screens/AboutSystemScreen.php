<?php

namespace Flute\Admin\Packages\AboutSystem\Screens;

use Flute\Admin\Packages\AboutSystem\Helpers\AboutSystemHelper;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;

class AboutSystemScreen extends Screen
{
    /**
     * Screen title
     *
     * @var string
     */
    protected ?string $name = null;

    /**
     * Screen description
     *
     * @var string
     */
    protected ?string $description = null;

    /**
     * Mount the screen
     *
     * @return void
     */
    public function mount(): void
    {
        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-about-system.labels.home'));
    }

    /**
     * Query data for the screen
     *
     * @return array
     */
    public function query(): array
    {
        return [
            'systemInfo' => AboutSystemHelper::getSystemInfo(),
            'phpInfo' => AboutSystemHelper::getPhpInfo(),
            'serverInfo' => AboutSystemHelper::getServerInfo(),
            'requiredExtensions' => AboutSystemHelper::getRequiredExtensions(),
            'phpVersionValid' => AboutSystemHelper::checkPhpVersion(),
            'phpWarnings' => AboutSystemHelper::getPhpSettingWarnings(),
            'systemHealth' => AboutSystemHelper::getSystemHealth(),
            'resourceUsage' => AboutSystemHelper::getResourceUsage(),
        ];
    }

    /**
     * Get the layout elements
     *
     * @return array
     */
    public function layout(): array
    {
        return [
            LayoutFactory::view('admin-about-system::index', $this->query()),
        ];
    }
}
