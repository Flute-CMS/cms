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
 * Vkontakte OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'scope' => 'email',
 *       'fields' => 'photo_max,screen_name',
 *       'version' => '5.131',
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Vkontakte($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage();
 *   }
 */
class Vkontakte extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.vk.com/method/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://oauth.vk.com/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.vk.com/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.vk.com/api/overview';

    /**
     * {@inheritdoc}
     */
    protected $userId = null;

    /**
     * User email.
     *
     * @var string
     */
    protected $userEmail = null;

    /**
     * VK API version.
     *
     * @var string
     */
    protected $apiVersion = '5.131';

    /**
     * Profile fields to request.
     *
     * @var string
     */
    protected $fields = 'photo_max,screen_name';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $parameters = [
            'user_ids' => $this->userId,
            'fields' => $this->fields,
            'v' => $this->apiVersion,
            'access_token' => $this->getStoredData('access_token'),
        ];

        $response = $this->apiRequest('users.get', 'GET', $parameters);

        if (isset($response->error)) {
            throw new UnexpectedApiResponseException($response->error->error_msg ?? 'Unknown VK API error');
        }

        $data = new Data\Collection($response);

        if (!$data->exists('response')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $users = $data->get('response');

        if (!is_array($users) || empty($users)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($users[0]);

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->email = $this->userEmail;
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('first_name') . ' ' . $data->get('last_name');
        $userProfile->photoURL = $data->get('photo_max');

        $screenName = $data->get('screen_name');
        if ($screenName) {
            $userProfile->profileURL = 'https://vk.com/' . $screenName;
        } else {
            $userProfile->profileURL = 'https://vk.com/id' . $data->get('id');
        }

        $sex = $data->get('sex');
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
    protected function initialize()
    {
        parent::initialize();

        $this->apiVersion = $this->config->get('version') ?: '5.131';
        $this->fields = $this->config->get('fields') ?: 'photo_max,screen_name';

        $this->AuthorizeUrlParameters += [
            'display' => $this->config->get('display') ?: 'page',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->userId = $collection->get('user_id');
        $this->userEmail = $collection->get('email');

        return $collection;
    }
}
