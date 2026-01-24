<?php

namespace Flute\Admin\Packages\NotificationTemplates\Screens;

use Exception;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\NotificationTemplate;
use Flute\Core\Modules\Notifications\Services\NotificationTemplateService;
use Throwable;

class NotificationTemplateEditScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.notifications';

    public ?NotificationTemplate $template = null;

    public ?int $templateId = null;

    public array $layoutTypes = [];

    public array $channelOptions = [];

    public function mount(): void
    {
        $this->templateId = (int) request()->input('id');

        $service = app(NotificationTemplateService::class);
        $this->layoutTypes = $service->getLayoutTypes();
        $this->channelOptions = $service->getChannels();

        if ($this->templateId) {
            $this->template = NotificationTemplate::findByPK($this->templateId);

            if (!$this->template) {
                $this->flashMessage(__('admin-notifications.errors.not_found'), 'error');
                $this->redirectTo('/admin/notification-templates', 300);

                return;
            }

            $this->name = __('admin-notifications.title.edit') . ': ' . $this->template->key;
            $this->description = __('admin-notifications.title.edit_description');
        }

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('admin-notifications.title.list'), url('/admin/notification-templates'))
            ->add($this->template ? $this->template->key : __('admin-notifications.title.edit'));
    }

    public function commandBar(): array
    {
        $buttons = [
            Button::make(__('def.cancel'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/notification-templates'),
        ];

        if ($this->template?->is_customized) {
            $buttons[] = Button::make(__('admin-notifications.reset'))
                ->type(Color::OUTLINE_SECONDARY)
                ->icon('ph.bold.arrow-counter-clockwise-bold')
                ->confirm(__('admin-notifications.confirms.reset'))
                ->method('resetTemplate');
        }

        $buttons[] = Button::make(__('def.save'))
            ->type(Color::PRIMARY)
            ->icon('ph.bold.floppy-disk-bold')
            ->method('saveTemplate');

        return $buttons;
    }

    public function layout(): array
    {
        if (!$this->template) {
            return [];
        }

        $tabs = [];

        $tabs[] = Tab::make(__('admin-notifications.tabs.content'))
            ->icon('ph.bold.text-t-bold')
            ->layouts([$this->contentLayout()])
            ->active(true);

        $tabs[] = Tab::make(__('admin-notifications.tabs.appearance'))
            ->icon('ph.bold.paint-brush-bold')
            ->layouts([$this->appearanceLayout()]);

        $tabs[] = Tab::make(__('admin-notifications.tabs.channels'))
            ->icon('ph.bold.broadcast-bold')
            ->layouts([$this->channelsLayout()]);

        $tabs[] = Tab::make(__('admin-notifications.tabs.variables'))
            ->icon('ph.bold.code-bold')
            ->layouts([$this->variablesLayout()])
            ->badge(count($this->template->variables ?? []));

        $tabs[] = Tab::make(__('admin-notifications.tabs.components'))
            ->icon('ph.bold.squares-four-bold')
            ->layouts([$this->componentsLayout()])
            ->badge(count($this->template->components ?? []));

        return [
            LayoutFactory::tabs($tabs)
                ->slug('notification-edit')
                ->pills(),
        ];
    }

    public function saveTemplate(): void
    {
        if (!$this->template) {
            $this->flashMessage(__('admin-notifications.errors.not_found'), 'error');

            return;
        }

        $data = request()->input();

        $validation = $this->validate([
            'title' => ['required', 'string', 'max-str-len:255'],
            'content' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max-str-len:100'],
            'layout' => ['nullable', 'string', 'max-str-len:50'],
            'priority' => ['nullable', 'integer'],
            'components_json' => ['nullable', 'string'],
        ], $data);

        if (!$validation) {
            return;
        }

        // Parse components JSON
        $components = null;
        if (!empty($data['components_json']) && $data['components_json'] !== '[]') {
            $components = json_decode($data['components_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->inputError('components_json', __('admin-notifications.errors.invalid_json'));

                return;
            }
        }

        // Parse channels
        $channels = [];
        if (isset($data['channels']) && is_array($data['channels'])) {
            foreach ($data['channels'] as $channel => $value) {
                if ($value === 'on' || $value === true || $value === '1') {
                    $channels[] = $channel;
                }
            }
        }

        if (empty($channels)) {
            $channels = ['inapp'];
        }

        try {
            $service = app(NotificationTemplateService::class);
            $service->update($this->template, [
                'title' => $data['title'],
                'content' => $data['content'],
                'icon' => $data['icon'] ?: null,
                'layout' => $data['layout'] ?? 'standard',
                'priority' => (int) ($data['priority'] ?? 100),
                'components' => $components,
                'channels' => $channels,
                'is_enabled' => isset($data['is_enabled']) && ($data['is_enabled'] === 'on' || $data['is_enabled'] === true),
            ]);

            $this->flashMessage(__('admin-notifications.messages.saved'));
            $this->template = NotificationTemplate::findByPK($this->templateId);
        } catch (Exception $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function resetTemplate(): void
    {
        if (!$this->template) {
            return;
        }

        try {
            $service = app(NotificationTemplateService::class);
            $service->reset($this->template);
            $this->flashMessage(__('admin-notifications.messages.reset'));
            $this->template = NotificationTemplate::findByPK($this->templateId);
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    protected function contentLayout()
    {
        $fields = [
            LayoutFactory::field(
                Input::make('key')
                    ->type('text')
                    ->value($this->template->key)
                    ->disabled(true)
            )
                ->label(__('admin-notifications.fields.key'))
                ->small(__('admin-notifications.hints.key')),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('module')
                        ->type('text')
                        ->value($this->template->module ?? 'core')
                        ->disabled(true)
                )
                    ->label(__('admin-notifications.fields.module')),

                LayoutFactory::field(
                    CheckBox::make('is_enabled')
                        ->label(__('admin-notifications.fields.enabled'))
                        ->checked($this->template->is_enabled)
                ),
            ]),

            LayoutFactory::field(
                Input::make('title')
                    ->type('text')
                    ->value($this->template->title)
                    ->placeholder(__('admin-notifications.placeholders.title'))
            )
                ->label(__('admin-notifications.fields.title'))
                ->small(__('admin-notifications.hints.title'))
                ->required(),

            LayoutFactory::field(
                TextArea::make('content')
                    ->value($this->template->content)
                    ->placeholder(__('admin-notifications.placeholders.content'))
                    ->rows(5)
            )
                ->label(__('admin-notifications.fields.content'))
                ->small(__('admin-notifications.hints.content'))
                ->required(),
        ];

        return LayoutFactory::block($fields)
            ->title(__('admin-notifications.blocks.content'));
    }

    protected function appearanceLayout()
    {
        $layoutOptions = [];
        foreach ($this->layoutTypes as $key => $label) {
            $layoutOptions[] = ['id' => $key, 'name' => $label];
        }

        $fields = [
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('icon')
                        ->type('text')
                        ->value($this->template->icon ?? '')
                        ->placeholder('ph.bold.bell-bold')
                )
                    ->label(__('admin-notifications.fields.icon'))
                    ->small(__('admin-notifications.hints.icon')),

                LayoutFactory::field(
                    Select::make('layout')
                        ->options($layoutOptions)
                        ->value($this->template->layout)
                )
                    ->label(__('admin-notifications.fields.layout')),
            ]),

            LayoutFactory::field(
                Input::make('priority')
                    ->type('number')
                    ->value((string) $this->template->priority)
                    ->placeholder('100')
            )
                ->label(__('admin-notifications.fields.priority'))
                ->small(__('admin-notifications.hints.priority')),
        ];

        return LayoutFactory::block($fields)
            ->title(__('admin-notifications.blocks.appearance'));
    }

    protected function channelsLayout()
    {
        $enabledChannels = $this->template->getChannels();
        if (empty($enabledChannels)) {
            $enabledChannels = ['inapp'];
        }
        $checkboxes = [];

        foreach ($this->channelOptions as $key => $channel) {
            $checkboxes[] = LayoutFactory::field(
                CheckBox::make("channels.{$key}")
                    ->label($channel['name'])
                    ->checked(in_array($key, $enabledChannels, true))
                    ->disabled(!$channel['enabled'])
            );
        }

        return LayoutFactory::block([
            LayoutFactory::view('admin-notifications::partials.channels-info'),
            LayoutFactory::split($checkboxes),
        ])
            ->title(__('admin-notifications.blocks.channels'))
            ->description(__('admin-notifications.blocks.channels_description'));
    }

    protected function variablesLayout()
    {
        $variables = $this->template->getVariables();

        if (empty($variables)) {
            return LayoutFactory::block([
                LayoutFactory::view('admin-notifications::partials.no-variables'),
            ])
                ->title(__('admin-notifications.blocks.variables'));
        }

        return LayoutFactory::block([
            LayoutFactory::view('admin-notifications::partials.variables-list', [
                'variables' => $variables,
            ]),
        ])
            ->title(__('admin-notifications.blocks.variables'))
            ->description(__('admin-notifications.blocks.variables_description'));
    }

    protected function componentsLayout()
    {
        $components = $this->template->getComponents();

        $fields = [
            LayoutFactory::field(
                TextArea::make('components_json')
                    ->value(!empty($components) ? json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '[]')
                    ->placeholder('[]')
                    ->rows(15)
            )
                ->label(__('admin-notifications.fields.components'))
                ->small(__('admin-notifications.hints.components')),
        ];

        return LayoutFactory::block($fields)
            ->title(__('admin-notifications.blocks.components'))
            ->description(__('admin-notifications.blocks.components_description'));
    }
}
