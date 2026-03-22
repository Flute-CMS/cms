<?php

/*!
 * Hybridauth
 * https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\HttpClient;
use Hybridauth\User;

/**
 * Minecraft OAuth2 provider adapter.
 *
 * Uses Microsoft OAuth2 → Xbox Live → XSTS → Minecraft Services chain
 * to verify Minecraft Java Edition account ownership.
 *
 * Config:
 *   'keys' => ['id' => 'azure-client-id', 'secret' => 'azure-client-secret']
 */
class Minecraft extends OAuth2
{
    protected $scope = 'XboxLive.signin offline_access';

    protected $apiBaseUrl = 'https://api.minecraftservices.com/';

    protected $authorizeUrl = 'https://login.live.com/oauth20_authorize.srf';

    protected $accessTokenUrl = 'https://login.live.com/oauth20_token.srf';

    protected $apiDocumentation = 'https://wiki.vg/Microsoft_Authentication_Scheme';

    public function getUserProfile()
    {
        $msAccessToken = $this->getStoredData('access_token');

        if (!$msAccessToken) {
            throw new UnexpectedApiResponseException('Missing Microsoft access token.');
        }

        // Step 1: Authenticate with Xbox Live
        $xblResponse = $this->httpPost('https://user.auth.xboxlive.com/user/authenticate', [
            'Properties' => [
                'AuthMethod' => 'RPS',
                'SiteName' => 'user.auth.xboxlive.com',
                'RpsTicket' => 'd=' . $msAccessToken,
            ],
            'RelyingParty' => 'http://auth.xboxlive.com',
            'TokenType' => 'JWT',
        ]);

        if (!isset($xblResponse['Token'])) {
            throw new UnexpectedApiResponseException('Xbox Live authentication failed.');
        }

        $xblToken = $xblResponse['Token'];
        $userHash = $xblResponse['DisplayClaims']['xui'][0]['uhs'] ?? null;

        if (!$userHash) {
            throw new UnexpectedApiResponseException('Xbox Live user hash not found.');
        }

        // Step 2: Get XSTS token
        $xstsResponse = $this->httpPost('https://xsts.auth.xboxlive.com/xsts/authorize', [
            'Properties' => [
                'SandboxId' => 'RETAIL',
                'UserTokens' => [$xblToken],
            ],
            'RelyingParty' => 'rp://api.minecraftservices.com/',
            'TokenType' => 'JWT',
        ]);

        if (!isset($xstsResponse['Token'])) {
            $xErr = $xstsResponse['XErr'] ?? 'unknown';

            throw new UnexpectedApiResponseException("XSTS authorization failed (XErr: {$xErr}).");
        }

        $xstsToken = $xstsResponse['Token'];

        // Step 3: Login with Xbox to Minecraft Services
        $mcLoginResponse = $this->httpPost('https://api.minecraftservices.com/authentication/login_with_xbox', [
            'identityToken' => "XBL3.0 x={$userHash};{$xstsToken}",
        ]);

        if (!isset($mcLoginResponse['access_token'])) {
            throw new UnexpectedApiResponseException('Minecraft authentication failed.');
        }

        $mcAccessToken = $mcLoginResponse['access_token'];

        // Step 4: Get Minecraft profile
        $mcProfile = $this->httpGet('https://api.minecraftservices.com/minecraft/profile', $mcAccessToken);

        if (!isset($mcProfile['id'])) {
            throw new UnexpectedApiResponseException(
                'Minecraft profile not found. The user may not own Minecraft Java Edition.',
            );
        }

        $uuid = $mcProfile['id'];
        $username = $mcProfile['name'];

        // Format UUID with dashes: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
        $formattedUuid = $this->formatUuid($uuid);

        $userProfile = new User\Profile();
        $userProfile->identifier = $formattedUuid;
        $userProfile->displayName = $username;
        $userProfile->profileURL = 'https://namemc.com/profile/' . $uuid;
        $userProfile->photoURL = 'https://crafatar.com/avatars/' . $uuid . '?overlay&size=128';

        // Store raw UUID and skin data
        $existingData = is_array($userProfile->data ?? null) ? $userProfile->data : [];
        $existingData['uuid_raw'] = $uuid;
        $existingData['username'] = $username;

        if (!empty($mcProfile['skins'])) {
            $activeSkin = null;
            foreach ($mcProfile['skins'] as $skin) {
                if (!empty($skin['state']) && $skin['state'] === 'ACTIVE') {
                    $activeSkin = $skin;

                    break;
                }
            }
            if ($activeSkin) {
                $existingData['skin_url'] = $activeSkin['url'] ?? null;
                $existingData['skin_variant'] = $activeSkin['variant'] ?? 'CLASSIC';
            }
        }

        $userProfile->data = $existingData;

        return $userProfile;
    }

    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }
    }

    /**
     * Get proxy curl options from provider config (if set).
     */
    private function getProxyCurlOptions(): array
    {
        if ($this->config->exists('curl_options')) {
            $opts = $this->config->get('curl_options');

            return array_intersect_key($opts, array_flip([CURLOPT_PROXY, CURLOPT_PROXYUSERPWD]));
        }

        return [];
    }

    /**
     * POST JSON to an external API and return decoded response.
     */
    private function httpPost(string $url, array $data): array
    {
        $json = json_encode($data);

        $curl = curl_init($url);
        curl_setopt_array($curl, array_replace([
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ], $this->getProxyCurlOptions()));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new UnexpectedApiResponseException("HTTP request failed: {$error}");
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new UnexpectedApiResponseException("Invalid JSON response (HTTP {$httpCode}).");
        }

        return $decoded;
    }

    /**
     * GET from an external API with Bearer token.
     */
    private function httpGet(string $url, string $bearerToken): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, array_replace([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $bearerToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ], $this->getProxyCurlOptions()));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new UnexpectedApiResponseException("HTTP request failed: {$error}");
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new UnexpectedApiResponseException("Invalid JSON response (HTTP {$httpCode}).");
        }

        return $decoded;
    }

    /**
     * Format a raw UUID (no dashes) into standard UUID format.
     */
    private function formatUuid(string $uuid): string
    {
        $uuid = str_replace('-', '', $uuid);

        if (strlen($uuid) !== 32) {
            return $uuid;
        }

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($uuid, 0, 8),
            substr($uuid, 8, 4),
            substr($uuid, 12, 4),
            substr($uuid, 16, 4),
            substr($uuid, 20, 12),
        );
    }
}
