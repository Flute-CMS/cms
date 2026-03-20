<?php

namespace Flute\Admin\Packages\NotificationTemplates\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\Filters;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\NotificationTemplate;
use Flute\Core\Modules\Notifications\Services\NotificationTemplateService;
use Throwable;

class NotificationTemplateListScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.notifications';

    public $templates;

    public $groupedTemplates;

    public array $metrics = [];

    public function mount(): void
    {
        $this->name = __('admin-notifications.title.list');
        $this->description = __('admin-notifications.title.description');

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-notifications.title.list'));

        $this->loadTemplates();
        $this->calculateMetrics();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::metrics([
                __('admin-notifications.metrics.total_templates') => 'metrics.total',
                __('admin-notifications.metrics.active_templates') => 'metrics.active',
                __('admin-notifications.metrics.modules') => 'metrics.modules',
            ])->setIcons([
                __('admin-notifications.metrics.total_templates') => 'bell-ringing',
                __('admin-notifications.metrics.active_templates') => 'check-circle',
                __('admin-notifications.metrics.modules') => 'squares-four',
            ]),

            $this->getFilters(),

            LayoutFactory::table('templates', [
                TD::selection('id'),

                TD::make('status')
                    ->title(__('admin-notifications.fields.status'))
                    ->width('100px')
                    ->alignCenter()
                    ->render(static fn(NotificationTemplate $template) => view('admin-notifications::cells.status', [
                        'model' => $template,
                    ])),

                TD::make('key')
                    ->title(__('admin-notifications.fields.key'))
                    ->render(static fn(NotificationTemplate $template) => view('admin-notifications::cells.key', [
                        'model' => $template,
                    ]))
                    ->minWidth('200px')
                    ->cantHide(),

                TD::make('title')
                    ->title(__('admin-notifications.fields.title'))
                    ->render(static fn(NotificationTemplate $template) => view('admin-notifications::cells.title', [
                        'model' => $template,
                    ]))
                    ->minWidth('250px'),

                TD::make('channels')
                    ->title(__('admin-notifications.fields.channels'))
                    ->render(static fn(NotificationTemplate $template) => view('admin-notifications::cells.channels', [
                        'model' => $template,
                    ]))
                    ->width('150px')
                    ->alignCenter(),

                TD::make('actions')
                    ->class('actions-col')
                    ->title(__('def.actions'))
                    ->width('100px')
                    ->alignCenter()
                    ->render(static fn(NotificationTemplate $template) => DropDown::make()
                        ->icon('ph.regular.dots-three-outline-vertical')
                        ->list(array_filter([
                            DropDownItem::make(__('def.edit'))
                                ->redirect(url('/admin/notification-templates/' . $template->id . '/edit'))
                                ->icon('ph.bold.pencil-bold')
                                ->type(Color::OUTLINE_PRIMARY)
                                ->size('small')
                                ->fullWidth(),

                            DropDownItem::make(
                                $template->is_enabled
                                    ? __('admin-notifications.disable')
                                    : __('admin-notifications.enable'),
                            )
                                ->method('toggle', ['id' => $template->id])
                                ->icon($template->is_enabled ? 'ph.bold.toggle-right-bold' : 'ph.bold.toggle-left-bold')
                                ->type($template->is_enabled ? Color::OUTLINE_WARNING : Color::OUTLINE_SUCCESS)
                                ->size('small')
                                ->fullWidth(),

                            $template->is_customized
                                ? DropDownItem::make(__('admin-notifications.reset'))
                                    ->confirm(__('admin-notifications.confirms.reset'))
                                    ->method('reset', ['id' => $template->id])
                                    ->icon('ph.bold.arrow-counter-clockwise-bold')
                                    ->type(Color::OUTLINE_WARNING)
                                    ->size('small')
                                    ->fullWidth()
                                : null,

                            DropDownItem::make(__('def.delete'))
                                ->confirm(__('admin-notifications.confirms.delete'))
                                ->method('delete', ['id' => $template->id])
                                ->icon('ph.bold.trash-bold')
                                ->type(Color::OUTLINE_DANGER)
                                ->size('small')
                                ->fullWidth(),
                        ]))),
            ])
                ->searchable(['key', 'title', 'module'])
                ->bulkActions([
                    Button::make(__('admin-notifications.bulk.enable'))
                        ->icon('ph.bold.toggle-right-bold')
                        ->type(Color::OUTLINE_SUCCESS)
                        ->method('bulkEnable'),

                    Button::make(__('admin-notifications.bulk.disable'))
                        ->icon('ph.bold.toggle-left-bold')
                        ->type(Color::OUTLINE_WARNING)
                        ->method('bulkDisable'),

                    Button::make(__('admin.bulk.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('admin.confirms.delete_selected'))
                        ->method('bulkDelete'),
                ]),
        ];
    }

    public function toggle(): void
    {
        $id = (int) request()->input('id');
        $template = NotificationTemplate::findByPK($id);

        if (!$template) {
            $this->flashMessage(__('admin-notifications.errors.not_found'), 'error');

            return;
        }

        try {
            app(NotificationTemplateService::class)->toggle($template);
            $this->flashMessage(__('admin-notifications.messages.toggled'));
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->loadTemplates();
        $this->calculateMetrics();
    }

    public function reset(): void
    {
        $id = (int) request()->input('id');
        $template = NotificationTemplate::findByPK($id);

        if (!$template) {
            $this->flashMessage(__('admin-notifications.errors.not_found'), 'error');

            return;
        }

        try {
            app(NotificationTemplateService::class)->reset($template);
            $this->flashMessage(__('admin-notifications.messages.reset'));
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->loadTemplates();
    }

    public function delete(): void
    {
        $id = (int) request()->input('id');
        $template = NotificationTemplate::findByPK($id);

        if (!$template) {
            $this->flashMessage(__('admin-notifications.errors.not_found'), 'error');

            return;
        }

        try {
            app(NotificationTemplateService::class)->delete($template);
            $this->flashMessage(__('admin-notifications.messages.deleted'));
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->loadTemplates();
        $this->calculateMetrics();
    }

    public function bulkEnable(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }

        $count = 0;

        foreach ($ids as $id) {
            $template = NotificationTemplate::findByPK((int) $id);
            if ($template && !$template->is_enabled) {
                try {
                    $template->is_enabled = true;
                    $template->save();
                    $count++;
                } catch (Throwable) {
                }
            }
        }

        $this->loadTemplates();
        $this->calculateMetrics();
        $this->flashMessage(__('admin-notifications.messages.bulk_enabled', ['count' => $count]));
    }

    public function bulkDisable(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }

        $count = 0;

        foreach ($ids as $id) {
            $template = NotificationTemplate::findByPK((int) $id);
            if ($template && $template->is_enabled) {
                try {
                    $template->is_enabled = false;
                    $template->save();
                    $count++;
                } catch (Throwable) {
                }
            }
        }

        $this->loadTemplates();
        $this->calculateMetrics();
        $this->flashMessage(__('admin-notifications.messages.bulk_disabled', ['count' => $count]));
    }

    public function bulkDelete(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }

        $service = app(NotificationTemplateService::class);
        $count = 0;

        foreach ($ids as $id) {
            $template = NotificationTemplate::findByPK((int) $id);
            if ($template) {
                try {
                    $service->delete($template);
                    $count++;
                } catch (Throwable) {
                }
            }
        }

        $this->loadTemplates();
        $this->calculateMetrics();
        $this->flashMessage(__('admin-notifications.messages.bulk_deleted', ['count' => $count]));
    }

    protected function loadTemplates(): void
    {
        $service = app(NotificationTemplateService::class);
        $this->groupedTemplates = $service->getGroupedByModule();

        $query = NotificationTemplate::query()->orderBy('module', 'ASC')->orderBy('priority', 'ASC');

        // Apply status filter
        $status = request()->input('status', '');
        if ($status === 'active') {
            $query->where('is_enabled', true);
        } elseif ($status === 'inactive') {
            $query->where('is_enabled', false);
        } elseif ($status === 'customized') {
            $query->where('is_customized', true);
        }

        // Apply module filter
        $module = request()->input('module', '');
        if ($module) {
            if ($module === 'core') {
                $query->where('module', null);
            } else {
                $query->where('module', $module);
            }
        }

        $this->templates = $query;
    }

    protected function calculateMetrics(): void
    {
        $service = app(NotificationTemplateService::class);
        $allTemplates = $service->getAll();

        $total = count($allTemplates);
        $active = 0;
        $modules = [];

        foreach ($allTemplates as $template) {
            if ($template->is_enabled) {
                $active++;
            }
            $module = $template->module ?? 'core';
            $modules[$module] = true;
        }

        $activePercent = $total > 0 ? round(( $active / $total ) * 100) : 0;

        $this->metrics = [
            'total' => [
                'value' => (string) $total,
            ],
            'active' => [
                'value' => $active . ' (' . $activePercent . '%)',
            ],
            'modules' => [
                'value' => (string) count($modules),
            ],
        ];
    }

    protected function getFilters(): Filters
    {
        $service = app(NotificationTemplateService::class);
        $modules = $service->getModules();

        $moduleOptions = ['' => __('admin-notifications.filters.all')];
        foreach ($modules as $module) {
            $moduleOptions[$module] = $module;
        }

        return Filters::make()
            ->buttonGroup(
                'status',
                __('admin-notifications.fields.enabled'),
                [
                    '' => __('admin-notifications.filters.all'),
                    'active' => __('admin-notifications.filters.active'),
                    'inactive' => __('admin-notifications.filters.inactive'),
                    'customized' => __('admin-notifications.filters.customized'),
                ],
                '',
            )
            ->select('module', __('admin-notifications.fields.module'), $moduleOptions, '')
            ->compact();
    }
}
