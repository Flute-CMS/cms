<?php

namespace Flute\Admin\Packages\NotificationTemplates\Screens;

use Cycle\Database\Injection\Parameter;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\CheckBox;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Database\Entities\Role;
use Flute\Core\Database\Entities\User;
use Flute\Core\Modules\Notifications\Services\NotificationService;
use Flute\Core\Modules\Notifications\Services\NotificationTemplateService;
use Throwable;

class NotificationBroadcastScreen extends Screen
{
    public ?string $name = null;

    public ?string $description = null;

    public ?string $permission = 'admin.notifications';

    public string $target = 'all';

    public function mount(): void
    {
        $this->name = __('admin-notifications.broadcast.title');
        $this->description = __('admin-notifications.broadcast.description');

        $this->target = request()->input('target', $this->target);

        breadcrumb()->add(__('def.admin_panel'), url('/admin'))->add(__('admin-notifications.broadcast.title'));
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('def.send'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.paper-plane-tilt-bold')
                ->confirm(__('admin-notifications.broadcast.confirm_send'), 'info')
                ->method('send'),
        ];
    }

    public function layout(): array
    {
        return [
            // Recipients block
            LayoutFactory::block(array_filter([
                LayoutFactory::field(
                    Select::make('target')
                        ->options([
                            'all' => __('admin-notifications.broadcast.target_all'),
                            'roles' => __('admin-notifications.broadcast.target_roles'),
                            'users' => __('admin-notifications.broadcast.target_users'),
                        ])
                        ->aligned()
                        ->value($this->target)
                        ->yoyo(),
                )->label(__('admin-notifications.broadcast.target')),

                $this->target === 'roles'
                    ? LayoutFactory::field(
                        Select::make('roles')
                            ->fromDatabase('roles', 'name', 'id', ['name', 'id'])
                            ->multiple(true)
                            ->placeholder(__('admin-notifications.broadcast.roles')),
                    )
                        ->label(__('admin-notifications.broadcast.roles'))
                        ->required()
                    : null,

                $this->target === 'users'
                    ? LayoutFactory::field(
                        Select::make('users')
                            ->fromDatabase('users', 'name', 'id', ['name', 'id', 'login'])
                            ->multiple(true)
                            ->placeholder(__('admin-notifications.broadcast.users')),
                    )
                        ->label(__('admin-notifications.broadcast.users'))
                        ->required()
                    : null,
                LayoutFactory::columns($this->buildChannelFields()),
            ]))
                ->title(__('admin-notifications.broadcast.blocks.recipients'))
                ->description(__('admin-notifications.broadcast.blocks.recipients_description'))
                ->addClass('mb-3'),

            // Content + Preview columns
            LayoutFactory::columns([
                // Left: Form
                LayoutFactory::blank([
                    LayoutFactory::block([
                        LayoutFactory::field(
                            Input::make('title')
                                ->required()
                                ->placeholder(__('admin-notifications.broadcast.notification_title')),
                        )
                            ->label(__('admin-notifications.broadcast.notification_title'))
                            ->required(),

                        LayoutFactory::field(
                            TextArea::make('content')
                                ->required()
                                ->rows(4)
                                ->placeholder(__('admin-notifications.broadcast.notification_content')),
                        )
                            ->label(__('admin-notifications.broadcast.notification_content'))
                            ->required(),

                        LayoutFactory::field(
                            Input::make('icon')->type('icon')->placeholder('ph.bold.bell-bold'),
                        )->label(__('admin-notifications.broadcast.notification_icon')),

                        LayoutFactory::field(Input::make('url')->placeholder('https://'))->label(__(
                            'admin-notifications.broadcast.notification_url',
                        )),
                    ])->title(__('admin-notifications.broadcast.blocks.content'))->description(__(
                        'admin-notifications.broadcast.blocks.content_description',
                    )),
                ]),

                // Right: Preview
                LayoutFactory::blank([
                    LayoutFactory::view('admin-notifications::partials.broadcast-preview'),
                ]),
            ]),
        ];
    }

