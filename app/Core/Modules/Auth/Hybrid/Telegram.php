<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;
use OpenSSLAsymmetricKey;

/**
 * Telegram OAuth 2.0 / OpenID Connect provider adapter.
 *
 * Uses the new OAuth 2.0 Authorization Code Flow with PKCE.
 * Validates id_token RS256 signature using Telegram's JWKS endpoint via OpenSSL.
 *
 * @see https://core.telegram.org/bots/telegram-login
 *
 * Config:
 *   'keys' => ['id' => 'client_id (numeric bot ID)', 'secret' => 'client_secret (from BotFather)']
 */
class Telegram extends OAuth2
{
    protected const JWKS_URL = 'https://oauth.telegram.org/.well-known/jwks.json';

    protected const EXPECTED_ISSUER = 'https://oauth.telegram.org';

    protected $scope = 'openid profile';

    protected $apiBaseUrl = 'https://oauth.telegram.org/';

    protected $authorizeUrl = 'https://oauth.telegram.org/auth';

    protected $accessTokenUrl = 'https://oauth.telegram.org/token';

    protected $apiDocumentation = 'https://core.telegram.org/bots/telegram-login';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $claims = $this->getStoredData('id_token_claims');

        if (!is_array($claims) || empty($claims['sub'])) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $claims['sub'];
        $userProfile->displayName = $claims['name'] ?? null;
        $userProfile->photoURL = $claims['picture'] ?? null;
        $userProfile->phone = $claims['phone_number'] ?? null;

        $username = $claims['preferred_username'] ?? null;
        if (!empty($username)) {
            $userProfile->profileURL = "https://t.me/{$username}";
        }

        if (empty($userProfile->displayName)) {
            $parts = array_filter([
                $claims['first_name'] ?? null,
                $claims['last_name'] ?? null,
            ]);
            $userProfile->displayName = implode(' ', $parts) ?: $username;
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if (!$this->getStoredData('code_verifier')) {
            $codeVerifier = $this->generateCodeVerifier();
            $this->storeData('code_verifier', $codeVerifier);
        }

        $codeVerifier = $this->getStoredData('code_verifier');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $this->AuthorizeUrlParameters['code_challenge'] = $codeChallenge;
        $this->AuthorizeUrlParameters['code_challenge_method'] = 'S256';

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
        ];

