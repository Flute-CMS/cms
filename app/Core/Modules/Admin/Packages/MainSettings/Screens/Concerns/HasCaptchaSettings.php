<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Layouts\LayoutFactory;

trait HasCaptchaSettings
{
    private function usersCaptchaBlock()
    {
        $captchaType = request()->input('captcha_type', config('auth.captcha.type'));

        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::field(
                    ButtonGroup::make('captcha_enabled_login')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.captcha.enabled.login') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.captcha_enabled_login'))
                    ->popover(__('admin-main-settings.popovers.captcha_enabled_login')),
                LayoutFactory::field(
                    ButtonGroup::make('captcha_enabled_register')
                        ->options([
                            '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                            '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                        ])
                        ->value(config('auth.captcha.enabled.register') ? '1' : '0')
                        ->color('accent'),
                )
                    ->label(__('admin-main-settings.labels.captcha_enabled_register'))
                    ->popover(__('admin-main-settings.popovers.captcha_enabled_register')),
            ]),
            LayoutFactory::field(
                ButtonGroup::make('captcha_enabled_password_reset')
                    ->options([
                        '0' => ['label' => __('def.off'), 'icon' => 'ph.bold.x-bold'],
                        '1' => ['label' => __('def.on'), 'icon' => 'ph.bold.check-bold'],
                    ])
                    ->value(config('auth.captcha.enabled.password_reset') ? '1' : '0')
                    ->color('accent'),
            )
                ->label(__('admin-main-settings.labels.captcha_enabled_password_reset'))
                ->popover(__('admin-main-settings.popovers.captcha_enabled_password_reset')),
            LayoutFactory::field(
                ButtonGroup::make('captcha_type')
                    ->yoyo()
                    ->options([
                        'recaptcha_v2' => [
                            'label' => 'reCAPTCHA v2',
                            'icon' => 'ph.bold.robot-bold',
                        ],
                        'recaptcha_v3' => [
                            'label' => 'reCAPTCHA v3',
                            'icon' => 'ph.bold.robot-bold',
                        ],
                        'hcaptcha' => [
                            'label' => 'hCaptcha',
                            'icon' => 'ph.bold.puzzle-piece-bold',
                        ],
                        'turnstile' => [
                            'label' => 'Turnstile',
                            'icon' => 'ph.bold.cloud-bold',
                        ],
                        'yandex' => [
                            'label' => 'Yandex SmartCaptcha',
                            'icon' => 'ph.bold.shield-check-bold',
                        ],
                    ])
                    ->value(config('auth.captcha.type'))
                    ->size('small'),
            )
                ->label(__('admin-main-settings.labels.captcha_type'))
                ->required(),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('recaptcha_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_site_key'))
                        ->value(config('auth.captcha.recaptcha.site_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_site_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_site_key')),
                LayoutFactory::field(
                    Input::make('recaptcha_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_secret_key'))
                        ->value(config('auth.captcha.recaptcha.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_secret_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_secret_key')),
            ])->setVisible($captchaType === 'recaptcha_v2'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('recaptcha_v3_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_v3_site_key'))
                        ->value(config('auth.captcha.recaptcha_v3.site_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_v3_site_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_v3_site_key')),
                LayoutFactory::field(
                    Input::make('recaptcha_v3_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_v3_secret_key'))
                        ->value(config('auth.captcha.recaptcha_v3.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_v3_secret_key'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_v3_secret_key')),
            ])->setVisible($captchaType === 'recaptcha_v3'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('recaptcha_v3_score_threshold')
                        ->type('number')
                        ->step('0.05')
                        ->min(0)
                        ->max(1)
                        ->placeholder(__('admin-main-settings.placeholders.recaptcha_v3_score_threshold'))
                        ->value(config('auth.captcha.recaptcha_v3.score_threshold', 0.5)),
                )
                    ->label(__('admin-main-settings.labels.recaptcha_v3_score_threshold'))
                    ->popover(__('admin-main-settings.popovers.recaptcha_v3_score_threshold')),
            ])->ratio('50/50')->setVisible($captchaType === 'recaptcha_v3'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('hcaptcha_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.hcaptcha_site_key'))
                        ->value(config('auth.captcha.hcaptcha.site_key')),
                )
                    ->label(__('admin-main-settings.labels.hcaptcha_site_key'))
                    ->popover(__('admin-main-settings.popovers.hcaptcha_site_key')),
                LayoutFactory::field(
                    Input::make('hcaptcha_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.hcaptcha_secret_key'))
                        ->value(config('auth.captcha.hcaptcha.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.hcaptcha_secret_key'))
                    ->popover(__('admin-main-settings.popovers.hcaptcha_secret_key')),
            ])->setVisible($captchaType === 'hcaptcha'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('turnstile_site_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.turnstile_site_key'))
                        ->value(config('auth.captcha.turnstile.site_key')),
                )
                    ->label(__('admin-main-settings.labels.turnstile_site_key'))
                    ->popover(__('admin-main-settings.popovers.turnstile_site_key')),
                LayoutFactory::field(
                    Input::make('turnstile_secret_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.turnstile_secret_key'))
                        ->value(config('auth.captcha.turnstile.secret_key')),
                )
                    ->label(__('admin-main-settings.labels.turnstile_secret_key'))
                    ->popover(__('admin-main-settings.popovers.turnstile_secret_key')),
            ])->setVisible($captchaType === 'turnstile'),
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('yandex_client_key')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.yandex_client_key'))
                        ->value(config('auth.captcha.yandex.client_key')),
                )
                    ->label(__('admin-main-settings.labels.yandex_client_key'))
                    ->popover(__('admin-main-settings.popovers.yandex_client_key')),
                LayoutFactory::field(
                    Input::make('yandex_server_key')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.yandex_server_key'))
                        ->value(config('auth.captcha.yandex.server_key')),
                )
                    ->label(__('admin-main-settings.labels.yandex_server_key'))
                    ->popover(__('admin-main-settings.popovers.yandex_server_key')),
            ])->setVisible($captchaType === 'yandex'),
        ])->title(__('admin-main-settings.blocks.captcha_settings'));
    }
}
