<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class DiscordService
{
    protected const BASE_URL = 'https://discord.com/api/v10';
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function clearRoles(User $user)
    {
        $this->linkRoles($user, []);
    }

    public function linkRoles(User $user, array $roles = [])
    {
        if (!config('app.discord_link_roles')) {
            return;
        }

        $discordInfo = $this->getDiscordInfo();

        if (!$discordInfo) {
            return false;
        }

        $clientId = $discordInfo['id'];
        $url = self::BASE_URL . "/users/@me/applications/{$clientId}/role-connection";
        $accessToken = $this->getUserAccessToken($user);

        if (!$accessToken) {
            return false;
        }

        if (sizeof($roles) > 0) {
            foreach ($roles as $key => $role) {
                try {
                    $this->client->request('PUT', $url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Accept' => 'application/json',
                        ],
                        'json' => [
                            'platform_name' => config('app.name'),
                            'metadata' => [
                                'role_id' => $role->id,
                            ],
                        ],
                    ]);

                    if (isset($roles[$key + 1])) {
                        sleep(5);
                    }
                } catch (ClientException $e) {
                    logs()->error($e);

                    if (is_debug()) {
                        throw $e;
                    }
                }
            }
        } else {
            try {
                $this->client->request('PUT', $url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'platform_name' => config('app.name'),
                        'metadata' => [
                            'role_id' => 0,
                        ],
                    ],
                ]);
            } catch (ClientException $e) {
                logs()->error($e);

                if (is_debug()) {
                    throw $e;
                }
            }
        }

        return true;
    }

    public function refreshAccessToken(string $refreshToken, int $userId)
    {
        if (!config('app.discord_link_roles')) {
            return;
        }

        $discordInfo = $this->getDiscordInfo();

        if (!$discordInfo) {
            return false;
        }

        try {
            $response = $this->client->request('POST', self::BASE_URL . '/oauth2/token', [
                'form_params' => [
                    'client_id' => $discordInfo['id'],
                    'client_secret' => $discordInfo['secret'],
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
            ]);
        } catch (ClientException $e) {
            logs()->error($e);

            return false;
        }

        $data = json_decode($response->getBody()->getContents(), true);

        $user = user()->get($userId);

        if ($user) {
            if ($social = $user->getSocialNetwork('Discord')) {
                $social->additional = json_encode([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_at' => (new \DateTime())->add(new \DateInterval('PT' . $data['expires_in'] . 'S')),
                ]);

                transaction($user)->run();
            }
        }

        return $data['access_token'];
    }

    public function registerMetadata()
    {
        if (!config('app.discord_link_roles')) {
            return false;
        }

        if (cache()->has('flute.discord_roles_metadata')) {
            return;
        }

        $discordInfo = $this->getDiscordInfo();

        if (!$discordInfo) {
            return false;
        }

        $url = "https://discord.com/api/v10/applications/{$discordInfo['id']}/role-connections/metadata";

        try {
            $response = $this->client->request('PUT', $url, [
                'headers' => [
                    'Authorization' => 'Bot ' . $discordInfo['token'],
                    'Accept' => 'application/json',
                ],
                'json' => [
                    [
                        'key' => 'role_id',
                        'name' => __('def.discord_role_id'),
                        'description' => __('def.discord_role_desc'),
                        'type' => 2,
                    ],
                ],
            ]);
        } catch (ClientException $e) {
            logs()->error($e);

            return false;
        }

        cache()->set('flute.discord_roles_metadata', 'true');

        return $response->getStatusCode() === 200;
    }

    protected function getDiscordInfo()
    {
        $socialNetwork = $this->getSocialNetworkByKey("Discord");

        if (!$socialNetwork) {
            return false;
        }

        $settings = $socialNetwork->getSettings();

        if (!isset($settings['token'])) {
            return false;
        }

        return $settings;
    }

    protected function getUserAccessToken(User $user)
    {
        if (!$user->getSocialNetwork('Discord')) {
            return false;
        }

        $additional = json_decode($user->getSocialNetwork('Discord')->additional, true);

        if (is_int($additional['expires_at'])) {
            if ((new \DateTime())->getTimestamp() > (int) $additional['expires_at']) {
                return $this->refreshAccessToken($additional['refresh_token'], $user->id);
            }
        } else {
            if (new \DateTime() > new \DateTime($additional['expires_at']['date'])) {
                return $this->refreshAccessToken($additional['refresh_token'], $user->id);
            }
        }

        return $additional['access_token'];
    }

    protected function getSocialNetworkByKey(string $key): ?SocialNetwork
    {
        return SocialNetwork::findOne(['key' => $key]);
    }
}
