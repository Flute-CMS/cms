<?php

namespace Flute\Core\Services;

use DateInterval;
use DateTime;
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

        if (!empty($discordInfo['token']) && !empty($discordInfo['guild_id'])) {
            return $this->linkRolesViaBot($user, $roles, $discordInfo);
        }

        return $this->linkRolesViaRoleConnections($user, $roles, $discordInfo);
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
                    'expires_at' => (new DateTime())->add(new DateInterval('PT' . $data['expires_in'] . 'S')),
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

    /**
     * Sync roles using Discord Role Connections (user OAuth access token)
     */
    protected function linkRolesViaRoleConnections(User $user, array $roles, array $discordInfo)
    {
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

    /**
     * Sync roles using Bot token at Guild level
     */
    protected function linkRolesViaBot(User $user, array $roles, array $discordInfo)
    {
        $guildId = $discordInfo['guild_id'];
        $botToken = $discordInfo['token'];

        $userSocial = $user->getSocialNetwork('Discord');
        if (!$userSocial) {
            return false;
        }

        $discordUserId = $userSocial->value;

        $rolesMap = isset($discordInfo['roles_map']) && is_array($discordInfo['roles_map'])
            ? $discordInfo['roles_map']
            : [];

        $targetGuildRoleIds = [];
        foreach ($roles as $role) {
            $fluteRoleId = $role->id;
            if (isset($rolesMap[$fluteRoleId])) {
                $targetGuildRoleIds[] = (string) $rolesMap[$fluteRoleId];
            }
        }

        $targetGuildRoleIds = array_values(array_unique($targetGuildRoleIds));

        $currentRoles = [];

        try {
            $response = $this->client->request('GET', self::BASE_URL . "/guilds/{$guildId}/members/{$discordUserId}", [
                'headers' => [
                    'Authorization' => 'Bot ' . $botToken,
                    'Accept' => 'application/json',
                ],
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            $currentRoles = isset($data['roles']) && is_array($data['roles']) ? $data['roles'] : [];
        } catch (ClientException $e) {
            if (method_exists($e, 'getResponse') && $e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
                logs()->warning('Discord member not found in guild for user #' . $user->id . ' (Discord ID ' . $discordUserId . '). Skipping bot role sync.');

                return false;
            }
            logs()->error($e);
            if (is_debug()) {
                throw $e;
            }
        }

        $managedRoleIds = array_values(array_unique(array_map('strval', array_values($rolesMap))));
        $currentManaged = array_values(array_intersect($currentRoles, $managedRoleIds));
        $toAdd = array_values(array_diff($targetGuildRoleIds, $currentManaged));
        $toRemove = array_values(array_diff($currentManaged, $targetGuildRoleIds));

        foreach ($toRemove as $discordRoleId) {
            try {
                $this->client->request('DELETE', self::BASE_URL . "/guilds/{$guildId}/members/{$discordUserId}/roles/{$discordRoleId}", [
                    'headers' => [
                        'Authorization' => 'Bot ' . $botToken,
                        'Accept' => 'application/json',
                    ],
                ]);
                usleep(250000);
            } catch (ClientException $e) {
                logs()->error($e);
                if (is_debug()) {
                    throw $e;
                }
            }
        }

        foreach ($toAdd as $discordRoleId) {
            try {
                $this->client->request('PUT', self::BASE_URL . "/guilds/{$guildId}/members/{$discordUserId}/roles/{$discordRoleId}", [
                    'headers' => [
                        'Authorization' => 'Bot ' . $botToken,
                        'Accept' => 'application/json',
                    ],
                ]);
                usleep(250000);
            } catch (ClientException $e) {
                logs()->error($e);
                if (is_debug()) {
                    throw $e;
                }
            }
        }

        return true;
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
            if ((new DateTime())->getTimestamp() > (int) $additional['expires_at']) {
                return $this->refreshAccessToken($additional['refresh_token'], $user->id);
            }
        } else {
            if (new DateTime() > new DateTime($additional['expires_at']['date'])) {
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
