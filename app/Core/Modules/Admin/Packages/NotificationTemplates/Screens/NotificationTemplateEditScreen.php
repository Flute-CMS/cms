<?php

namespace Flute\Admin\Packages\NotificationTemplates\Screens;

use Exception;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
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

    public array $channelOptions = [];

    public function mount(): void
    {
        $this->templateId = (int) ( request()->input('id') ?: $this->templateId );

        $service = app(NotificationTemplateService::class);
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

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(
            __('admin-notifications.title.list'),
            url('/admin/notification-templates'),
        )->add($this->template ? $this->template->key : __('admin-notifications.title.edit'));
    }

    public function commandBar(): array
    {
        $buttons = [
            Button::make(__('def.cancel'))->type(Color::OUTLINE_PRIMARY)->redirect('/admin/notification-templates'),
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

        $tabs[] = Tab::make(__('admin-notifications.tabs.settings'))
            ->icon('ph.bold.gear-bold')
            ->layouts([$this->settingsLayout()])
            ->active(true);

        $tabs[] = Tab::make(__('admin-notifications.tabs.channels'))
            ->icon('ph.bold.broadcast-bold')
            ->layouts([$this->channelsLayout()])
            ->badge(count($this->template->getChannels() ?: []));

        return [
            LayoutFactory::tabs($tabs)->slug('notification-edit')->pills(),
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
        ], $data);

        if (!$validation) {
            return;
        }

        // Parse buttons
        $components = null;
        $buttons = $this->parseButtons($data);
        if (!empty($buttons)) {
            $components = [
                ['type' => 'actions', 'buttons' => $buttons],
            ];
        }

        // Parse channels from toggles
        $channels = [];
        foreach ($this->channelOptions as $key => $channel) {
            $channelKey = 'channels_' . $key;
            if (isset($data[$channelKey]) && ( $data[$channelKey] === 'true' || $data[$channelKey] === '1' )) {
                $channels[] = $key;
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
                'components' => $components,
                'channels' => $channels,
                'is_enabled' => ( $data['is_enabled'] ?? '0' ) === '1',
            ]);

            $this->flashMessage(__('admin-notifications.messages.saved'));
            $this->template = NotificationTemplate::findByPK($this->templateId);
        } catch (Throwable $e) {
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

    protected function parseButtons(array $data): array
    {
        $buttons = [];
        $index = 0;

        while (isset($data["button_{$index}_label"]) || isset($data["button_{$index}_url"])) {
            $label = trim($data["button_{$index}_label"] ?? '');
            $url = trim($data["button_{$index}_url"] ?? '');

            if ($label !== '' || $url !== '') {
                $buttons[] = [
                    'label' => $label,
                    'url' => $url,
                    'type' => $data["button_{$index}_type"] ?? 'primary',
                ];
            }
            $index++;
        }

        return $buttons;
    }

    protected function settingsLayout()
    {
        $variables = $this->template->getVariables();
        $buttons = $this->extractButtons($this->template->getComponents());

        return LayoutFactory::blank([
            // Template info bar
            LayoutFactory::block([
                LayoutFactory::split([
                    LayoutFactory::field(Input::make('key')->value($this->template->key)->readonly())->label(__(
                        'admin-notifications.fields.key',
                    )),

                    LayoutFactory::field(
                        Input::make('module')
                            ->value($this->template->module ?? 'core')
                            ->readonly(),
                    )->label(__('admin-notifications.fields.module')),

                    LayoutFactory::field(
                        ButtonGroup::make('is_enabled')
                            ->options([
                                '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                                '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                            ])
                            ->value($this->template->is_enabled ? '1' : '0')
                            ->color('accent'),
                    )->label(__('admin-notifications.fields.enabled')),
                ]),
            ])->addClass('mb-3'),

            // Main content
            LayoutFactory::columns([
                // Left: Form
                LayoutFactory::blank([
                    LayoutFactory::block([
                        LayoutFactory::field(
                            Input::make('title')
                                ->value($this->template->title)
                                ->placeholder(__('admin-notifications.placeholders.title'))
                                ->required(),
                        )
                            ->label(__('admin-notifications.fields.title'))
                            ->required(),

                        LayoutFactory::field(
                            TextArea::make('content')
                                ->value($this->template->content)
                                ->placeholder(__('admin-notifications.placeholders.content'))
                                ->rows(3)
                                ->required(),
                        )
                            ->label(__('admin-notifications.fields.content'))
                            ->required(),

                        LayoutFactory::view('admin-notifications::partials.variables-list', [
                            'variables' => $variables,
                        ]),

                        LayoutFactory::field(
                            Input::make('icon')
                                ->type('icon')
                                ->value($this->template->icon ?? '')
                                ->placeholder('ph.bold.bell-bold'),
                        )->label(__('admin-notifications.fields.icon')),
                    ])->title(__('admin-notifications.blocks.content')),

                    // Buttons editor
                    LayoutFactory::block([
                        LayoutFactory::view('admin-notifications::partials.buttons-editor', [
                            'buttons' => $buttons,
                        ]),
                    ])
                        ->title(__('admin-notifications.blocks.buttons'))
                        ->description(__('admin-notifications.blocks.buttons_description'))
                        ->addClass('mt-3'),
                ]),

                // Right: Preview
                LayoutFactory::blank([
                    LayoutFactory::view('admin-notifications::partials.preview', [
                        'template' => $this->template,
                    ]),
                ]),
            ]),
        ]);
    }

    protected function channelsLayout()
    {
        $enabledChannels = $this->template->getChannels();
        if (empty($enabledChannels)) {
            $enabledChannels = ['inapp'];
        }

        return LayoutFactory::block([
            LayoutFactory::view('admin-notifications::partials.channels-list', [
                'channels' => $this->channelOptions,
                'enabledChannels' => $enabledChannels,
            ]),
        ])->title(__('admin-notifications.blocks.channels'))->description(__(
            'admin-notifications.blocks.channels_description',
        ));
    }

    protected function extractButtons(?array $components): array
    {
        if (!$components) {
            return [];
        }

        foreach ($components as $component) {
            if (( $component['type'] ?? '' ) === 'actions' && isset($component['buttons'])) {
                return $component['buttons'];
            }
        }

        return [];
    }
}
