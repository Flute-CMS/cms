<?php

namespace Flute\Core\Modules\Auth\Components;

use Clickfwd\Yoyo\Component;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Services\CaptchaService;
use Nette\Schema\ValidationException;

class PasswordResetComponent extends Component
{
    public ?string $loginOrEmail = null;

    public bool $success = false;

    public function validate()
    {
        $this->validator();
    }

    public function reset()
    {
        if ($this->validator() && $this->validateCaptcha()) {
            $startTime = microtime(true);

            try {
                auth()->resetPassword($this->loginOrEmail);
            } catch (UserNotFoundException $e) {
                // intentional no-op — same response as success
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();
                $firstError = $errors[0] ?? null;

                if ($firstError) {
                    toast()->error(__($firstError->code, $firstError->variables))->push();
                }

                return;
            } catch (TooManyRequestsException $e) {
                toast()->error(__('auth.too_many_requests'))->push();

                return;
            } catch (Throwable $e) {
                logs()->error($e);

                toast()->error(is_debug() ? $e->getMessage() : __('def.unknown_error'))->push();

                return;
            }

            // Constant-time response to prevent user enumeration
            $elapsed = microtime(true) - $startTime;
            $minTime = 3.0;
            if ($elapsed < $minTime) {
                usleep((int) ( ( $minTime - $elapsed ) * 1_000_000 ));
            }

            $this->success = true;
        }
    }

    public function render()
    {
        return $this->view('flute::components.reset.reset', ['success' => $this->success]);
    }

    protected function validator()
    {
        return validator()->validate([
            'loginOrEmail' => $this->loginOrEmail,
        ], [
            'loginOrEmail' => [
                'required',
                'regex:/^([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})|([a-zA-Z0-9._-]+)$/',
            ],
        ]);
    }

    protected function validateCaptcha()
    {
        /** @var CaptchaService $captchaService */
        $captchaService = app(CaptchaService::class);

        if (!$captchaService->isEnabled('password_reset')) {
            return true;
        }

        $captchaResponse =
            request()->input('g-recaptcha-response') ?? request()->input('h-captcha-response') ?? request()->input(
                'cf-turnstile-response',
            ) ?? request()->input('smart-token');

        if (empty($captchaResponse)) {
            toast()->error(__('auth.captcha_required'))->push();

            return false;
        }

        if (!$captchaService->verify($captchaResponse, $captchaService->getType() . ':password_reset')) {
            toast()->error(__('auth.captcha_invalid'))->push();

            return false;
        }

        return true;
    }
}
