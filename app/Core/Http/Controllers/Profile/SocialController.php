<?php

namespace Flute\Core\Http\Controllers\Profile;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\DiscordLink\DiscordLinkRoles;
use Flute\Core\Exceptions\SocialNotFoundException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\Response;

class SocialController extends AbstractController
{
    /**
     * Привязывает социальную сеть к профилю пользователя.
     *
     * @param FluteRequest $fluteRequest
     * @param string $provider
     * @return Response
     */
    public function bindSocial(FluteRequest $fluteRequest, string $provider): Response
    {
        try {
            $auth = social()->authenticate(ucfirst($provider), true);
            $token = $auth['adapter']->getAccessToken();

            $userProfile = $auth['profile'];

            $duplicateCheck = rep(UserSocialNetwork::class)->select()->load('user')->where([
                'value' => $userProfile->identifier,
            ])->fetchOne();

            if ($duplicateCheck) {
                return $this->socialError(__('profile.errors.social_binded'));
            }

            $userSocialNetwork = new UserSocialNetwork();
            $userSocialNetwork->value = $userProfile->identifier;
            $userSocialNetwork->url = $userProfile->profileURL;
            $userSocialNetwork->name = $userProfile->displayName;

            $userSocialNetwork->user = user()->getCurrentUser();
            $userSocialNetwork->socialNetwork = rep(SocialNetwork::class)->findOne(['key' => ucfirst($provider)]);

            if ($token) {
                $userSocialNetwork->additional = json_encode($token);
            }

            transaction($userSocialNetwork)->run();

            if ($userSocialNetwork->socialNetwork->key === 'Discord') {
                $user = user()->get(user()->id, true);

                app()->get(DiscordLinkRoles::class)->linkRoles($user, $user->getRoles()->toArray());
            }

            $auth['adapter']->disconnect();
            $auth['adapter']->getStorage()->clear();

            user()->log('profile.bind_social', $provider);

            return $this->socialSuccess();
        } catch (UserNotFoundException $e) {
            return $this->socialError(__('auth.errors.user_not_found'));
        } catch (SocialNotFoundException $e) {
            return $this->socialError(__('auth.errors.social_not_found'));
        } catch (\Exception $e) {
            logs()->error($e);

            if (is_debug()) {
                throw $e;
            }

            return $this->socialError(__('auth.errors.unknown'));
        }
    }

    /**
     * Отвязывает социальную сеть от профиля пользователя.
     *
     * @param FluteRequest $fluteRequest
     * @param string $provider
     * @return Response
     */
    public function unbindSocial(FluteRequest $fluteRequest, string $provider): Response
    {
        $repository = rep(UserSocialNetwork::class);

        $socialNetwork = $repository->select()->load(['user', 'socialNetwork'])->where([
            'user.id' => user()->id,
            'socialNetwork.key' => $provider
        ])->fetchOne();

        $countSocialNetworks = $repository->select()->load(['user'])->where([
            'user.id' => user()->id,
        ])->count();

        if (!$socialNetwork) {
            return redirect()->back()->withErrors(t('profile.errors.social_not_connected'));
        }

        if ($countSocialNetworks === 1 && !$socialNetwork->user->password) {
            return redirect()->back()->withErrors(t('profile.errors.social_only_one'));
        }

        $lastLinked = $socialNetwork->linkedAt;
        $now = new \DateTime();

        if ($socialNetwork->socialNetwork->cooldownTime > 0 && ($lastLinked && $now->getTimestamp() - $lastLinked->getTimestamp() < $socialNetwork->socialNetwork->cooldownTime)) {
            return redirect()->back()->withErrors(t('profile.errors.social_delay'));
        }

        user()->log('profile.unbind_social', $provider);

        transaction($socialNetwork, 'delete')->run();

        if ($provider === 'Discord')
            app()->get(DiscordLinkRoles::class)->clearRoles(user()->getCurrentUser());

        return redirect()->back()->with('success', t('profile.s_social.social_disconnected'));
    }

    /**
     * Скрывает социальную сеть в профиле пользователя.
     *
     * @param FluteRequest $fluteRequest
     * @param string $provider
     * @return Response
     */
    public function hideSocial(FluteRequest $fluteRequest, string $provider): Response
    {
        try {
            $this->throttle("profile_change_hide_social");
        } catch (\Exception $e) {
            return $this->error(__('auth.too_many_requests'));
        }

        $repository = rep(UserSocialNetwork::class);

        $socialNetwork = $repository->select()->load(['user', 'socialNetwork'])->where([
            'user.id' => user()->id,
            'socialNetwork.key' => $provider
        ])->fetchOne();

        $socialNetwork->hidden = !$socialNetwork->hidden;

        user()->log('profile.hide_social', $provider);

        transaction($socialNetwork)->run();

        return $this->success();
    }

    /**
     * Возвращает ответ с ошибкой авторизации через социальную сеть.
     *
     * @param string $error
     * @return Response
     */
    protected function socialError(string $error): Response
    {
        return response()->make("<script>window.opener.postMessage('authorization_error:' + '$error', '*');window.close();</script>");
    }

    /**
     * Возвращает успешный ответ об авторизации через социальную сеть.
     *
     * @return Response
     */
    protected function socialSuccess(): Response
    {
        return response()->make("<script>window.opener.postMessage('authorization_success', '*');window.close();</script>");
    }
}
