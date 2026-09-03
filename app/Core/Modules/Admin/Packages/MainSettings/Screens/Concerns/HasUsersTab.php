<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Sight;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Modules\Profile\Services\ProfileTabService;
use stdClass;
use Throwable;

trait HasUsersTab
{
    public function saveProfileTabsOrder()
    {
        $sortableResult = json_decode(request()->input('sortableResult', '[]'), true);

        if (!$sortableResult) {
            $this->flashMessage(__('admin-main-settings.messages.invalid_sort'), 'error');

            return;
        }

        $order = [];
        foreach ($sortableResult as $item) {
            if (isset($item['id'])) {
                $order[] = $item['id'];
            }
        }

        config()->set('profile.tabs_order', $order);

        try {
            config()->save();
            cache()->deleteImmediately('profile_tabs_cache');
            $this->flashMessage(__('admin-main-settings.messages.profile_tabs_order_saved'));
            $this->loadProfileTabs();
            $this->invalidateConfig('profile');
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    protected function loadProfileTabs(): void
    {
        if (app()->has(ProfileTabService::class)) {
            $profileTabService = app(ProfileTabService::class);
            $this->profileTabs = $profileTabService->getUniqueTabPaths()->map(static function ($tab) {
                $obj = new stdClass();
                $obj->id = $tab['id'];
                $obj->path = $tab['path'];
                $obj->title = $tab['title'];
                $obj->icon = $tab['icon'];

                return $obj;
            });
        } else {
            $this->profileTabs = collect();
        }
    }

    private function usersSettingsLayout()
    {
        return LayoutFactory::tabs([
            Tab::make(__('admin-main-settings.blocks.auth_settings'))->layouts([
                $this->usersAuthBlock(),
                $this->usersSessionBlock(),
            ]),
            Tab::make(__('admin-main-settings.blocks.captcha_settings'))->layouts([
                $this->usersCaptchaBlock(),
            ]),
            Tab::make(__('admin-main-settings.blocks.two_factor_settings'))->layouts([
                $this->usersTwoFactorBlock(),
            ]),
            Tab::make(__('admin-main-settings.blocks.profile_settings'))->layouts([
                $this->usersProfileBlock(),
                $this->usersProfileTabsOrderBlock(),
            ]),
        ])
            ->slug('users_settings_sections')
            ->pills()
            ->sticky(false)
            ->lazyload(false);
    }

    private function usersAuthBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('reset_password')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.reset_password') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.reset_password'))
                    ->popover(__('admin-main-settings.popovers.reset_password')),
                LayoutFactory::field(
                    ButtonGroup::make('only_social')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.only_social') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.only_social'))
                    ->popover(__('admin-main-settings.popovers.only_social')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('only_modal')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.auth.page'),
                                'icon' => 'ph.bold.browser-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.auth.modal'),
                                'icon' => 'ph.bold.frame-corners-bold',
                            ],
                        ])
                        ->value(config('auth.only_modal') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.only_modal'))
                    ->popover(__('admin-main-settings.popovers.only_modal')),
                LayoutFactory::field(
                    ButtonGroup::make('confirm_email')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.envelope-bold'],
                        ])
                        ->value(config('auth.registration.confirm_email') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.confirm_email'))
                    ->popover(__('admin-main-settings.popovers.confirm_email')),
                LayoutFactory::field(
                    ButtonGroup::make('social_supplement')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.user-plus-bold'],
                        ])
                        ->value(config('auth.registration.social_supplement') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.social_supplement'))
                    ->popover(__('admin-main-settings.popovers.social_supplement')),
            ]),
            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('remember_me')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.remember_me') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.remember_me'))
                    ->popover(__('admin-main-settings.popovers.remember_me')),
                LayoutFactory::field(
                    Input::make('remember_me_duration')
                        ->type('number')
                        ->placeholder(__('admin-main-settings.placeholders.remember_me_duration'))
                        ->value(config('auth.remember_me_duration')),
                )
                    ->label(__('admin-main-settings.labels.remember_me_duration'))
                    ->small(__('admin-main-settings.examples.remember_me_duration')),
            ]),
            LayoutFactory::field(
                Select::make('default_role')
                    ->fromDatabase('roles', 'name', 'id', ['name', 'id'])
                    ->placeholder(__('admin-main-settings.placeholders.default_role_placeholder'))
                    ->value(config('auth.default_role', 0)),
            )
                ->label(__('admin-main-settings.labels.default_role'))
                ->popover(__('admin-main-settings.popovers.default_role')),
        ])->title(__('admin-main-settings.blocks.auth_settings'))->addClass('mb-3');
    }

    private function usersSessionBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    ButtonGroup::make('check_ip')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.map-pin-bold'],
                        ])
                        ->value(config('auth.check_ip') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.check_ip'))
                    ->popover(__('admin-main-settings.popovers.check_ip')),
                LayoutFactory::field(
                    ButtonGroup::make('security_token')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.key-bold'],
                        ])
                        ->value(config('auth.security_token') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.security_token'))
                    ->popover(__('admin-main-settings.popovers.security_token')),
            ]),
        ])->title(__('admin-main-settings.blocks.session_settings'))->description(__(
            'admin-main-settings.blocks.session_description',
        ));
    }

    private function usersTwoFactorBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('two_factor_enabled')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.shield-check-bold'],
                        ])
                        ->value(config('auth.two_factor.enabled') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.two_factor_enabled'))
                    ->popover(__('admin-main-settings.popovers.two_factor_enabled')),
                LayoutFactory::field(
                    ButtonGroup::make('two_factor_force')
                        ->options([
                            '0' => [
                                'label' => __('admin-main-settings.options.two_factor.optional'),
                                'icon' => 'ph.bold.user-bold',
                            ],
                            '1' => [
                                'label' => __('admin-main-settings.options.two_factor.required'),
                                'icon' => 'ph.bold.lock-bold',
                            ],
                        ])
                        ->value(config('auth.two_factor.force') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.two_factor_force'))
                    ->popover(__('admin-main-settings.popovers.two_factor_force')),
            ]),
            LayoutFactory::field(
                Input::make('two_factor_issuer')
                    ->type('text')
                    ->placeholder(__('admin-main-settings.placeholders.two_factor_issuer'))
                    ->value(config('auth.two_factor.issuer', '')),
            )
                ->label(__('admin-main-settings.labels.two_factor_issuer'))
                ->popover(__('admin-main-settings.popovers.two_factor_issuer')),
        ])->title(__('admin-main-settings.blocks.two_factor_settings'));
    }

    private function usersProfileBlock()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                ButtonGroup::make('change_uri')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.link-bold'],
                    ])
                    ->value(config('profile.change_uri') ? '1' : '0')
                    ->color('accent'),
            )->label(__('admin-main-settings.labels.change_uri')),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('default_avatar')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(asset(config('profile.default_avatar'))),
                )->label(__('admin-main-settings.labels.default_avatar')),
                LayoutFactory::field(
                    Input::make('default_banner')
                        ->type('file')
                        ->filePond()
                        ->accept('image/png, image/jpeg, image/gif, image/webp')
                        ->defaultFile(asset(config('profile.default_banner'))),
                )->label(__('admin-main-settings.labels.default_banner')),
            ]),
            LayoutFactory::rows([
                Button::make(__('admin-main-settings.buttons.save_profile_images'))
                    ->size('small')
                    ->type(Color::ACCENT)
                    ->method('saveProfileImages'),
            ]),
        ])->title(__('admin-main-settings.blocks.profile_settings'));
    }

    private function usersProfileTabsOrderBlock()
    {
        if ($this->profileTabs->isEmpty()) {
            return LayoutFactory::block([
                LayoutFactory::view('admin-main-settings::profile-tabs-empty'),
            ])->title(__('admin-main-settings.blocks.profile_tabs_order'));
        }

        return LayoutFactory::sortable('profileTabs', [
            Sight::make('title', __('admin-main-settings.labels.profile_tab_title'))->render(static function ($tab) {
                $icon = $tab->icon;
                $title = $tab->title;
                $path = $tab->path;

                return view('admin-main-settings::cells.profile-tab-item', compact('icon', 'title', 'path'));
            }),
        ])
            ->title(__('admin-main-settings.blocks.profile_tabs_order'))
            ->description(__('admin-main-settings.blocks.profile_tabs_order_description'))
            ->maxLevels(1)
            ->onSortEnd('saveProfileTabsOrder');
    }
}
