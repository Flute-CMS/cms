<?php

namespace Flute\Admin\Packages\NotificationTemplates\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\TD;
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

    public function mount(): void
    {
        $this->name = __('admin-notifications.title.list');
        $this->description = __('admin-notifications.title.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-notifications.title.list'));

        $this->loadTemplates();
    }

    protected function loadTemplates(): void
    {
        $service = app(NotificationTemplateService::class);
        $this->groupedTemplates = $service->getGroupedByModule();
        $this->templates = $service->getAll();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('templates', [
                TD::selection('id'),

                TD::make('status')
                    ->title('')
                    ->width('50px')
                    ->alignCenter()
                    ->render(fn(NotificationTemplate $template) => view('admin-notifications::cells.status', ['model' => $template])),

                TD::make('key')
                    ->title(__('admin-notifications.fields.key'))
                    ->render(fn(NotificationTemplate $template) => view('admin-notifications::cells.key', ['model' => $template]))
                    ->minWidth('250px')
                    ->cantHide(),

                TD::make('title')
                    ->title(__('admin-notifications.fields.title'))
                    ->render(fn(NotificationTemplate $template) => view('admin-notifications::cells.title', ['model' => $template]))
                    ->width('300px'),

                TD::make('channels')
                    ->title(__('admin-notifications.fields.channels'))
                    ->render(fn(NotificationTemplate $template) => view('admin-notifications::cells.channels', ['model' => $template]))
                    ->width('200px'),

                TD::make('layout')
                    ->title(__('admin-notifications.fields.layout'))
                    ->render(fn(NotificationTemplate $template) => view('admin-notifications::cells.layout', ['model' => $template]))
                    ->width('100px')
                    ->alignCenter(),

                TD::make('actions')
                    ->class('actions-col')
                    ->title(__('def.actions'))
                    ->width('120px')
                    ->alignCenter()
                    ->render(
                        fn(NotificationTemplate $template) => DropDown::make()
                            ->icon('ph.regular.dots-three-outline-vertical')
                            ->list([
                                DropDownItem::make(__('def.edit'))
                                    ->redirect(url('/admin/notification-templates/' . $template->id . '/edit'))
                                    ->icon('ph.bold.pencil-bold')
                                    ->type(Color::OUTLINE_PRIMARY)
                                    ->size('small')
                                    ->fullWidth(),

                                DropDownItem::make($template->is_enabled ? __('admin-notifications.disable') : __('admin-notifications.enable'))
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
                                    ->type(Color::OUTLINE_SECONDARY)
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
                            ])
                    ),
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
    }

    public function bulkEnable(): void
    {
        $ids = request()->input('selected', []);
        if (!$ids) {
            return;
        }

        $service = app(NotificationTemplateService::class);
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
        $this->flashMessage(__('admin-notifications.messages.bulk_deleted', ['count' => $count]));
    }
}
