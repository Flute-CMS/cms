<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\ButtonGroup;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Support\Color;
use Flute\Core\Services\EmailService;
use Throwable;

trait HasMailTab
{
    public function testMail()
    {
        try {
            $to = user()->email;
            $mail = config('mail');

            $mail['smtp'] = (bool) ( request()->input('smtp') ?? $mail['smtp'] ?? false );
            $mail['from'] = request()->input('from') ?? $to;
            $mail['host'] = request()->input('host') ?? $mail['host'];
            $mail['port'] = request()->input('port') ?? $mail['port'];
            $mail['username'] = request()->input('username') ?? $mail['username'];
            $mail['password'] = request()->input('password') ?? $mail['password'];
            $mail['secure'] = request()->input('secure') ?? $mail['secure'];

            config()->set('mail', $mail);

            if (!$to) {
                $this->flashMessage(__('admin-main-settings.messages.sender_email_not_set'), 'error');

                return;
            }

            app(EmailService::class)->send($to, 'SMTP Test', 'This is a test email. bla bla bla');
            $this->flashMessage(__('admin-main-settings.messages.test_mail_sent'), 'success');
        } catch (Throwable $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    private function mailSettingsLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::columns([
                LayoutFactory::split([
                    LayoutFactory::field(Toggle::make('smtp')->checked(config('mail.smtp')))->label(__(
                        'admin-main-settings.labels.smtp',
                    )),
                    LayoutFactory::field(
                        Input::make('host')
                            ->type('text')
                            ->placeholder(__('admin-main-settings.placeholders.smtp_host'))
                            ->value(config('mail.host')),
                    )->label(__('admin-main-settings.labels.host')),
                ])->ratio('40/60'),
                LayoutFactory::field(
                    Input::make('port')
                        ->type('number')
                        ->placeholder(__('admin-main-settings.placeholders.smtp_port'))
                        ->value(config('mail.port')),
                )->label(__('admin-main-settings.labels.port')),
            ]),
            LayoutFactory::columns([
                LayoutFactory::field(
                    Input::make('username')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.username'))
                        ->value(config('mail.username')),
                )->label(__('admin-main-settings.labels.username')),
                LayoutFactory::field(
                    Input::make('password')
                        ->type('password')
                        ->placeholder(__('admin-main-settings.placeholders.password'))
                        ->value(config('mail.password')),
                )->label(__('admin-main-settings.labels.password')),
                LayoutFactory::field(
                    ButtonGroup::make('secure')
                        ->options([
                            'tls' => [
                                'label' => 'TLS',
                                'icon' => 'ph.bold.lock-bold',
                            ],
                            'ssl' => [
                                'label' => 'SSL',
                                'icon' => 'ph.bold.shield-check-bold',
                            ],
                        ])
                        ->value(config('mail.secure'))
                        ->color('accent'),
                )->label(__('admin-main-settings.labels.secure')),
                LayoutFactory::field(
                    Input::make('from')
                        ->type('text')
                        ->placeholder(__('admin-main-settings.placeholders.from'))
                        ->value(config('mail.from')),
                )
                    ->label(__('admin-main-settings.labels.from'))
                    ->popover(__('admin-main-settings.popovers.from'))
                    ->small(__('admin-main-settings.examples.from')),
            ]),
            LayoutFactory::rows([
                Button::make(__('admin-main-settings.buttons.test_mail'))
                    ->size('small')
                    ->type(Color::ACCENT)
                    ->method('testMail'),
            ]),
        ])->title(__('admin-main-settings.blocks.mail_settings'));
    }
}
