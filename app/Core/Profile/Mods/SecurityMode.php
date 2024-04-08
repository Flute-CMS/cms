<?php

namespace Flute\Core\Profile\Mods;

use Flute\Core\Contracts\ProfileModInterface;
use Flute\Core\Database\Entities\User;
use Flute\Core\Exceptions\DuplicateEmailException;
use Flute\Core\Exceptions\DuplicateLoginException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Http\Middlewares\isAuthenticatedMiddleware;
use Flute\Core\Support\FluteRequest;

class SecurityMode implements ProfileModInterface
{
    public function __construct()
    {
        router()->post('profile/edit/security', [$this, 'handleSave'], [isAuthenticatedMiddleware::class]);
    }

    public function handleSave(FluteRequest $fluteRequest)
    {
        $form = $this->getSecurityForm(user()->getCurrentUser());

        if ($form->isSuccess() && $form->isValid()) {
            $data = (array) $form->getValues();

            $user = user()->getCurrentUser();
            $rep = rep(User::class);

            try {
                throttler()->throttle(['action' => "profile.change", request()->ip()], 5, 60);

                if ($data['login'] !== $user->login)
                    $rep->checkDuplicity($data['login']);

                if ($data['email'] !== $user->email) {
                    $rep->checkDuplicity($data['email']);

                    if (config('auth.registration.confirm_email'))
                        $user->verified = false;
                }

                $user->name = $data['name'];

                if ($user->uri !== $data['uri']) {
                    $this->checkUriDuplicate($data['uri']);
                    $user->uri = $data['uri'];
                }
                $user->login = $data['login'];
                $user->email = $data['email'];

                if (!empty($data['password']))
                    $user->setPassword($data['password']);

                user()->log('events.profile_changed');

                transaction($user)->run();

                return redirect(url('profile/edit', [
                    'mode' => 'security'
                ]))->with([
                            'success' => __('def.success')
                        ]);
            } catch (DuplicateLoginException $e) {
                return redirect(url('profile/edit', [
                    'mode' => 'security'
                ]))->withErrors(__('auth.duplicate_login'));
            } catch (DuplicateEmailException $e) {
                return redirect(url('profile/edit', [
                    'mode' => 'security'
                ]))->withErrors(__('auth.duplicate_email'));
            } catch (TooManyRequestsException $e) {
                return redirect(url('profile/edit', [
                    'mode' => 'security'
                ]))->withErrors(__('auth.too_many_requests'));
            } catch (\Exception $e) {
                return redirect(url('profile/edit', [
                    'mode' => 'security'
                ]))->withErrors($e->getMessage());
            }
        }

        return redirect(url('profile/edit', [
            'mode' => 'security'
        ]))->withErrors(__('def.unknown_error'));
    }

    protected function checkUriDuplicate(string $uri)
    {
        $user = rep(User::class)->findOne([
            'uri' => $uri
        ]);

        if (!empty($user)) {
            throw new \Exception(__('profile.errors.uri_taken'));
        }
    }

    public function getKey(): string
    {
        return 'security';
    }

    public function render(User $user): string
    {
        return render('pages/profile/edit/security', [
            "user" => user()->getCurrentUser(),
            "form" => $this->getSecurityForm($user)
        ], true);
    }

    public function getSidebarInfo(): array
    {
        return [
            'icon' => 'ph ph-lock-simple',
            'name' => 'profile.settings.security',
            'desc' => 'profile.settings.security_desc',
        ];
    }

    protected function getSecurityForm(User $user)
    {
        $form = form();

        $form->setAction(url('profile/edit/security'));

        $form->addText('name', __('auth.registration.name'))
            ->addRule($form::MIN_LENGTH, __('auth.registration.name_min_length', ['length' => config('auth.validation.name.min_length')]), config('auth.validation.name.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.name_max_length', ['length' => config('auth.validation.name.max_length')]), config('auth.validation.name.max_length'))
            ->setRequired(__('auth.registration.enter_name'))
            ->setOption('col-md', 6)
            ->setDefaultValue($user->name);

        $form->addText('uri', __('profile.s_main.nickname_uri'))
            ->setOption('col-md', 6)
            ->setDefaultValue($user->uri)
            ->addRule($form::PATTERN, __('auth.registration.login_symbols'), '^[a-zA-Z0-9]*$');

        $form->addEmail('email', __('auth.registration.email'))
            ->setRequired(true)
            ->setOption('col-md', 6)
            ->setDefaultValue($user->email);

        $form->addText('login', __('auth.registration.login'))
            ->addRule($form::PATTERN, __('auth.registration.login_symbols'), '^[a-zA-Z0-9]*$')
            ->addRule($form::MIN_LENGTH, __('auth.registration.login_min_length', ['length' => config('auth.validation.login.min_length')]), config('auth.validation.login.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.login_max_length', ['length' => config('auth.validation.login.max_length')]), config('auth.validation.login.max_length'))
            ->setRequired(__('auth.registration.enter_login'))
            ->setOption('col-md', 6)
            ->setDefaultValue($user->login);

        $form->addPassword('password', __('auth.registration.password'))
            ->addRule($form::MIN_LENGTH, __('auth.registration.password_min_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.password_max_length', ['length' => config('auth.validation.password.max_length')]), config('auth.validation.password.max_length'))
            ->setOption('col-md', 6);

        $form->addPassword('password_confirmation', __('auth.registration.password_confirmation'))
            ->setOption('col-md', 6)
            ->addConditionOn($form['password'], $form::FILLED)
            ->addRule($form::FILLED, __('auth.registration.enter_password_confirmation_incorrect'))
            ->addRule($form::EQUAL, __('auth.registration.enter_password_confirmation_incorrect'), $form['password']);

        $form->addSubmit('save', __('def.save'));

        return $form;
    }
}