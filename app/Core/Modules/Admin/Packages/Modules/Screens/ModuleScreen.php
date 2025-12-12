<?php

namespace Flute\Admin\Packages\Modules\Screens;

use Exception;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\ModulesManager\ModuleActions;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\ModulesManager\ModuleManager;

class ModuleScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.modules';

    public $modules;

    public $key;

    protected ModuleManager $moduleManager;

    public function mount(): void
    {
        $this->moduleManager = app(ModuleManager::class);
        $this->name = __('admin-modules.title');
        $this->description = __('admin-modules.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-modules.title'));

        $this->loadJS('app/Core/Modules/Admin/Packages/Modules/Resources/assets/js/modules.js');
        $this->loadModules();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::view('admin-modules::dropzone'),

            LayoutFactory::table('modules', [
                TD::selection('key'),
                TD::make('name', __('admin-modules.table.name'))
                    ->render(static fn (ModuleInformation $module) => view('admin-modules::cells.name', compact('module')))
                    ->minWidth('200px'),

                TD::make('version', __('admin-modules.table.version'))
                    ->render(static fn (ModuleInformation $module) => view('admin-modules::cells.version', compact('module')))
                    ->minWidth('150px'),

                TD::make('status', __('admin-modules.table.status'))
                    ->render(static function (ModuleInformation $module) {
                        switch ($module->status) {
                            case ModuleManager::ACTIVE:
                                return '<span class="badge success">'.__('admin-modules.status.active').'</span>';
                            case ModuleManager::DISABLED:
                                return '<span class="badge warning">'.__('admin-modules.status.disabled').'</span>';
                            case ModuleManager::NOTINSTALLED:
                                return '<span class="badge error">'.__('admin-modules.status.not_installed').'</span>';
                            default:
                                return '<span class="badge dark">'.__('admin-modules.status.unknown').'</span>';
                        }
                    })
                    ->minWidth('100px'),

                TD::make('actions', __('admin-modules.table.actions'))
                    ->width('250px')
                    ->alignCenter()
                    ->render(static function (ModuleInformation $module) {
                        $actions = [];

                        if ($module->status !== ModuleManager::NOTINSTALLED && $module->installedVersion !== $module->version) {
                            $actions[] = DropDownItem::make(__('admin-modules.actions.update'))
                                ->method('updateModule', ['key' => $module->key])
                                ->icon('ph.bold.rocket-launch-bold')
                                ->type(Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth();
                        }

                        if ($module->status === ModuleManager::NOTINSTALLED) {
                            $actions[] = DropDownItem::make(__('admin-modules.actions.install'))
                                ->method('installModule', ['key' => $module->key])
                                ->icon('ph.bold.download-simple-bold')
                                ->confirm(__('admin-modules.confirmations.install'), 'info')
                                ->type(Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth();
                        } elseif ($module->status === ModuleManager::DISABLED) {
                            $actions[] = DropDownItem::make(__('admin-modules.actions.activate'))
                                ->method('activateModule', ['key' => $module->key])
                                ->icon('ph.bold.play-bold')
                                ->type(Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth();
                        } elseif ($module->status === ModuleManager::ACTIVE) {
                            $actions[] = DropDownItem::make(__('admin-modules.actions.disable'))
                                ->method('disableModule', ['key' => $module->key])
                                ->icon('ph.bold.pause-bold')
                                ->type(Color::OUTLINE_WARNING)
                                ->size('small')
                                ->fullWidth();
                        }

                        $actions[] = DropDownItem::make(__('admin-modules.actions.delete'))
                            ->confirm(__('admin-modules.confirmations.delete'))
                            ->method('uninstallModule', ['key' => $module->key])
                            ->icon('ph.bold.trash-bold')
                            ->type(Color::OUTLINE_DANGER)
                            ->size('small')
                            ->fullWidth();

                        $actions[] = DropDownItem::make(__('admin-modules.actions.details'))
                            ->modal('moduleDetailsModal', ['key' => $module->key])
                            ->icon('ph.bold.info-bold')
                            ->type(Color::OUTLINE_PRIMARY)
                            ->size('small')
                            ->fullWidth();

                        return DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list($actions);
                    }),
            ])
                ->searchable(['key', 'name'])
                ->bulkActions([
                    Button::make(__('admin.bulk.enable_selected'))
                        ->icon('ph.bold.play-bold')
                        ->type(Color::OUTLINE_SUCCESS)
                        ->method('bulkActivateModules'),

                    Button::make(__('admin.bulk.disable_selected'))
                        ->icon('ph.bold.pause-bold')
                        ->type(Color::OUTLINE_WARNING)
                        ->method('bulkDisableModules'),

                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkUninstallModules'),
                ])
                ->commands([
                    Button::make(__('admin-modules.actions.refresh_list'))
                        ->icon('ph.regular.arrows-counter-clockwise')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->size('small')
                        ->method('refreshModules'),
                ]),
        ];
    }

    /**
     * Модальное окно для отображения детальной информации о модуле
     */
    public function moduleDetailsModal(Repository $parameters)
    {
        $module = $this->moduleManager->getModule($parameters->get('key'));
        if (!$module) {
            $this->flashMessage(__('admin-modules.messages.module_not_found'), 'error');

            return;
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->value(__($module->name))
                    ->readOnly()
            )
                ->label(__('admin-modules.modal.module_name')),

            LayoutFactory::field(
                Input::make('version')
                    ->type('text')
                    ->value($module->version)
                    ->readOnly()
            )
                ->label(__('admin-modules.modal.module_version')),

            LayoutFactory::field(
                TextArea::make('description')
                    ->value(__($module->description))
                    ->readOnly(true)
            )
                ->label(__('admin-modules.modal.module_description')),

            LayoutFactory::field(
                Input::make('authors')
                    ->type('text')
                    ->value(implode(', ', $module->authors))
                    ->readOnly()
            )
                ->label(__('admin-modules.modal.module_authors')),

            $module->url ? LayoutFactory::field(
                Input::make('url')
                    ->type('url')
                    ->value($module->url)
                    ->readOnly()
            )
                ->label(__('admin-modules.modal.module_url'))
            : null,
        ])
            ->title(__('admin-modules.modal.details_title', ['name' => __($module->name)]))
            ->withoutApplyButton()
            ->right();
    }

    /**
     * Обновление списка модулей
     */
    public function refreshModules()
    {
        $this->moduleManager->clearCache();
        $this->loadModules(true);
        $this->modules = $this->moduleManager->getModules()->sortBy('status', SORT_STRING, true);
        $this->flashMessage(__('admin-modules.messages.list_updated'), 'success');
    }

    /**
     * Установка модуля
     */
    public function installModule()
    {
        $module = $this->moduleManager->getModule($this->key);
        if (!$module) {
            $this->flashMessage(__('admin-modules.messages.module_not_found'), 'error');

            return;
        }

        try {
            app(ModuleActions::class)->installModule($module, $this->moduleManager);
            $this->flashMessage(__('admin-modules.messages.installed', ['name' => __($module->name)]), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-modules.messages.install_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadModules(true);
    }

    /**
     * Активация модуля
     */
    public function activateModule()
    {
        $module = $this->moduleManager->getModule($this->key);
        if (!$module) {
            $this->flashMessage(__('admin-modules.messages.module_not_found'), 'error');

            return;
        }

        try {
            app(ModuleActions::class)->activateModule($module, $this->moduleManager);
            $this->flashMessage(__('admin-modules.messages.activated', ['name' => __($module->name)]), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-modules.messages.activation_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadModules(true);
    }

    /**
     * Отключение модуля
     */
    public function disableModule()
    {
        $module = $this->moduleManager->getModule($this->key);
        if (!$module) {
            $this->flashMessage(__('admin-modules.messages.module_not_found'), 'error');

            return;
        }

        try {
            app(ModuleActions::class)->disableModule($module, $this->moduleManager);
            $this->flashMessage(__('admin-modules.messages.disabled', ['name' => __($module->name)]), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-modules.messages.disable_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadModules(true);
    }

    /**
     * Обновление модуля
     */
    public function updateModule()
    {
        $module = $this->moduleManager->getModule($this->key);
        if (!$module) {
            $this->flashMessage(__('admin-modules.messages.module_not_found'), 'error');

            return;
        }

        try {
            app(ModuleActions::class)->updateModule($module, $this->moduleManager);
            $this->flashMessage(__('admin-modules.messages.updated', ['name' => __($module->name)]), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-modules.messages.update_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadModules(true);
    }

    /**
     * Удаление модуля
     */
    public function uninstallModule()
    {
        $module = $this->moduleManager->getModule($this->key);
        if (!$module) {
            $this->flashMessage(__('admin-modules.messages.module_not_found'), 'error');

            return;
        }

        try {
            app(ModuleActions::class)->uninstallModule($module, $this->moduleManager);
            $this->flashMessage(__('admin-modules.messages.uninstalled', ['name' => __($module->name)]), 'success');
            $this->triggerSidebarRefresh();
        } catch (Exception $e) {
            $this->flashMessage(__('admin-modules.messages.uninstall_error', ['message' => $e->getMessage()]), 'error');
        }

        $this->loadModules(true);
    }

    public function bulkActivateModules(): void
    {
        $keys = request()->input('selected', []);
        if (!$keys) {
            return;
        }
        foreach ($keys as $key) {
            $module = $this->moduleManager->getModule($key);
            if (!$module) {
                continue;
            }

            try {
                app(ModuleActions::class)->activateModule($module, $this->moduleManager);
            } catch (Exception $e) {
            }
        }
        $this->loadModules(true);
        $this->flashMessage(__('admin-modules.messages.activated', ['name' => '']), 'success');
        $this->triggerSidebarRefresh();
    }

    public function bulkDisableModules(): void
    {
        $keys = request()->input('selected', []);
        if (!$keys) {
            return;
        }
        foreach ($keys as $key) {
            $module = $this->moduleManager->getModule($key);
            if (!$module) {
                continue;
            }

            try {
                app(ModuleActions::class)->disableModule($module, $this->moduleManager);
            } catch (Exception $e) {
            }
        }
        $this->loadModules(true);
        $this->flashMessage(__('admin-modules.messages.disabled', ['name' => '']), 'success');
        $this->triggerSidebarRefresh();
    }

    public function bulkUninstallModules(): void
    {
        $keys = request()->input('selected', []);
        if (!$keys) {
            return;
        }
        foreach ($keys as $key) {
            $module = $this->moduleManager->getModule($key);
            if (!$module) {
                continue;
            }

            try {
                app(ModuleActions::class)->uninstallModule($module, $this->moduleManager);
            } catch (Exception $e) {
            }
        }
        $this->loadModules(true);
        $this->flashMessage(__('admin-modules.messages.uninstalled', ['name' => '']), 'success');
        $this->triggerSidebarRefresh();
    }

    protected function triggerSidebarRefresh(): void
    {
        $this->dispatchBrowserEvent('sidebar-refresh');
    }

    protected function loadModules(bool $refresh = false): void
    {
        if ($refresh) {
            $this->moduleManager->refreshModules();
        }

        $this->modules = $this->moduleManager->getModules()->sortBy('status', SORT_STRING, true);
    }
}
