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
 * Yandex OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Yandex($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage();
 *   }
 */
class Yandex extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://login.yandex.ru/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://oauth.yandex.ru/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.yandex.ru/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://yandex.ru/dev/id/doc/dg/concepts/about.html';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenName = 'oauth_token';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->tokenExchangeHeaders = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('info', 'GET', ['format' => 'json']);

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('display_name') ?: $data->get('real_name');
        $userProfile->email = $data->get('default_email');
        $userProfile->emailVerified = $data->get('default_email');

        // Avatar URL
        $defaultAvatarId = $data->get('default_avatar_id');
        if ($defaultAvatarId && !$data->get('is_avatar_empty')) {
            $userProfile->photoURL = 'https://avatars.yandex.net/get-yapic/' . $defaultAvatarId . '/islands-200';
        }

        // Gender
        $sex = $data->get('sex');
        if ($sex === 'male') {
            $userProfile->gender = 'male';
        } elseif ($sex === 'female') {
            $userProfile->gender = 'female';
        }

        // Birthday
        $birthday = $data->get('birthday');
        if ($birthday) {
            $parts = explode('-', $birthday);
            if (count($parts) === 3) {
                $userProfile->birthYear = (int) $parts[0];
                $userProfile->birthMonth = (int) $parts[1];
                $userProfile->birthDay = (int) $parts[2];
            }
        }

        // Login as profile URL
        $login = $data->get('login');
        if ($login) {
            $userProfile->profileURL = 'https://yandex.ru/user/' . $login;
        }

        return $userProfile;
    }
}
