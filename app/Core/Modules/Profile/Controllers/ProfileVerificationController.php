<?php

namespace Flute\Core\Modules\Profile\Controllers;

use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Support\BaseController;

class ProfileVerificationController extends BaseController
{
    public function verifyEmail()
    {
        try {
            $user = user()->getCurrentUser();

            $verificationToken = auth()->createVerificationToken($user)->token;

            $template = template()->render('flute::emails.confirmation', [
                'url' => url('confirm/' . $verificationToken),
                'name' => $user->name,
            ]);

            email()->send($user->email, __('auth.confirmation.subject'), $template);

            toast()->success(__('auth.verification_token_sent'))->push();

            return response()->json(['success' => true]);
        } catch (AccountNotVerifiedException $e) {
            toast()->error(__('auth.verification_token_already_exists'))->push();
        } catch (\Exception $e) {
            toast()->error($e->getMessage())->push();
        }

        return response()->json(['success' => false]);
    }
}