        unset($this->tokenExchangeParameters['client_id'], $this->tokenExchangeParameters['client_secret']);
    }

    /**
     * {@inheritdoc}
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $this->tokenExchangeParameters['code'] = $code;

        $codeVerifier = $this->getStoredData('code_verifier');
        if ($codeVerifier) {
            $this->tokenExchangeParameters['code_verifier'] = $codeVerifier;
        }

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders,
        );

        $this->validateApiResponse('Unable to exchange code for API access token');

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $idToken = $collection->get('id_token');
        if (!$idToken) {
            throw new InvalidAccessTokenException('Provider returned no id_token.');
        }

        $claims = $this->verifyAndDecodeIdToken($idToken);
        $this->storeData('id_token_claims', $claims);

        return $collection;
    }

    /**
     * Verify the id_token RS256 signature using Telegram's JWKS and validate claims.
     *
     * @throws InvalidAccessTokenException
     */
    protected function verifyAndDecodeIdToken(string $idToken): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new InvalidAccessTokenException('Malformed id_token.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $header = json_decode($this->base64UrlDecode($headerB64), true);
        if (!is_array($header) || ( $header['alg'] ?? null ) !== 'RS256') {
            throw new InvalidAccessTokenException('Unsupported id_token algorithm.');
        }

        $kid = $header['kid'] ?? null;
        $publicKey = $this->fetchJwksPublicKey($kid);

        $signature = $this->base64UrlDecode($signatureB64);
        $data = $headerB64 . '.' . $payloadB64;

        $valid = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) {
            throw new InvalidAccessTokenException('id_token signature verification failed.');
        }

        $decoded = json_decode($this->base64UrlDecode($payloadB64), true);
        if (!is_array($decoded)) {
            throw new InvalidAccessTokenException('Unable to decode id_token payload.');
        }

        if (( $decoded['iss'] ?? null ) !== static::EXPECTED_ISSUER) {
            throw new InvalidAccessTokenException('id_token issuer mismatch: expected ' . static::EXPECTED_ISSUER);
        }

        if (( $decoded['aud'] ?? null ) !== $this->clientId) {
            throw new InvalidAccessTokenException('id_token audience mismatch: expected ' . $this->clientId);
        }

        if (isset($decoded['exp']) && (int) $decoded['exp'] < time()) {
            throw new InvalidAccessTokenException('id_token has expired.');
        }

        return $decoded;
    }

    /**
     * Fetch the RSA public key from Telegram's JWKS endpoint.
     *
     * @throws InvalidAccessTokenException
     */
    protected function fetchJwksPublicKey(?string $kid): OpenSSLAsymmetricKey
    {
        $jwksJson = $this->httpClient->request(static::JWKS_URL);
        $jwks = json_decode($jwksJson, true);

        if (!is_array($jwks) || empty($jwks['keys'])) {
            throw new InvalidAccessTokenException('Unable to fetch Telegram JWKS keys.');
        }

        $jwk = null;
        foreach ($jwks['keys'] as $key) {
            if ($kid !== null && ( $key['kid'] ?? null ) === $kid) {
                $jwk = $key;

                break;
            }
        }

        if (!$jwk) {
            $jwk = $jwks['keys'][0];
        }

        if (( $jwk['kty'] ?? null ) !== 'RSA' || empty($jwk['n']) || empty($jwk['e'])) {
            throw new InvalidAccessTokenException('Invalid JWK key in Telegram JWKS.');
        }

        $pem = $this->rsaJwkToPem($jwk['n'], $jwk['e']);
        $key = openssl_pkey_get_public($pem);

        if (!$key) {
            throw new InvalidAccessTokenException('Failed to parse RSA public key from JWKS.');
        }

        return $key;
    }

    /**
     * Convert RSA JWK modulus and exponent to PEM format.
     */
    protected function rsaJwkToPem(string $n, string $e): string
    {
        $modulus = $this->base64UrlDecode($n);
        $exponent = $this->base64UrlDecode($e);

        $modulus = ltrim($modulus, "\x00");
        if (ord($modulus[0]) > 0x7f) {
            $modulus = "\x00" . $modulus;
        }

        $components = $this->asn1Sequence(
            $this->asn1Sequence($this->asn1ObjectIdentifier("\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01") . "\x05\x00")
                . $this->asn1BitString($this->asn1Sequence(
                    $this->asn1UnsignedInteger($modulus) . $this->asn1UnsignedInteger($exponent),
                )), // rsaEncryption // NULL
        );

        return (
            "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($components), 64, "\n")
            . "-----END PUBLIC KEY-----\n"
        );
    }

    /**
     * Generate a cryptographically random code verifier for PKCE.
     */
    protected function generateCodeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * Decode a base64url-encoded string.
     */
    protected function base64UrlDecode(string $input): string
    {
        $decoded = base64_decode(strtr($input, '-_', '+/'));

        if ($decoded === false) {
            return '';
        }

        return $decoded;
    }

    protected function asn1Length(string $data): string
    {
        $len = strlen($data);
        if ($len < 0x80) {
            return chr($len);
        }

        $lenBytes = '';
        $temp = $len;
        while ($temp > 0) {
            $lenBytes = chr($temp & 0xff) . $lenBytes;
            $temp >>= 8;
        }

        return chr(0x80 | strlen($lenBytes)) . $lenBytes;
    }

    protected function asn1Sequence(string $data): string
    {
        return "\x30" . $this->asn1Length($data) . $data;
    }

    protected function asn1UnsignedInteger(string $data): string
    {
        return "\x02" . $this->asn1Length($data) . $data;
    }

    protected function asn1BitString(string $data): string
    {
        return "\x03" . $this->asn1Length("\x00" . $data) . "\x00" . $data;
    }

    protected function asn1ObjectIdentifier(string $oid): string
    {
        return "\x06" . $this->asn1Length($oid) . $oid;
    }
}