    public function send(): void
    {
        $target = request()->input('target', 'all');
        $title = request()->input('title');
        $content = request()->input('content');
        $icon = request()->input('icon') ?: 'ph.bold.bell-bold';
        $url = request()->input('url') ?: null;

        $channels = [];
        if (request()->input('channel_inapp')) {
            $channels[] = 'inapp';
        }
        if (request()->input('channel_email')) {
            $channels[] = 'email';
        }
        if (request()->input('channel_push')) {
            $channels[] = 'push';
        }

        if (empty($channels)) {
            $channels = ['inapp'];
        }

        $validation = $this->validate([
            'title' => ['required', 'string'],
            'content' => ['required', 'string'],
        ], request()->input());

        if (!$validation) {
            return;
        }

        $users = $this->resolveRecipients($target);

        if (empty($users)) {
            $this->flashMessage(__('admin-notifications.broadcast.no_recipients'), 'error');

            return;
        }

        $notificationService = app(NotificationService::class);
        $count = 0;

        $hasEmail = in_array('email', $channels, true) && function_exists('email');
        $hasPush = in_array('push', $channels, true);
        $hasInApp = in_array('inapp', $channels, true);

        $pushService = null;
        if ($hasPush) {
            try {
                $pushService = app('push.service');
            } catch (Throwable) {
                $hasPush = false;
            }
        }

        foreach ($users as $user) {
            try {
                if ($hasInApp) {
                    $notificationService->createTextNotification($user, $title, $content, $icon);
                }

                if ($hasEmail && ( $user->email ?? null )) {
                    $this->sendEmailNotification($user, $title, $content);
                }

                if ($hasPush && $pushService) {
                    $pushService->sendToUser($user, $title, $content, $icon, $url);
                }

                $count++;
            } catch (Throwable $e) {
                logs()->error($e);
            }
        }

        $this->flashMessage(__('admin-notifications.broadcast.sent', ['count' => $count]));
    }

    protected function sendEmailNotification(User $user, string $title, string $content): void
    {
        if (!function_exists('email') || !$user->email) {
            return;
        }

        email()->send(
            $user->email,
            strip_tags($title),
            view('notifications::emails.notification', [
                'title' => $title,
                'content' => $content,
                'components' => [],
                'user' => $user,
            ]),
        );
    }

    protected function buildChannelFields(): array
    {
        $channels = app(NotificationTemplateService::class)->getChannels();
        $fields = [];

        foreach ($channels as $key => $channel) {
            if (!$channel['enabled'] && $key !== 'inapp') {
                continue;
            }

            if ($key === 'telegram') {
                continue;
            }

            $fields[] = LayoutFactory::blank([
                LayoutFactory::field(
                    CheckBox::make('channel_' . $key)
                        ->label($channel['name'])
                        ->checked($key === 'inapp')
                        ->sendTrueOrFalse(),
                ),
            ]);
        }

        return $fields;
    }

    protected function resolveRecipients(string $target): array
    {
        switch ($target) {
            case 'roles':
                $roleIds = request()->input('roles', []);
                if (empty($roleIds)) {
                    return [];
                }

                $intRoleIds = array_map('intval', $roleIds);
                $roles = Role::query()->where('id', 'IN', new Parameter($intRoleIds))->fetchAll();
                if (empty($roles)) {
                    return [];
                }

                $validRoleIds = array_map(fn($r) => $r->id, $roles);
                $users = User::query()->where('roles.id', 'IN', new Parameter($validRoleIds))->fetchAll();

                // Deduplicate users (a user may have multiple matching roles)
                $seen = [];
                $unique = [];
                foreach ($users as $user) {
                    if (!isset($seen[$user->id])) {
                        $seen[$user->id] = true;
                        $unique[] = $user;
                    }
                }

                return $unique;

            case 'users':
                $userIds = request()->input('users', []);
                if (empty($userIds)) {
                    return [];
                }

                $intUserIds = array_map('intval', $userIds);

                return User::query()->where('id', 'IN', new Parameter($intUserIds))->fetchAll();

            default:
                return User::query()->fetchAll();
        }
    }
}
