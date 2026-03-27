<?php

namespace Flute\Core\Modules\Profile\Controllers;

use Exception;
use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Support\BaseController;

class ProfileVerificationController extends BaseController
{
    public function verifyEmail()
    {
        try {
            throttler()->throttle(['action' => 'verify_email', 'user' => user()->id], 3, 300, 1);

            $user = user()->getCurrentUser();

            $verificationToken = auth()->createVerificationToken($user)->rawToken;

            $template = template()->render('flute::emails.confirmation', [
                'url' => url('confirm/' . $verificationToken),
                'name' => $user->name,
            ]);

            email()->send($user->email, __('auth.confirmation.subject'), $template);

            toast()->success(__('auth.verification_token_sent'))->push();

            return response()->json(['success' => true]);
        } catch (AccountNotVerifiedException $e) {
            toast()->error(__('auth.verification_token_already_exists'))->push();
        } catch (Throwable $e) {
            toast()->error($e->getMessage())->push();
        }

        return response()->json(['success' => false]);
    }
}
