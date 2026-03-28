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
        $origin = json_encode($this->getSiteOrigin());

        try {
            if (user()->isLoggedIn()) {
                social()->bindSocialNetwork(user()->getCurrentUser(), ucfirst($provider));

                flash()->add('success', __('auth.errors.social_binded'));

                $redirectUrl = redirect('/profile/settings?tab=social')->getTargetUrl();

                $redirectUrlJs = json_encode($redirectUrl);

                return response()->make(
                    "<script>if (window.opener) { window.opener.postMessage('authorization_success', "
                    . $origin
                    . '); window.close(); } else { window.location = '
                    . $redirectUrlJs
                    . '; }</script>',
                );
            }

            $user = social()->authenticateWithRegister(ucfirst($provider));

            auth()->authenticateById($user->id, config('auth.remember_me'), true);

            events()->dispatch(new SocialLoggedInEvent($user), SocialLoggedInEvent::NAME);

            flash()->add('success', __('auth.login_success'));

            $redirectUrl = redirect('/')->getTargetUrl();
            $redirectUrlJs = json_encode($redirectUrl);

            return response()->make(
                "<script>if (window.opener) { window.opener.postMessage('authorization_success', "
                . $origin
                . '); window.close(); } else { window.location = '
                . $redirectUrlJs
                . '; }</script>',
            );
        } catch (NeedRegistrationException $e) {
            $profile = $e->getProfile();
            $socialNetwork = $e->getSocialNetwork();

            $payload = json_encode([
                'identifier' => $profile->identifier,
                'displayName' => $profile->displayName,
                'email' => $profile->email,
                'photoURL' => $profile->photoURL,
                'profileURL' => $profile->profileURL,
                'social_id' => $socialNetwork->id,
                'provider_key' => $socialNetwork->key,
                'issued_at' => time(),
            ]);

            session()->set('social_supplement', encrypt()->encrypt($payload));

            $redirectUrl = json_encode(url('/social/supplement')->get());

            return response()->make(
                '<script>if (window.opener) { window.opener.location = '
                . $redirectUrl
                . '; window.close(); } else { window.location = '
                . $redirectUrl
                . '; }</script>',
            );
        } catch (UserNotFoundException $e) {
            return $this->socialError(__('auth.errors.user_not_found'), $request);
        } catch (SocialNotFoundException $e) {
            return $this->socialError(__('auth.errors.social_not_found'), $request);
        } catch (Throwable $e) {
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
    protected function socialSuccess(?FluteRequest $request = null)
    {
        $redirectUrl = redirect('/')->getTargetUrl();
        $origin = json_encode($this->getSiteOrigin());

        $redirectUrlJs = json_encode($redirectUrl);

        return response()->make(
            "<script>if (window.opener) { window.opener.postMessage('authorization_success', "
            . $origin
            . '); window.close(); } else { window.location = '
            . $redirectUrlJs
            . '; }</script>',
        );
    }

    /**
     * Returns an error response for social network authorization in popup flows.
     */
    protected function socialError(string $error, ?FluteRequest $request = null)
    {
        $redirectUrl = redirect('/')->getTargetUrl();
        $origin = json_encode($this->getSiteOrigin());

        $errorJs = json_encode($error);
        $redirectUrlJs = json_encode($redirectUrl);

        return response()->make(
            "<script>if (window.opener) { window.opener.postMessage('authorization_error:' + "
            . $errorJs
            . ', '
            . $origin
            . '); window.close(); } else { alert('
            . $errorJs
            . '); window.location = '
            . $redirectUrlJs
            . '; }</script>',
        );
    }

    /**
     * Get the site origin for postMessage security.
     */
    private function getSiteOrigin(): string
    {
        $url = config('app.url');
        if (!empty($url)) {
            $parsed = parse_url($url);

            return ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' );
        }

        return request()->getSchemeAndHttpHost();
    }
}
