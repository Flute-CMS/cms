<?php

namespace Flute\Core\Modules\Auth\Components;

use Flute\Core\Exceptions\DuplicateEmailException;
use Flute\Core\Exceptions\DuplicateLoginException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Services\CaptchaService;
use Flute\Core\Support\FluteComponent;
use Nette\Schema\ValidationException;

class RegisterComponent extends FluteComponent
{
    public ?string $name = null;
    public ?string $email = null;
    public ?string $login = null;
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public ?string $token = null;
    public $rememberMe;

    public function register()
    {
        if ($this->validator() && $this->validateCaptcha()) {
            $this->rememberMe = filter_var($this->rememberMe, FILTER_VALIDATE_BOOLEAN);

            try {
                auth()->register([
                    'email' => $this->email,
                    'login' => $this->login,
                    'name' => $this->name,
                    'password' => $this->password,
                    'remember_me' => $this->rememberMe,
                ], $this->rememberMe);

                toast()->success(app('auth.registration.confirm_email') ? __('auth.register_email') : __('auth.register_success'))->push();

                $this->modalClose('register-modal');

                $this->redirectTo(url('/'), 1500);
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    toast()->error(__($error->code, $error->variables))->push();
                }
            } catch (TooManyRequestsException $e) {
                toast()->error(__('auth.too_many_requests'))->push();
            } catch (DuplicateLoginException $e) {
                toast()->error(__('auth.duplicate_login'))->push();
            } catch (DuplicateEmailException $e) {
                toast()->error(__('auth.duplicate_email'))->push();
            }
        }
    }

    protected function validator()
    {
        return validator()->validate([
            'name' => $this->name,
            'login' => $this->login,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], [
            'name' => [
                'required',
                'human-name',
                'min-str-len:' . config('auth.validation.name.min_length'),
                'max-str-len:' . config('auth.validation.name.max_length'),
            ],
            'login' => [
                'required',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'min-str-len:' . config('auth.validation.login.min_length'),
                'max-str-len:' . config('auth.validation.login.max_length'),
            ],
            'email' => [
                'required',
                'email',
                'max-str-len:255',
            ],
            'password' => [
                'required',
                'confirmed',
                'min-str-len:' . config('auth.validation.password.min_length'),
                'max-str-len:' . config('auth.validation.password.max_length'),
            ],
        ]);
    }

    protected function validateCaptcha()
    {
        /** @var CaptchaService $captchaService */
        $captchaService = app(CaptchaService::class);

        if (!$captchaService->isEnabled('register')) {
            return true;
        }

        $captchaResponse = request()->input('g-recaptcha-response') ?? request()->input('h-captcha-response');

        if (empty($captchaResponse)) {
            toast()->error(__('auth.captcha_required'))->push();

            return false;
        }

        if (!$captchaService->verify($captchaResponse, $captchaService->getType())) {
            toast()->error(__('auth.captcha_invalid'))->push();

            return false;
        }

        return true;
    }

    public function render()
    {
        return $this->view('flute::components.auth.register', [
            'token' => request()->input('token'),
        ]);
    }
}
