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
 * Twitter (X) OAuth2 provider adapter.
 */
class Twitter extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'tweet.read users.read offline.access';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.twitter.com/2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://twitter.com/i/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.twitter.com/2/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.twitter.com/en/docs/authentication/oauth-2-0';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'code_challenge' => $this->generateCodeChallenge(),
            'code_challenge_method' => 'S256',
        ];

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
            ];
        }
    }

    /**
     * Generate PKCE code challenge
     */
    protected function generateCodeChallenge()
    {
        $codeVerifier = base64url_encode(random_bytes(32));
        session(['twitter_code_verifier' => $codeVerifier]);
        
        return base64url_encode(hash('sha256', $codeVerifier, true));
    }

    /**
     * {@inheritdoc}
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $codeVerifier = session('twitter_code_verifier');
        
        $this->tokenExchangeParameters += [
            'code_verifier' => $codeVerifier,
        ];

        session()->forget('twitter_code_verifier');

        return parent::exchangeCodeForAccessToken($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];

        $params = [
            'user.fields' => 'created_at,description,entities,id,location,name,pinned_tweet_id,profile_image_url,protected,public_metrics,url,username,verified,verified_type'
        ];

        $response = $this->apiRequest('users/me?' . http_build_query($params), 'GET', [], $headers);

        $data = new Data\Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userData = new Data\Collection($data->get('data'));

        $userProfile = new User\Profile();

        $userProfile->identifier = $userData->get('id');
        $userProfile->displayName = $userData->get('name');
        $userProfile->username = $userData->get('username');
        $userProfile->profileURL = 'https://twitter.com/' . $userData->get('username');
        $userProfile->description = $userData->get('description');
        $userProfile->region = $userData->get('location');

        if ($userData->get('profile_image_url')) {
            // Get higher resolution image
            $photoURL = str_replace('_normal', '_400x400', $userData->get('profile_image_url'));
            $userProfile->photoURL = $photoURL;
        }

        if ($userData->get('verified')) {
            $userProfile->data['verified'] = true;
        }

        // Twitter API v2 doesn't provide email in basic scope
        // Would need additional approval for email scope
        
        return $userProfile;
    }
}

if (!function_exists('base64url_encode')) {
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
