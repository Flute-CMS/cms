<?php

/*!
 * Hybridauth
 * https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * Twitch OAuth2 provider adapter.
 */
class Twitch extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user:read:email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.twitch.tv/helix/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://id.twitch.tv/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://id.twitch.tv/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.twitch.tv/docs/authentication/';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Client-Id' => $this->clientId,
        ];

        $response = $this->apiRequest('users', 'GET', [], $headers);

        $data = new Data\Collection($response);

        if (!$data->exists('data') || !is_array($data->get('data')) || empty($data->get('data'))) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userData = new Data\Collection($data->get('data')[0]);

        $userProfile = new User\Profile();

        $userProfile->identifier = $userData->get('id');
        $userProfile->displayName = $userData->get('display_name') ?: $userData->get('login');
        $userProfile->email = $userData->get('email');
        $userProfile->profileURL = 'https://www.twitch.tv/' . $userData->get('login');
        $userProfile->description = $userData->get('description');

        if ($userData->get('profile_image_url')) {
            $userProfile->photoURL = $userData->get('profile_image_url');
        }

        if ($userData->get('email')) {
            $userProfile->emailVerified = $userData->get('email');
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }
    }
}
