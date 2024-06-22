<?php

namespace Flute\Core\Steam;

use Zyberspace\SteamWebApi\Client;
use Zyberspace\SteamWebApi\Interfaces\ISteamUser;

class SteamParser
{
    protected Client $steamClient;
    protected ISteamUser $steamUser;
    protected const CACHE_KEY = 'flute.steam.cache.%s.%s';

    public function __construct()
    {
        $this->connect();
    }

    public function getUser( int $steamid64, bool $force = false ) : array
    {
        $data = $this->getUsers([$steamid64], $force);
        return !empty( $data ) ? $data : $data[0];
    }

    public function getUsers( array $steamIds, bool $force = false ) : array
    {
        $needsToGetItems = [];
        $cacheResults = [];
        $result = [];

        foreach ( $steamIds as $key => $steamid64 ) {
            $cacheItem = cache($this->cacheName( $steamid64, 'GetPlayerSummariesV2' ));

            if( $cacheItem && !$force ) {
                $cacheResults[$key] = $cacheItem;
            } else {
                $needsToGetItems[$key] = $steamid64;
            }
        }

        $result = $cacheResults;

        if( !empty( $needsToGetItems ) ) {
            $response = $this->steamUser->GetPlayerSummariesV2( implode(',', $needsToGetItems) );

            $players = $response->response->players;

            foreach( $needsToGetItems as $key => $steamid64 ) {
                $search = array_search($steamid64, array_column((array) $players, 'steamid'));

                if( isset( $players[$search] ) ) {
                    $result[$key] = $players[$search];

                    cache()->set($this->cacheName( $steamid64, 'GetPlayerSummariesV2' ), $players[$search], 3600);
                } else {
                    $result[$key] = [];
                }
            }
        }

        return $result;
    }

    public function steamid( $steam ) : \SteamID
    {
        return new \SteamID( $steam );
    }

    public function client() : ISteamUser
    {
        return $this->steamUser;
    }

    protected function cacheName( $steam, $method )
    {
        return sprintf(self::CACHE_KEY, $steam, $method );
    }

    protected function connect()
    {
        $steam = config('app.steam_api', null);

        if( $steam ) {
            $this->steamClient = new Client($steam);
            $this->steamUser = new ISteamUser($this->steamClient);
        } else {
            throw new \Exception('STEAM API key is not configured. Please set the key in the main settings.');
        }
    }
}