<?php

namespace Flute\Core\Http\Controllers\Auth;

use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Exceptions\DuplicateEmailException;
use Flute\Core\Exceptions\DuplicateLoginException;
use Flute\Core\Exceptions\IncorrectPasswordException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Http\Controllers\Auth\Controls\RememberMeControl;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Services\FormService;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Nette\Schema\ValidationException;

class AuthController extends AbstractController
{
    public function getLogin(FluteRequest $request)
    {
        $form = $this->getAuthForm();

        return view('pages/auth', [
            "form" => $form,
            "social" => social()->toDisplay()
        ], true);
    }

    public function getConfirmation(FluteRequest $request, string $token)
    {
        try {
            if (auth()->verify($token)) {
                flash()->add('success', __('auth.confirmation.success'));
                return response()->redirect('/');
            }

            return response()->error(404);
        } catch (AccountNotVerifiedException $e) {
            return response()->error(404, __('auth.confirmation.verify_old'));
        }
    }

    public function postLogin(FluteRequest $request)
    {
        $form = $this->getAuthForm();

        if ($form->isSuccess() && $form->isValid()) {
            $data = (array) $form->getValues();

            try {
                auth()->authenticate($data, $data['remember_me']);
                flash()->add('success', __('auth.login_success'));

                return response()->redirect('/');
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    flash()->add('error', __($error->code, $error->variables));
                }
            } catch (AccountNotVerifiedException $e) {
                flash()->add('info', [
                    'text' => __('auth.account_not_verified'),
                    // 'link' => [
                    //     'href' => '#',
                    //     'text' => __('auth.resend_verification')
                    // ]
                ]);
            } catch (UserNotFoundException $e) {
                flash()->add('error', __('auth.incorrect_password_or_login')); // Сделано специально.
            } catch (IncorrectPasswordException $e) {
                flash()->add('error', __('auth.incorrect_password_or_login'));
            } catch (TooManyRequestsException $e) {
                flash()->add('error', __('auth.too_many_requests'));
            }
        }

        // flash()->add('error', __('auth.submit_reset_error'));

        breadcrumb()->add(__('auth.auth.title'));

        return view('pages/auth', [
            "form" => $form,
            "social" => social()->toDisplay()
        ], true);
    }

    public function getRegister(FluteRequest $request)
    {
        $form = $this->getRegisterForm();

        breadcrumb()->add(__('auth.auth.title'), url('login'))->add(__('auth.registration.title'));

        return view('pages/register', [
            "form" => $form,
            "social" => social()->toDisplay()
        ], true);
    }

    public function postRegister(FluteRequest $request)
    {
        $form = $this->getRegisterForm();

        if ($form->isSuccess() && $form->isValid()) {
            $data = (array) $form->getValues();

            try {
                auth()->register($data, $data['remember_me']);

                flash()->add('success', app('auth.registration.confirm_email') ? __('auth.register_email') : __('auth.register_success'));

                return response()->redirect('/');
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    flash()->add('error', __($error->code, $error->variables));
                }
            } catch (TooManyRequestsException $e) {
                flash()->add('error', __('auth.too_many_requests'));
            } catch (DuplicateLoginException $e) {
                flash()->add('error', __('auth.duplicate_login'));
            } catch (DuplicateEmailException $e) {
                flash()->add('error', __('auth.duplicate_email'));
            }
        }

        breadcrumb()->add(__('auth.auth.title'), url('login'))->add(__('auth.registration.title'));

        // flash()->add('error', __('auth.submit_reset_error'));

        return view('pages/register', [
            "form" => $form,
            "social" => social()->toDisplay()
        ], true);
    }

    public function getLogout(FluteRequest $request)
    {
        try {
            auth()->logout();

            social()->clearAuthData();

            flash()->add('success', __('auth.logout_success'));

            return response()->redirect('/');
        } catch (\Exception $e) {
            logs()->error($e);

            return response()->error(500, __('def.unknown_error'));
        }
    }

    protected function getAuthForm(array $default = [])
    {
        /** @var FormService $form */
        $form = form($default);

        $form->addText('login', __('auth.auth.login'))
            ->setRequired(__('auth.auth.enter_login'));
        $form
            ->addPassword('password', __('auth.auth.password'))
            ->setRequired(__('auth.auth.enter_password'));
        $form
            ->addComponent(new RememberMeControl(__('auth.remember_me')), 'remember_me');

        $form->csrf();

        $form->addSubmit('submit', __('auth.auth.button'));

        return $form;
    }

    protected function getRegisterForm(array $default = [])
    {
        /** @var FormService $form */
        $form = form($default);

        $form->addText('login', __('auth.registration.login'))
            ->addRule($form::PATTERN, __('auth.registration.login_symbols'), '^[a-zA-Z0-9]*$')
            ->addRule($form::MIN_LENGTH, __('auth.registration.login_min_length', ['length' => config('auth.validation.login.min_length')]), config('auth.validation.login.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.login_max_length', ['length' => config('auth.validation.login.min_length')]), config('auth.validation.login.max_length'))
            ->setRequired(__('auth.registration.enter_login'));

        $form->addText('name', __('auth.registration.name'))
            ->addRule($form::MIN_LENGTH, __('auth.registration.name_min_length', ['length' => config('auth.validation.name.min_length')]), config('auth.validation.name.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.name_max_length', ['length' => config('auth.validation.name.min_length')]), config('auth.validation.name.max_length'))
            ->setRequired(__('auth.registration.enter_name'));

        $form->addEmail('email', __('auth.registration.email'))->setRequired(__('auth.registration.enter_email'))
            ->addRule(
                    $form::Email,
                __('auth.registration.invalid_email')
            );

        $form->addPassword('password', __('auth.registration.password'))->setRequired(__('auth.registration.enter_password'));

        $form->addPassword('password_confirmation', __('auth.registration.password_confirmation'))
            ->setRequired(__('auth.registration.enter_password_confirmation'))
            ->addRule($form::Equal, __('auth.registration.enter_password_confirmation_incorrect'), $form['password'])
            ->addRule($form::MIN_LENGTH, __('auth.registration.password_min_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.password_max_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.max_length'))
            ->setOmitted();

        $form->addCheckbox('remember_me', __('auth.remember_me'))
            ->setDefaultValue(true);

        $form->csrf();

        $form->addSubmit('submit', __('auth.registration.button'));

        return $form;
    }
}