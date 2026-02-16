<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\HttpClient\Util;
use Hybridauth\User\Profile;

/**
 * Telegram provider adapter using redirect-based OAuth flow.
 *
 * Uses https://oauth.telegram.org/auth for authentication instead of
 * the deprecated widget-based approach with exit().
 *
 * Config:
 *   'keys' => ['id' => 'bot_username', 'secret' => 'bot_token']
 */
class Telegram extends AbstractAdapter implements AdapterInterface
{
    protected $botId = '';

    protected $botSecret = '';

    protected $callbackUrl = '';

    protected $apiDocumentation = 'https://core.telegram.org/widgets/login';

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isCallbackWithAuthData()) {
            $this->authenticateCheckError();
            $this->authenticateFinish();
        } else {
            $this->authenticateBegin();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        $authData = $this->getStoredData('auth_data');

        return !empty($authData);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $data = new Collection($this->getStoredData('auth_data'));

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('username')
            ?: trim($data->get('first_name') . ' ' . $data->get('last_name'));
        $userProfile->photoURL = $data->get('photo_url');

        $username = $data->get('username');
        if (!empty($username)) {
            $userProfile->profileURL = "https://t.me/{$username}";
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->botId = $this->config->filter('keys')->get('id');
        $this->botSecret = $this->config->filter('keys')->get('secret');
        $this->callbackUrl = $this->config->get('callback');

        if (!$this->botId || !$this->botSecret) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
    }

    /**
     * Check whether the current request is a callback with Telegram auth data.
     */
    protected function isCallbackWithAuthData(): bool
    {
        return !empty($this->filterInput(INPUT_GET, 'hash'))
            && !empty($this->filterInput(INPUT_GET, 'auth_date'));
    }

    /**
     * Redirect user to Telegram OAuth page.
     */
    protected function authenticateBegin()
    {
        $botNumericId = $this->extractBotNumericId();
        $origin = $this->getOrigin();

        $params = [
            'bot_id' => $botNumericId,
            'origin' => $origin,
            'request_access' => 'write',
            'return_to' => $this->callbackUrl,
        ];

        $authUrl = 'https://oauth.telegram.org/auth?' . http_build_query($params);

        $this->logger->debug(
            sprintf('%s::authenticateBegin(), redirecting user to: %s', get_class($this), $authUrl)
        );

        Util::redirect($authUrl);
    }

    /**
     * Validate the hash from Telegram callback data.
     *
     * @see https://core.telegram.org/widgets/login#checking-authorization
     */
    protected function authenticateCheckError()
    {
        $authData = $this->parseAuthData();
        $checkHash = $authData['hash'];
        unset($authData['hash']);

        $dataCheckArr = [];
        foreach ($authData as $key => $value) {
            if ($value !== null && $value !== '') {
                $dataCheckArr[] = $key . '=' . $value;
            }
        }
        sort($dataCheckArr);

        $dataCheckString = implode("\n", $dataCheckArr);
        $secretKey = hash('sha256', $this->botSecret, true);
        $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($hash, $checkHash)) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is NOT from Telegram')
            );
        }

        if ((time() - (int) $authData['auth_date']) > 86400) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is outdated')
            );
        }
    }

    /**
     * Store auth data after successful validation.
     */
    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [Util::getCurrentUrl(true)]
        );

        $this->storeData('auth_data', $this->parseAuthData());

        $this->initialize();
    }

    /**
     * Parse auth data from GET parameters.
     */
    protected function parseAuthData(): array
    {
        return [
            'id' => $this->filterInput(INPUT_GET, 'id'),
            'first_name' => $this->filterInput(INPUT_GET, 'first_name'),
            'last_name' => $this->filterInput(INPUT_GET, 'last_name'),
            'username' => $this->filterInput(INPUT_GET, 'username'),
            'photo_url' => $this->filterInput(INPUT_GET, 'photo_url'),
            'auth_date' => $this->filterInput(INPUT_GET, 'auth_date'),
            'hash' => $this->filterInput(INPUT_GET, 'hash'),
        ];
    }

    /**
     * Extract the numeric bot ID from the bot token.
     * Token format: "123456789:ABCDEF..."
     */
    protected function extractBotNumericId(): string
    {
        $parts = explode(':', $this->botSecret);

        if (count($parts) >= 2 && is_numeric($parts[0])) {
            return $parts[0];
        }

        return $this->botId;
    }

    /**
     * Get the origin (scheme + host) from the callback URL.
     */
    protected function getOrigin(): string
    {
        $parsed = parse_url($this->callbackUrl);

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';

        return $scheme . '://' . $host;
    }
}
