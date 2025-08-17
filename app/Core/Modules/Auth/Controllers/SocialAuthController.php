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

                return response()->redirect('/profile/settings?tab=social');
            }

            $user = social()->authenticateWithRegister(ucfirst($provider));

            auth()->authenticateById($user->id, config('auth.remember_me'), true);

            events()->dispatch(new SocialLoggedInEvent($user), SocialLoggedInEvent::NAME);

            flash()->add('success', __('auth.login_success'));

            return response()->redirect('/');
        } catch (NeedRegistrationException $e) {
            return $this->error('This function is not supported yet.', 404);
        } catch (UserNotFoundException $e) {
            return $this->error(__('auth.errors.user_not_found'));
        } catch (SocialNotFoundException $e) {
            return $this->error(__('auth.errors.social_not_found'));
        } catch (\Exception $e) {
            logs()->error($e);

            if (is_debug()) {
                throw $e;
            }

            return $this->error(__('auth.errors.unknown'));
        }
    }

    // public function getSocialRegister( FluteRequest $request )
    // {

    // }

    // public function postSocialRegister( FluteRequest $request )
    // {

    // }
}
