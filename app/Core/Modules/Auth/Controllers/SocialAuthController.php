<?php

namespace Flute\Core\Modules\Auth\Controllers;

use Flute\Core\Exceptions\NeedRegistrationException;
use Flute\Core\Exceptions\SocialNotFoundException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Modules\Auth\Events\SocialLoggedInEvent;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;

class SocialAuthController extends BaseController
{
    public function redirectToProvider(FluteRequest $request, string $provider)
    {
        try {
            if (user()->isLoggedIn()) {
                social()->bindSocialNetwork(user()->getCurrentUser(), ucfirst($provider));

                flash()->add('success', __('auth.errors.social_binded'));

                $redirectUrl = redirect('/profile/settings?tab=social')->getTargetUrl();

                return response()->make("<script>if (window.opener) { window.opener.postMessage('authorization_success', '*'); window.close(); } else { window.location = '" . $redirectUrl . "'; }</script>");
            }

            $user = social()->authenticateWithRegister(ucfirst($provider));

            auth()->authenticateById($user->id, config('auth.remember_me'), true);

            events()->dispatch(new SocialLoggedInEvent($user), SocialLoggedInEvent::NAME);

            flash()->add('success', __('auth.login_success'));

            $redirectUrl = redirect('/')->getTargetUrl();

            return response()->make("<script>if (window.opener) { window.opener.postMessage('authorization_success', '*'); window.close(); } else { window.location = '" . $redirectUrl . "'; }</script>");
        } catch (NeedRegistrationException $e) {
            return $this->socialError('This function is not supported yet.', $request);
        } catch (UserNotFoundException $e) {
            return $this->socialError(__('auth.errors.user_not_found'), $request);
        } catch (SocialNotFoundException $e) {
            return $this->socialError(__('auth.errors.social_not_found'), $request);
        } catch (\Exception $e) {
            logs()->error($e);

            if (is_debug()) {
                throw $e;
            }
            return $this->socialError(__('auth.errors.unknown'), $request);
        }
    }

    /**
     * Returns a successful response for social network authorization in popup flows.
     */
    protected function socialSuccess(FluteRequest $request = null)
    {
        $redirectUrl = redirect('/')->getTargetUrl();

        return response()->make("<script>if (window.opener) { window.opener.postMessage('authorization_success', '*'); window.close(); } else { window.location = '" . $redirectUrl . "'; }</script>");
    }

    /**
     * Returns an error response for social network authorization in popup flows.
     */
    protected function socialError(string $error, FluteRequest $request = null)
    {
        $redirectUrl = redirect('/')->getTargetUrl();

        return response()->make("<script>if (window.opener) { window.opener.postMessage('authorization_error:' + '" . addslashes($error) . "', '*'); window.close(); } else { alert('" . addslashes($error) . "'); window.location = '" . $redirectUrl . "'; }</script>");
    }

    // public function getSocialRegister( FluteRequest $request )
    // {

    // }

    // public function postSocialRegister( FluteRequest $request )
    // {

    // }
}
