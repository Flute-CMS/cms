<?php

namespace Flute\Admin\Packages\Marketplace\Screens;

use Flute\Admin\Packages\Marketplace\Services\MarketplaceService;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\ModulesManager\ModuleManager;

class MarketplaceProductScreen extends Screen
{
    protected ?string $name = 'admin-marketplace.labels.module_details';
    protected ?string $description = 'admin-marketplace.labels.module_details';

    public string $slugParam = '';
    public array $module = [];
    public array $versions = [];
    protected MarketplaceService $marketplaceService;

    public function mount(): void
    {
        breadcrumb()
            ->add(__('admin-marketplace.labels.marketplace'), url('/admin/marketplace'));

        $this->marketplaceService = app(MarketplaceService::class);

        $this->slugParam = (string) request()->input('slug');
        if (!$this->slugParam) {
            $path = request()->getPathInfo();
            $parts = explode('/', trim($path, '/'));
            $this->slugParam = end($parts) ?: '';
        }

        $this->moduleManager = app(ModuleManager::class);

        $this->loadModule();
    }

    protected function loadModule(): void
    {
        try {
            if (!$this->slugParam) {
                return;
            }

            $modules = $this->marketplaceService->getModules('', '');
            foreach ($modules as $item) {
                if (!empty($item['slug']) && $item['slug'] === $this->slugParam) {
                    $this->module = $item;

                    break;
                }
            }

            $this->module['isInstalled'] = $this->moduleManager->issetModule($this->module['name']) &&
                $this->moduleManager->getModule($this->module['name'])->status !== 'notinstalled';

            if ($this->module['isInstalled'] && isset($this->module['currentVersion'])) {
                $installedModule = $this->moduleManager->getModule($this->module['name']);
                $this->module['installedVersion'] = $installedModule->installedVersion ?? '0.0.0';
                $this->module['needsUpdate'] = version_compare(
                    $this->module['currentVersion'],
                    $this->module['installedVersion'],
                    '>'
                );
            }

            if (!empty($this->module['name'])) {
                $this->name = $this->module['name'];
            }
            $this->versions = [];
        } catch (\Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('admin-marketplace.actions.back_to_list'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/marketplace'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::view('admin-marketplace::marketplace.module-details', [
                'module' => $this->module,
                'versions' => $this->versions,
                'isInstalled' => $this->module['isInstalled'] ?? false,
                'needsUpdate' => $this->module['needsUpdate'] ?? false,
            ]),
        ];
    }
}
