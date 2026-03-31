<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * VK ID OAuth 2.1 provider adapter.
 *
 * Uses the new VK ID authorization endpoints (id.vk.ru) with mandatory PKCE.
 * Old oauth.vk.com / api.vk.com flow is deprecated and no longer supported by VK.
 *
 * @see https://id.vk.com/about/business/go/docs/ru/vkid/latest/vk-id/connection/api-description
 */
class Vkontakte extends OAuth2
{
    protected $scope = 'vkid.personal_info email';

    protected $apiBaseUrl = 'https://id.vk.ru/';

    protected $authorizeUrl = 'https://id.vk.ru/authorize';

    protected $accessTokenUrl = 'https://id.vk.ru/oauth2/auth';

    protected $apiDocumentation = 'https://id.vk.com/about/business/go/docs/ru/vkid/latest/vk-id/connection/api-description';

    protected $codeVerifier = null;

    protected $deviceId = null;

    protected function initialize()
    {
        parent::initialize();

        $this->scope = $this->config->exists('scope') ? $this->config->get('scope') : $this->scope;

        $this->codeVerifier = $this->generateCodeVerifier();
        $this->storeData('code_verifier', $this->codeVerifier);

        $state = $this->generateState();
        $this->storeData('authorization_state', $state);

        $this->AuthorizeUrlParameters = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callback,
            'scope' => $this->scope,
            'state' => $state,
            'code_challenge' => $this->generateCodeChallenge($this->codeVerifier),
            'code_challenge_method' => 'S256',
        ];

        $this->tokenExchangeParameters = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callback,
            'code_verifier' => $this->codeVerifier,
            'device_id' => '',
            'state' => $state,
        ];

        $this->tokenExchangeHeaders = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function authenticateFinish()
    {
        $inputType = $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET;

        $state = $this->filterInput($inputType, 'state');
        $code = $this->filterInput($inputType, 'code');
        $deviceId = $this->filterInput($inputType, 'device_id');

        if ($this->supportRequestState && ( !$state || $this->getStoredData('authorization_state') != $state )) {
            $this->deleteStoredData('authorization_state');
            throw new \Hybridauth\Exception\InvalidAuthorizationStateException(
                'The authorization state [state='
                . substr(htmlentities($state), 0, 100)
                . '] '
                . 'of this page is either invalid or has already been consumed.',
            );
        }

        $codeVerifier = $this->getStoredData('code_verifier');

        $this->tokenExchangeParameters['code'] = $code;
        $this->tokenExchangeParameters['code_verifier'] = $codeVerifier;
        $this->tokenExchangeParameters['device_id'] = $deviceId ?: '';
        $this->tokenExchangeParameters['state'] = $state;

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            'POST',
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders,
        );

        $this->validateApiResponse('Unable to exchange code for API access token');

        $this->validateAccessTokenExchange($response);

        if ($deviceId) {
            $this->storeData('device_id', $deviceId);
        }

        $this->initialize();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = ( new Data\Parser() )->parse($response);
        $collection = new Data\Collection($data);

        if ($collection->exists('error')) {
            throw new InvalidAccessTokenException(
                'Provider returned an error: '
                . $collection->get('error')
                . ' — '
                . ( $collection->get('error_description') ?? 'Unknown error' ),
            );
        }

        if (!$collection->exists('access_token')) {
            throw new InvalidAccessTokenException('Provider returned no access_token: ' . htmlentities($response));
        }

        $this->storeData('access_token', $collection->get('access_token'));
        $this->storeData('token_type', $collection->get('token_type'));

        if ($collection->get('refresh_token')) {
            $this->storeData('refresh_token', $collection->get('refresh_token'));
        }

        if ($collection->get('id_token')) {
            $this->storeData('id_token', $collection->get('id_token'));
        }

        if ($collection->exists('user_id')) {
            $this->storeData('user_id', $collection->get('user_id'));
        }

        if ($collection->exists('expires_in')) {
            $expires_at = time() + (int) $collection->get('expires_in');
            $this->storeData('expires_in', $collection->get('expires_in'));
            $this->storeData('expires_at', $expires_at);
        }

        $this->deleteStoredData('authorization_state');

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $accessToken = $this->getStoredData('access_token');

        $response = $this->httpClient->request(
            'https://id.vk.ru/oauth2/user_info',
            'POST',
            [
                'client_id' => $this->clientId,
                'access_token' => $accessToken,
            ],
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        );

        $data = ( new Data\Parser() )->parse($response);

        if (isset($data->error)) {
            throw new UnexpectedApiResponseException(
                $data->error_description ?? $data->error ?? 'Unknown VK ID API error',
            );
        }

        $userData = null;
        if (is_object($data) && isset($data->user)) {
            $userData = $data->user;
        } elseif (is_array($data) && isset($data['user'])) {
            $userData = (object) $data['user'];
        }

        if (!$userData) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $userData->user_id ?? $this->getStoredData('user_id');
        $userProfile->firstName = $userData->first_name ?? null;
        $userProfile->lastName = $userData->last_name ?? null;
        $userProfile->displayName = trim(( $userData->first_name ?? '' ) . ' ' . ( $userData->last_name ?? '' ));
        $userProfile->email = $userData->email ?? null;
        $userProfile->phone = $userData->phone ?? null;
        $userProfile->photoURL = $userData->avatar ?? null;

        if ($userProfile->identifier) {
            $userProfile->profileURL = 'https://vk.com/id' . $userProfile->identifier;
        }

        $sex = $userData->sex ?? null;
        if ($sex === 1) {
            $userProfile->gender = 'female';
        } elseif ($sex === 2) {
            $userProfile->gender = 'male';
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->clientId = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key');
        $this->clientSecret = $this->config->filter('keys')->get('secret');

        if (!$this->clientId) {
            throw new \Hybridauth\Exception\InvalidApplicationCredentialsException('Your application id is required in order to connect to '
            . $this->providerId);
        }

        $this->scope = $this->config->exists('scope') ? $this->config->get('scope') : $this->scope;

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }

        if ($this->config->exists('supportRequestState')) {
            $this->supportRequestState = $this->config->get('supportRequestState');
        }

        $this->setCallback($this->config->get('callback'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    private function generateCodeVerifier(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
        $length = random_int(43, 128);
        $verifier = '';

        for ($i = 0; $i < $length; $i++) {
            $verifier .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $verifier;
    }

    private function generateCodeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    private function generateState(): string
    {
        return bin2hex(random_bytes(20));
    }
}
