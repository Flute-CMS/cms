<?php

namespace Flute\Core\Modules\Auth\Components;

use Flute\Core\Database\Entities\User;
use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Exceptions\IncorrectPasswordException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\TwoFactorRequiredException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Services\CaptchaService;
use Flute\Core\Services\TwoFactorService;
use Flute\Core\Support\FluteComponent;
use Nette\Schema\ValidationException;

class LoginComponent extends FluteComponent
{
    public ?string $loginOrEmail = null;

    public ?string $password = null;

    public $rememberMe;

    public bool $showTwoFactor = false;

    public ?string $twoFactorCode = null;

    public ?int $pendingUserId = null;

    public function login()
    {
        if ($this->validator() && $this->validateCaptcha()) {
            try {
                $this->rememberMe = filter_var($this->rememberMe, FILTER_VALIDATE_BOOLEAN);

                auth()->authenticate([
                    'login' => $this->loginOrEmail,
                    'password' => $this->password,
                    'remember_me' => $this->rememberMe ??= false,
                ], $this->rememberMe ??= false);

                toast()->success(__('auth.login_success'))->push();

                $this->modalClose('auth-modal');

                $this->redirectTo(url('/'), 1500);
            } catch (TwoFactorRequiredException $e) {
                $this->pendingUserId = $e->getUser()->id;
                $this->showTwoFactor = true;
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    toast()->error(__($error->code, $error->variables))->push();
                }
            } catch (AccountNotVerifiedException $e) {
                toast()->error(__('auth.account_not_verified'))->withDuration(10000)->push();
            } catch (UserNotFoundException $e) {
                toast()->error(__('auth.incorrect_password_or_login'))->push();
            } catch (IncorrectPasswordException $e) {
                toast()->error(__('auth.incorrect_password_or_login'))->push();
            } catch (TooManyRequestsException $e) {
                toast()->error(__('auth.too_many_requests'))->push();
            }
        }
    }

    public function verifyTwoFactor()
    {
        if (!$this->validate([
            'twoFactorCode' => 'required|string|min-str-len:6|max-str-len:20',
        ])) {
            return;
        }

        if (!$this->pendingUserId) {
            $this->showTwoFactor = false;

            return;
        }

        $user = User::findByPK($this->pendingUserId);

        if (!$user) {
            toast()->error(__('auth.errors.user_not_found'))->push();
            $this->showTwoFactor = false;

            return;
        }

        /** @var TwoFactorService $twoFactorService */
        $twoFactorService = app(TwoFactorService::class);

        $verified = $twoFactorService->verifyCode($user, $this->twoFactorCode);

        if (!$verified) {
            $verified = $twoFactorService->verifyRecoveryCode($user, $this->twoFactorCode);
        }

        if (!$verified) {
            $this->inputError('twoFactorCode', __('auth.two_factor.invalid_code'));

            return;
        }

        $this->rememberMe = filter_var($this->rememberMe, FILTER_VALIDATE_BOOLEAN);
        auth()->authenticateById($user->id, $this->rememberMe, false);

        toast()->success(__('auth.login_success'))->push();

        $this->modalClose('auth-modal');

        $this->redirectTo(url('/'), 1500);
    }

    public function cancelTwoFactor()
    {
        $this->showTwoFactor = false;
        $this->pendingUserId = null;
        $this->twoFactorCode = null;
    }

    public function render()
    {
        return $this->view('flute::components.auth.login', [
            'showTwoFactor' => $this->showTwoFactor,
            'pendingUserId' => $this->pendingUserId,
        ]);
    }

    protected function validator()
    {
        return validator()->validate([
            'loginOrEmail' => $this->loginOrEmail,
            'password' => $this->password,
        ], [
            'loginOrEmail' => [
                'required',
                'regex:/^([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})|([a-zA-Z0-9._-]+)$/',
            ],
            'password' => 'required',
        ]);
    }

    protected function validateCaptcha()
    {
        /** @var CaptchaService $captchaService */
        $captchaService = app(CaptchaService::class);

        if (!$captchaService->isEnabled('login')) {
            return true;
        }

        $captchaResponse = request()->input('g-recaptcha-response')
            ?? request()->input('h-captcha-response')
            ?? request()->input('cf-turnstile-response');

        if (empty($captchaResponse)) {
            toast()->error(__('auth.captcha_required'))->push();

            return false;
        }

        if (!$captchaService->verify($captchaResponse, $captchaService->getType() . ':login')) {
            toast()->error(__('auth.captcha_invalid'))->push();

            return false;
        }

        return true;
    }
}
