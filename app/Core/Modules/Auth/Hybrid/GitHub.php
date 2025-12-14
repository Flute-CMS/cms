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
 * GitHub OAuth2 provider adapter.
 */
class GitHub extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user:email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.github.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://github.com/login/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://github.com/login/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.github.com/en/developers/apps/building-oauth-apps/authorizing-oauth-apps';

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

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'Flute-CMS'
        ];

        $response = $this->apiRequest('user', 'GET', [], $headers);

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name') ?: $data->get('login');
        $userProfile->username = $data->get('login');
        $userProfile->profileURL = $data->get('html_url');
        $userProfile->description = $data->get('bio');
        $userProfile->webSiteURL = $data->get('blog');
        $userProfile->region = $data->get('location');

        if ($data->get('avatar_url')) {
            $userProfile->photoURL = $data->get('avatar_url');
        }

        // Get primary email
        try {
            $emailResponse = $this->apiRequest('user/emails', 'GET', [], $headers);
            if (is_array($emailResponse)) {
                foreach ($emailResponse as $email) {
                    $emailData = new Data\Collection($email);
                    if ($emailData->get('primary')) {
                        $userProfile->email = $emailData->get('email');
                        if ($emailData->get('verified')) {
                            $userProfile->emailVerified = $emailData->get('email');
                        }
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // If we can't get email, continue without it
        }

        return $userProfile;
    }
}
