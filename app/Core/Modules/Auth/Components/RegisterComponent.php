<?php

namespace Flute\Core\Modules\Auth\Components;

use Flute\Core\Exceptions\DuplicateEmailException;
use Flute\Core\Exceptions\DuplicateLoginException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Modules\Auth\Events\RegisterFormRenderingEvent;
use Flute\Core\Modules\Auth\Events\RegisterValidatingEvent;
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
        if ($this->validator() && $this->validateExtensions() && $this->validateCaptcha()) {
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

    public function render()
    {
        $formEvent = new RegisterFormRenderingEvent([
            'name' => $this->name,
            'email' => $this->email,
            'login' => $this->login,
        ]);

        events()->dispatch($formEvent, RegisterFormRenderingEvent::NAME);

        return $this->view('flute::components.auth.register', [
            'token' => request()->input('token'),
            'formEvent' => $formEvent,
        ]);
    }

    protected function validator()
    {
        // Dispatch validation event for modules
        $validationEvent = new RegisterValidatingEvent([
            'name' => $this->name,
            'login' => $this->login,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        events()->dispatch($validationEvent, RegisterValidatingEvent::NAME);

        // Merge module rules with base rules
        $baseRules = [
            'name' => [
                'required',
                'human-name',
                'min-str-len:' . config('auth.validation.name.min_length'),
                'max-str-len:' . config('auth.validation.name.max_length'),
            ],
            'login' => [
                'required',
                'regex:/^[\p{L}\p{N}._-]+$/u',
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
        ];

        $rules = array_merge($baseRules, $validationEvent->rules);

        return validator()->validate([
            'name' => $this->name,
            'login' => $this->login,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], $rules);
    }

    /**
     * Validate using module event listeners.
     */
    protected function validateExtensions(): bool
    {
        $validationEvent = new RegisterValidatingEvent([
            'name' => $this->name,
            'login' => $this->login,
            'email' => $this->email,
            'password' => $this->password,
        ]);

        events()->dispatch($validationEvent, RegisterValidatingEvent::NAME);

        if ($validationEvent->stopValidation) {
            if (isset($validationEvent->errors['_global'])) {
                toast()->error($validationEvent->errors['_global'])->push();
            }

            return false;
        }

        if ($validationEvent->hasErrors()) {
            foreach ($validationEvent->errors as $field => $message) {
                if ($field === '_global') {
                    toast()->error($message)->push();
                } else {
                    $this->inputError($field, $message);
                }
            }

            return false;
        }

        return true;
    }

    protected function validateCaptcha()
    {
        /** @var CaptchaService $captchaService */
        $captchaService = app(CaptchaService::class);

        if (!$captchaService->isEnabled('register')) {
            return true;
        }

        $captchaResponse = request()->input('g-recaptcha-response')
            ?? request()->input('h-captcha-response')
            ?? request()->input('cf-turnstile-response');

        if (empty($captchaResponse)) {
            toast()->error(__('auth.captcha_required'))->push();

            return false;
        }

        if (!$captchaService->verify($captchaResponse, $captchaService->getType() . ':register')) {
            toast()->error(__('auth.captcha_invalid'))->push();

            return false;
        }

        return true;
    }
}
