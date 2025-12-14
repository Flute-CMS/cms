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
 * Google OAuth2 provider adapter.
 */
class Google extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'openid profile email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.googleapis.com/oauth2/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth2.googleapis.com/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.google.com/identity/protocols/oauth2';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'access_type' => 'offline',
            'approval_prompt' => 'auto'
        ];

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
        ];

        $response = $this->apiRequest('userinfo', 'GET', [], $headers);

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->email = $data->get('email');
        $userProfile->profileURL = $data->get('link');
        $userProfile->language = $data->get('locale');
        $userProfile->gender = $data->get('gender');

        if ($data->get('picture')) {
            $userProfile->photoURL = $data->get('picture');
        }

        if ($data->get('verified_email')) {
            $userProfile->emailVerified = $data->get('email');
        }

        return $userProfile;
    }
}
