<?php

namespace Flute\Core\Modules\Auth\Components;

use Clickfwd\Yoyo\Component;
use Flute\Core\Exceptions\PasswordResetTokenExpiredException;
use Flute\Core\Exceptions\PasswordResetTokenNotFoundException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\UserNotFoundException;
use Nette\Schema\ValidationException;

class PasswordResetTokenComponent extends Component
{
    public ?string $password = null;
    public ?string $password_confirmation = null;
    public ?string $token = null;

    public function validate()
    {
        $this->validator();
    }

    public function reset()
    {
        if ($this->validator()) {
            try {
                auth()->resetPasswordToken($this->token, $this->password);

                flash()->success(__('auth.reset.success_reset'));

                $this->redirect(url('/'));
            } catch (ValidationException $e) {
                $errors = $e->getMessageObjects();

                foreach ($errors as $error) {
                    toast()->error(__($error->code, $error->variables))->push();
                }
            } catch (UserNotFoundException $e) {
                toast()->error(__('def.user_not_found'))->push();
            } catch (PasswordResetTokenExpiredException $e) {
                flash()->error(__('auth.reset.token_expired'));

                $this->redirect('/');
            } catch (PasswordResetTokenNotFoundException $e) {
                flash()->error(__('auth.reset.token_not_found'));

                $this->redirect('/');
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
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], [
            'password' => [
                'required',
                'confirmed',
                'min-str-len:' . config('auth.validation.password.min_length'),
                'max-str-len:' . config('auth.validation.password.max_length'),
            ],
            'password_confirmation' => 'required',
        ]);
    }

    public function render()
    {
        return $this->view('flute::components.reset.reset-token', [
            'token' => request()->input('token'),
        ]);
    }
}
