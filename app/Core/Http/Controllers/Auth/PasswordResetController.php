<?php

namespace Flute\Core\Http\Controllers\Auth;

use Flute\Core\Exceptions\PasswordResetTokenExpiredException;
use Flute\Core\Exceptions\PasswordResetTokenNotFoundException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Nette\Schema\ValidationException;

class PasswordResetController extends AbstractController
{
    public function getReset(FluteRequest $request)
    {
        $form = $this->getResetForm();

        breadcrumb()->add(__('auth.auth.title'), url('login'))->add(__('auth.reset.title'));

        return view('pages/reset_password', [
            "form" => $form,
        ], true);
    }

    public function postReset(FluteRequest $request)
    {
        $form = $this->getResetForm();

        if ($form->isSuccess()) {
            $data = (array) $form->getValues();

            try {
                auth()->resetPassword($data['login']);
                flash()->add('success', __('auth.submit_reset_success'));

                return response()->redirect('/');
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    flash()->add('error', __($error->code, $error->variables));
                }
            } catch (TooManyRequestsException $e) {
                flash()->add('error', __('auth.too_many_requests'));
            } catch (UserNotFoundException $e) {
                flash()->add('error', __('def.user_not_found'));
            } catch (\Exception $e) {
                logs()->error($e);
                $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');
                return response()->error(500, $message);
            }
        } else {
            flash()->add('error', __('auth.submit_reset_error'));
        }

        breadcrumb()->add(__('auth.auth.title'), url('login'))->add(__('auth.reset.title'));

        return response()->redirect('/reset');
    }

    public function getResetWithToken(FluteRequest $request, string $token)
    {
        try {
            $form = $this->getResetFormWithToken();

            breadcrumb()->add(__('auth.auth.title'), url('login'))->add(__('auth.reset.title'));

            return view('pages/reset_password_token', [
                "form" => $form,
            ], true);
        } catch (PasswordResetTokenNotFoundException $e) {
            return $this->error(__('auth.reset.token_not_found'), 404);
        }
    }

    public function postResetWithToken(FluteRequest $request, string $token)
    {
        $form = $this->getResetFormWithToken();

        if ($form->isSuccess()) {
            $data = (array) $form->getValues();

            try {
                auth()->resetPasswordToken($token, $data['password']);
                flash()->add('success', __('auth.reset.changed'));

                return response()->redirect('/');
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    flash()->add('error', __($error->code, $error->variables));
                }
            } catch (PasswordResetTokenExpiredException $e) {
                flash()->add('error', __('auth.reset.token_expired'));

                return response()->redirect('/');
            } catch (PasswordResetTokenNotFoundException $e) {
                flash()->add('error', __('auth.reset.token_not_found'));

                return response()->redirect('/');
            } catch (TooManyRequestsException $e) {
                flash()->add('error', __('auth.too_many_requests'));
            } catch (UserNotFoundException $e) {
                flash()->add('error', __('def.user_not_found'));
            } catch (\Exception $e) {
                logs()->error($e);
                $message = is_debug() ? ($e->getMessage() ?? __('def.unknown_error')) : __('def.unknown_error');
                return response()->error(500, $message);
            }
        } else {
            flash()->add('error', 'You magicized!');
        }

        return response()->redirect("/reset/{$token}");
    }

    protected function getResetForm(array $data = [])
    {
        $form = form($data);

        $form->addText('login', __('auth.auth.login'))
            // ->addRule($form::PATTERN, __('auth.registration.login_symbols'), '^[a-zA-Z0-9]*$')
            ->setRequired(__('auth.auth.enter_login'));

        $form->csrf();

        $form->addSubmit('submit', __('auth.reset.button'));

        return $form;
    }

    protected function getResetFormWithToken(array $data = [])
    {
        $form = form($data);

        $form->addPassword('password', __('auth.registration.password'))
            ->addRule($form::Equal, __('auth.registration.enter_password_confirmation_incorrect'), $form['password'])
            ->addRule($form::MIN_LENGTH, __('auth.registration.password_min_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.min_length'))
            ->addRule($form::MAX_LENGTH, __('auth.registration.password_max_length', ['length' => config('auth.validation.password.min_length')]), config('auth.validation.password.max_length'))
            ->setRequired(__('auth.registration.enter_password'));

        $form->addPassword('password_confirmation', __('auth.registration.password_confirmation'))
            ->setRequired(__('auth.registration.enter_password_confirmation'))
            ->addRule($form::Equal, __('auth.registration.enter_password_confirmation_incorrect'), $form['password'])
            ->setOmitted();

        $form->csrf();

        $form->addSubmit('submit', __('auth.reset.button'));

        return $form;
    }
}