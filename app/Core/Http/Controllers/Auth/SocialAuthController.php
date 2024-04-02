<?php

namespace Flute\Core\Http\Controllers\Auth;

use Flute\Core\Auth\Events\SocialLoggedInEvent;
use Flute\Core\Exceptions\NeedRegistrationException;
use Flute\Core\Exceptions\SocialNotFoundException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

class SocialAuthController extends AbstractController
{
    public function redirectToProvider( FluteRequest $request, string $provider )
    {
        try {
            $user = social()->authenticateWithRegister(ucfirst($provider));

            auth()->authenticateById( $user->id, config('auth.remember_me') );

            events()->dispatch(new SocialLoggedInEvent($user), SocialLoggedInEvent::NAME);

            flash()->add('success', __('auth.login_success') );

            return response()->redirect('/');
        }
        catch (NeedRegistrationException $e) {
            return $this->error('Эта функция еще не поддерживается.', 404);
        }
        catch (UserNotFoundException $e) {
            return $this->error(__('auth.errors.user_not_found'));
        }
        catch (SocialNotFoundException $e) {
            return $this->error(__('auth.errors.social_not_found'));
        }
        catch (\Exception $e) {
            logs()->error($e);
        
            if( app('debug') )
                throw $e;

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