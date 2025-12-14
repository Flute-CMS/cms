<?php

namespace Flute\Core\Modules\Profile\Controllers;

use DateTime;
use Exception;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Exceptions\SocialNotFoundException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Services\DiscordService;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class ProfileSocialBindController extends BaseController
{
    /**
     * Shows the social network binding page.
     */
    public function bindSocial(FluteRequest $fluteRequest, string $provider): Response
    {
        try {
            $auth = social()->authenticate(ucfirst($provider), true);
            $token = $auth['adapter']->getAccessToken();

            $userProfile = $auth['profile'];

            $duplicateCheck = UserSocialNetwork::findOne(['value' => $userProfile->identifier]);

            if ($duplicateCheck) {
                if ($duplicateCheck->user->isTemporary) {
                    transaction($duplicateCheck, 'delete')->run();
                } else {
                    return $this->socialError(__('profile.errors.social_binded'));
                }
            }

            $userSocialNetwork = new UserSocialNetwork();
            $userSocialNetwork->value = $userProfile->identifier;
            $userSocialNetwork->url = $userProfile->profileURL;
            $userSocialNetwork->name = $userProfile->displayName;

            $userSocialNetwork->user = user()->getCurrentUser();
            $userSocialNetwork->socialNetwork = SocialNetwork::findOne(['key' => ucfirst($provider)]);

            if ($token) {
                $userSocialNetwork->additional = json_encode($token);
            }

            transaction($userSocialNetwork)->run();

            if ($userSocialNetwork->socialNetwork->key === 'Discord') {
                $user = user()->get(user()->id, true);

                app()->get(DiscordService::class)->linkRoles($user, $user->roles);
            }

            $auth['adapter']->disconnect();
            $auth['adapter']->getStorage()->clear();

            return $this->socialSuccess();
        } catch (UserNotFoundException $e) {
            return $this->socialError(__('auth.errors.user_not_found'));
        } catch (SocialNotFoundException $e) {
            return $this->socialError(__('auth.errors.social_not_found'));
        } catch (Exception $e) {
            logs()->error($e);

            if (is_debug()) {
                throw $e;
            }

            return $this->socialError(__('auth.errors.unknown'));
        }
    }

    /**
     * Unbinds the social network from the user's profile.
     */
    public function unbindSocial(FluteRequest $fluteRequest, string $provider): Response
    {
        $socialNetwork = UserSocialNetwork::findOne(['user_id' => user()->id, 'socialNetwork_id' => $provider]);

        $countSocialNetworks = UserSocialNetwork::query()->where(['user_id' => user()->id])->count();

        if (!$socialNetwork) {
            return redirect()->back()->withErrors(t('profile.errors.social_not_connected'));
        }

        if ($socialNetwork->socialNetwork === null) {
            return redirect()->back()->withErrors(t('profile.errors.social_not_connected'));
        }

        if ($countSocialNetworks === 1 && !$socialNetwork->user->password) {
            return redirect()->back()->withErrors(t('profile.errors.social_only_one'));
        }

        $lastLinked = $socialNetwork->linkedAt;
        $now = new DateTime();

        if ($socialNetwork->socialNetwork->cooldownTime > 0 && ($lastLinked && $now->getTimestamp() - $lastLinked->getTimestamp() < $socialNetwork->socialNetwork->cooldownTime)) {
            return redirect()->back()->withErrors(t('profile.errors.social_delay'));
        }

        transaction($socialNetwork, 'delete')->run();

        if ($provider === 'Discord') {
            app()->get(DiscordService::class)->clearRoles(user()->getCurrentUser());
        }

        return redirect()->back()->with('success', t('profile.s_social.social_disconnected'));
    }

    /**
     * Hides the social network in the user's profile.
     */
    public function hideSocial(FluteRequest $fluteRequest, string $provider): Response
    {
        try {
            $this->throttle("profile_change_hide_social");
        } catch (Exception $e) {
            return $this->error(__('auth.too_many_requests'));
        }

        $socialNetwork = UserSocialNetwork::findOne(['user_id' => user()->id, 'socialNetwork_id' => $provider]);

        if ($socialNetwork === null) {
            return redirect()->back()->withErrors(t('profile.errors.social_not_connected'));
        }

        $socialNetwork->hidden = !$socialNetwork->hidden;

        transaction($socialNetwork)->run();

        return $this->success();
    }

    /**
     * Returns an error response for social network authorization.
     */
    protected function socialError(string $error): Response
    {
        $redirectUrl = redirect('profile/edit?tab=social')->getTargetUrl();
        $errorJs = json_encode($error, JSON_UNESCAPED_UNICODE);
        $redirectUrlJs = json_encode($redirectUrl, JSON_UNESCAPED_SLASHES);

        return response()->make("<script>if (window.opener) { window.opener.postMessage('authorization_error:' + " . $errorJs . ", '*'); window.close(); } else { alert(" . $errorJs . "); window.location = " . $redirectUrlJs . "; }</script>");
    }

    /**
     * Returns a successful response for social network authorization.
     */
    protected function socialSuccess(): Response
    {
        $redirectUrl = redirect('profile/edit?tab=social')->getTargetUrl();
        $redirectUrlJs = json_encode($redirectUrl, JSON_UNESCAPED_SLASHES);

        return response()->make("<script>if (window.opener) { window.opener.postMessage('authorization_success', '*'); window.close(); } else { window.location = " . $redirectUrlJs . "; }</script>");
    }
}
