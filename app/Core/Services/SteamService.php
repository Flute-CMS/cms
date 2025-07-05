<?php

namespace Flute\Core\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use xPaw\SteamID\SteamID;
use Flute\Core\Cache\CacheManager;

class SteamService
{
    protected string $apiKey;
    protected Client $httpClient;
    protected int $cacheDuration;
    protected CacheManager $cache;

    protected static array $pendingSteamIds = [];
    protected static array $deferreds = [];
    protected static bool $isBatchScheduled = false;
    /**
     * IDs, accumulated during the script execution for the «deferred» request.
     * These IDs are requested in one batch in the shutdown function.
     */
    protected static array $collectedSteamIds = [];

    public function __construct(string $apiKey, CacheManager $cache)
    {
        $this->apiKey = $apiKey;
        $this->cache = $cache;

        $this->httpClient = new Client([
            'base_uri' => 'https://api.steampowered.com/',
            'timeout' => 10.0,
        ]);
        $this->cacheDuration = config('app.steam_cache_duration', 604800); // 7 days

        register_shutdown_function([$this, 'executeBatchRequest']);
    }

    /**
     * Normalize Steam ID for cache key
     */
    protected function normalizeSteamId(string $steamId) : string
    {
        $steamID = new SteamID($steamId);
        return $steamID->ConvertToUInt64();
    }

    /**
     * Get SteamID object
     * 
     * @param string $steamId
     * 
     * @return SteamID
     */
    public function steamid(string $steamId) : SteamID
    {
        return new SteamID($steamId);
    }

    /**
     * Get all available information about Steam user.
     *
     * @param string $steamId
     * @return PromiseInterface|\React\Promise\Promise
     */
    public function getUserInfo(string $steamId)
    {
        $steamID = new SteamID($steamId);
        $steam64 = $steamID->ConvertToUInt64();
        $cacheKey = "steam_user_info_{$steam64}";

        if ($cachedData = cache()->get($cacheKey)) {
            return new FulfilledPromise($cachedData);
        }

        if (isset(self::$deferreds[$steam64])) {
            return self::$deferreds[$steam64]->promise();
        }

        $deferred = new \React\Promise\Deferred();
        self::$deferreds[$steam64] = $deferred;
        self::$pendingSteamIds[] = $steam64;

        if (! self::$isBatchScheduled) {
            self::$isBatchScheduled = true;
            Utils::queue()->add([$this, 'executeBatchRequest']);
        }

        return $deferred->promise();
    }

    /**
     * Execute batch request to get Steam users information.
     *
     * @return void
     */
    public function executeBatchRequest() : void
    {
        if (empty(self::$collectedSteamIds)) {
            return;
        }

        $chunks = array_chunk(self::$collectedSteamIds, 100);
        self::$collectedSteamIds = [];

        foreach ($chunks as $chunk) {
            try {
                $response = $this->httpClient->get('ISteamUser/GetPlayerSummaries/v0002/', [
                    'query' => [
                        'key' => $this->apiKey,
                        'steamids' => implode(',', $chunk)
                    ]
                ]);

                $data = json_decode($response->getBody(), true);

                if (isset($data['response']['players'])) {
                    foreach ($data['response']['players'] as $player) {
                        $steamId = $player['steamid'];
                        $normalizedId = $this->normalizeSteamId($steamId);

                        $userInfo = [
                            'steamid' => $steamId,
                            'name' => $player['personaname'] ?? '',
                            'avatar' => $player['avatarfull'] ?? '',
                            'profile' => $player['profileurl'] ?? ''
                        ];

                        cache()->set("steam_user_{$normalizedId}", $userInfo, $this->cacheDuration);
                        cache()->set("steam_user_info_{$normalizedId}", $userInfo, $this->cacheDuration);
                    }
                }
            } catch (\Exception $e) {
                logs()->error('Steam API Batch Request Failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Get information about multiple Steam users in one request
     */
    public function getUsersInfo(array $steamIds) : array
    {
        if (empty($steamIds) || empty($this->apiKey)) {
            return [];
        }

        $steamIds = array_filter(array_unique($steamIds));

        $result = [];
        $uncachedIds = [];
        $steamIdMap = [];

        foreach ($steamIds as $steamId) {
            try {
                $normalizedId = $this->normalizeSteamId($steamId);
                $steamIdMap[$normalizedId] = $steamId;

                $cached = cache()->get("steam_user_{$normalizedId}");
                if ($cached !== null) {
                    $result[$steamId] = $cached;
                } else {
                    $uncachedIds[$normalizedId] = $steamId;
                }
            } catch (\Exception $e) {
                // invalid steam, ignore
            }
        }

        if (!empty($uncachedIds)) {
            // Запрашиваем пачками по 100 ID
            $chunks = array_chunk(array_keys($uncachedIds), 100);
            foreach ($chunks as $chunk) {
                try {
                    $response = $this->httpClient->get('ISteamUser/GetPlayerSummaries/v0002/', [
                        'query' => [
                            'key' => $this->apiKey,
                            'steamids' => implode(',', $chunk)
                        ]
                    ]);

                    $data = json_decode($response->getBody(), true);

                    if (isset($data['response']['players'])) {
                        foreach ($data['response']['players'] as $player) {
                            $steamId64 = $player['steamid'];
                            $normalizedId = $this->normalizeSteamId($steamId64);

                            $userInfo = [
                                'steamid' => $steamId64,
                                'name'    => $player['personaname'] ?? '',
                                'avatar'  => $player['avatarfull'] ?? '',
                                'profile' => $player['profileurl'] ?? '',
                            ];

                            cache()->set("steam_user_{$normalizedId}", $userInfo, $this->cacheDuration);

                            $originalId = $steamIdMap[$normalizedId] ?? $steamId64;
                            $result[$originalId] = $userInfo;
                            unset($uncachedIds[$normalizedId]);
                        }
                    }
                } catch (\Exception $e) {
                    logs()->error("Steam API Error: " . $e->getMessage());
                }
            }
        }

        foreach ($uncachedIds as $normalizedId => $originalId) {
            $result[$originalId] = [];
        }

        return $result;
    }

    /**
     * Get Steam user information immediately
     */
    public function getUserInfoImmediately(string $steamId) : ?array
    {
        $result = $this->getUsersInfo([$steamId]);
        return $result[$steamId] ?? null;
    }

    /**
     * Get display name of Steam user.
     *
     * @param string $steamId
     * @return PromiseInterface
     */
    public function getUserName(string $steamId) : PromiseInterface
    {
        return $this->getUserInfo($steamId)->then(function ($userInfo) {
            return $userInfo['personaname'] ?? null;
        });
    }

    /**
     * Get URL of Steam user avatar.
     *
     * @param string $steamId
     * @return PromiseInterface
     */
    public function getUserAvatar(string $steamId) : PromiseInterface
    {
        return $this->getUserInfo($steamId)->then(function ($userInfo) {
            return $userInfo['avatarfull'] ?? null;
        });
    }

    /**
     * Refresh cached user information
     */
    public function refreshUserInfo(string $steamId) : void
    {
        $normalizedId = $this->normalizeSteamId($steamId);
        cache()->delete("steam_user_{$normalizedId}");
    }

    /**
     * Immediately execute batch request and wait for its completion.
     *
     * This is useful when you need to get user information immediately.
     *
     * @return void
     */
    public function flushBatch() : void
    {
        if (self::$isBatchScheduled) {
            $this->executeBatchRequest();
            Utils::queue()->run();
        }
    }
}
