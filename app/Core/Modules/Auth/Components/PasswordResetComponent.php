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
            try {
                auth()->resetPassword($this->loginOrEmail);

                $this->success = true;

                return;
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    toast()->error(__($error->code, $error->variables))->push();
                }
            } catch (UserNotFoundException $e) {
                $this->success = true;

                sleep(rand(3, 7));
            } catch (TooManyRequestsException $e) {
                toast()->error(__('auth.too_many_requests'))->push();
            } catch (\Exception $e) {
                logs()->error($e);

                toast()->error(is_debug() ? $e->getMessage() : __('def.unknown_error'))->push();
            }
        }
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
        return $this->view('flute::components.reset.reset', ['success' => $this->success]);
    }
}
