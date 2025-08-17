<?php

/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;

/**
 * Steam OpenID provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['secret' => 'steam-api-key']
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Steam($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class HttpsSteam extends OpenID
{
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'https://steamcommunity.com/openid';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://steamcommunity.com/dev';

    /**
     * {@inheritdoc}
     */
    public function authenticateFinish()
    {
        parent::authenticateFinish();

        $userProfile = $this->storage->get($this->providerId . '.user');

        $userProfile->identifier = str_ireplace([
            'http://steamcommunity.com/openid/id/',
            'https://steamcommunity.com/openid/id/',
        ], '', $userProfile->identifier);

        if (!$userProfile->identifier) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $apiKey = $this->resolveApiKey();

        if ($apiKey) {
            try {
                $result = $this->getUserProfileWebAPI($apiKey, $userProfile->identifier);

                if (empty($result['photoURL'])) {
                    $result = $this->getUserProfileLegacyAPI($userProfile->identifier);
                }
            } catch (\Exception $e) {
                logs()->error($e);

                $result = $this->getUserProfileLegacyAPI($userProfile->identifier);
            }
        } else {
            try {
                $result = $this->getUserProfileLegacyAPI($userProfile->identifier);
            } catch (\Exception $e) {
                logs()->error($e);
            }
        }

        foreach ($result as $k => $v) {
            $userProfile->$k = $v ?: $userProfile->$k;
        }

        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    /**
     * Fetch user profile on Steam web API
     *
     * @param $apiKey
     * @param $steam64
     *
     * @return array
     */
    public function getUserProfileWebAPI($apiKey, $steam64)
    {
        $q = http_build_query(['key' => $apiKey, 'steamids' => $steam64]);
        $apiUrl = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?' . $q;

        $response = $this->httpClient->request($apiUrl);

        $data = json_decode($response);

        $data = $data->response->players[0] ?? null;

        $data = new Data\Collection($data);

        $userProfile = [];

        $userProfile['displayName'] = (string)$data->get('personaname');
        $userProfile['firstName'] = (string)$data->get('realname');
        $userProfile['photoURL'] = (string)$data->get('avatarfull');
        $userProfile['profileURL'] = (string)$data->get('profileurl');
        $userProfile['country'] = (string)$data->get('loccountrycode');

        return $userProfile;
    }

    /**
     * Fetch user profile on community API
     * @param $steam64
     * @return array
     */
    public function getUserProfileLegacyAPI($steam64)
    {
        libxml_use_internal_errors(false);

        $apiUrl = 'https://steamcommunity.com/profiles/' . $steam64 . '/?xml=1';

        $response = $this->httpClient->request($apiUrl);

        $data = new \SimpleXMLElement($response);

        $data = new Data\Collection($data);

        $userProfile = [];

        $userProfile['displayName'] = (string)$data->get('steamID');
        $userProfile['firstName'] = (string)$data->get('realname');
        $userProfile['photoURL'] = (string)$data->get('avatarFull');
        $userProfile['description'] = (string)$data->get('summary');
        $userProfile['region'] = (string)$data->get('location');
        $userProfile['profileURL'] = (string)$data->get('customURL')
            ? 'https://steamcommunity.com/id/' . (string)$data->get('customURL')
            : 'https://steamcommunity.com/profiles/' . $steam64;

        return $userProfile;
    }

    /**
     * Resolve Steam API key from global config or provider-level settings.
     * This improves reliability when the key is stored in the social-network
     * settings instead of the generic config file.
     *
     * @return string|null
     */
    protected function resolveApiKey(): ?string
    {
        $key = config('app.steam_api');
        if ($key) {
            return $key;
        }

        $keys = (array) $this->config->get('keys');
        if (!empty($keys['secret'])) {
            return $keys['secret'];
        }

        foreach (['secret', 'apikey', 'api_key'] as $field) {
            $candidate = $this->config->get($field);
            if (!empty($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
