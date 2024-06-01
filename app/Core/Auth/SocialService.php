<?php

namespace Flute\Core\Auth;

use Exception;
use Flute\Core\Auth\Events\SocialProviderAddedEvent;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Exceptions\NeedRegistrationException;
use Flute\Core\Exceptions\SocialNotFoundException;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Hybridauth;
use Hybridauth\User\Profile;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Throwable;
use DateTime;
use DateInterval;

/**
 * Class SocialService
 * @package Flute\Core\Auth
 * Service class for handling social media authentication.
 */
class SocialService
{
    private $hybridauth;
    private $userRepository;
    private $socialNetworkRepository;
    private $userSocialNetworkRepository;
    private array $registeredProviders = [];

    /**
     * SocialService constructor.
     * Initialize repositories and register social media platforms.
     */
    public function __construct()
    {
        $this->userRepository = rep(User::class);
        $this->socialNetworkRepository = rep(SocialNetwork::class);
        $this->userSocialNetworkRepository = rep(UserSocialNetwork::class);

        $this->registerSocials();
    }

    /**
     * Register a social media platform.
     * @param SocialNetwork $socialNetwork
     * @throws JsonException
     */
    public function registerSocial(SocialNetwork $socialNetwork)
    {
        $this->registeredProviders[$this->replaceName($socialNetwork->key)] = array_merge([
            'enabled' => true,
            'entity' => $socialNetwork,
            // 'callback' => url("social/$socialNetwork->key")
        ], json_decode($socialNetwork->settings, true) ?? []);

        events()->dispatch(new SocialProviderAddedEvent($socialNetwork), SocialProviderAddedEvent::NAME);
    }

    /**
     * Register all enabled social media platforms.
     * @throws JsonException
     */
    public function registerSocials()
    {
        $providers = $this->socialNetworkRepository->findAll([
            'enabled' => true
        ]);

        foreach ($providers as $socialNetwork) {
            $this->registerSocial($socialNetwork);
        }
    }

    /**
     * Get all registered social providers
     * 
     * @return array
     */
    public function getAll(bool $onlyAllowed = true): array
    {
        return $onlyAllowed ? $this->getAllowedProviders() : $this->registeredProviders;
    }

    protected function getAllowedProviders()
    {
        $result = [];

        foreach( $this->registeredProviders as $key => $provider ) {
            if( $provider['entity']->allowToRegister === true ) {
                $result[$key] = $provider;
            }
        }

        return $result;
    }

    /**
     * If socials is empty
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->registeredProviders);
    }

    /**
     * Add and register a social
     *
     * @param string $key
     * @param array $settings
     * @param string $icon
     * @param bool $enabled
     *
     * @return SocialNetwork
     * @throws JsonException
     * @throws Throwable
     */
    public function addSocial(string $key, array $settings, string $icon, bool $enabled = true): SocialNetwork
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $key;
        $socialNetwork->settings = Json::encode($settings);
        $socialNetwork->icon = $icon;
        $socialNetwork->enabled = $enabled;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);

        return $socialNetwork;
    }

    /**
     * Authenticate a user using a specific social media platform.
     * @param string $socialNetworkName
     * @return User
     * @throws NeedRegistrationException
     * @throws SocialNotFoundException
     * @throws Exception
     */
    public function authenticateWithRegister(string $socialNetworkName): User
    {
        $social = $this->retrieveSocialNetwork($this->replaceName($socialNetworkName));
        
        if( $social['entity']->allowToRegister === false ) {
            throw new Exception(__('def.not_found'));
        }

        $userProfile = $this->authenticate($socialNetworkName);

        $userSocial = $this->userSocialNetworkRepository->select()->load(['user'])->where([
            'value' => $userProfile->identifier,
        ])->fetchOne();

        if ($userSocial)
            return $userSocial->user;

        if (app('auth.registration.social_supplement'))
            throw new NeedRegistrationException($userProfile);

        return $this->registerNewUser($userProfile, $social['entity']);
    }

    /**
     * Authenticate a user using a specific social media platform.
     * 
     * @param string $socialNetworkName
     * 
     * @throws NeedRegistrationException
     * @throws SocialNotFoundException
     * @throws Exception
     * 
     * @return mixed
     */
    public function authenticate(string $socialNetworkName, bool $bind = false)
    {
        $this->registerHybridAuth($socialNetworkName, $bind);

        $adapter = $this->hybridauth->authenticate($this->replaceName($socialNetworkName));

        $userProfile = $adapter->getUserProfile();

        try {
            $this->hybridauth->disconnectAllAdapters();
        } catch (InvalidApplicationCredentialsException $e) {
            logs()->error($e);
        }

        $adapter->disconnect();
        $adapter->getStorage()->clear();

        if (!$userProfile)
            throw new Exception('User profile load failed.');

        return $userProfile;
    }

    protected function replaceName(string $socialName)
    {
        if ($socialName === 'Steam') {
            return 'HttpsSteam';
        }

        return $socialName;
    }

    /**
     * Display the registered social media platforms.
     * @return array
     */
    public function toDisplay(): array
    {
        $result = [];

        foreach ($this->getAll() as $provider) {
            if ($provider['entity']->key === 'HttpsSteam') {
                $result['Steam'] = $provider['entity']->icon;
            } else {
                $result[$provider['entity']->key] = $provider['entity']->icon;
            }
        }

        return $result;
    }

    public function clearAuthData()
    {
        try {
            $this->registerHybridAuth();

            $this->hybridauth->disconnectAllAdapters();
        } catch (Exception $e) {
            // ignore stupid errors
        }
    }

    /**
     * Retrieve the information of a specific social media platform.
     * @param string $socialNetworkName
     * @return array
     * @throws SocialNotFoundException
     */
    public function retrieveSocialNetwork(string $socialNetworkName): array
    {
        if (!isset($this->registeredProviders[$socialNetworkName]))
            throw new SocialNotFoundException($socialNetworkName);

        return $this->registeredProviders[$socialNetworkName];
    }

    /**
     * Initialize Hybridauth with registered providers.
     * @throws InvalidArgumentException
     */
    public function registerHybridAuth(string $socialNetworkName = null, bool $bind = false): void
    {
        $this->hybridauth = new Hybridauth([
            'callback' => url($bind ? "profile/social/bind/$socialNetworkName" : "social/$socialNetworkName")->get(),
            'providers' => $this->registeredProviders
        ], null, new StorageSession);
    }

    /**
     * Register a new user with the given social media profile.
     * @param Profile $userProfile
     * @param SocialNetwork $socialNetwork
     * @return User
     * @throws Throwable
     */
    protected function registerNewUser(Profile $userProfile, SocialNetwork $socialNetwork): User
    {
        $email = $userProfile->email;

        // If user has a email, we just delete them
        if ($userProfile->email) {
            $findUser = $this->userRepository->select()->where([
                'email' => $userProfile->email
            ])->fetchOne();

            if (!empty($findUser)) {
                $email = null;
            }
        }

        $user = new User();
        $user->name = $userProfile->displayName;
        $user->email = !empty($email) ? $email : null;
        $user->uri = null;
        $user->login = null;
        $user->avatar = $userProfile->photoURL ?? config('profile.default_avatar');
        $user->banner = config('profile.default_banner');
        $user->verified = true;

        $userSocialNetwork = new UserSocialNetwork();
        $userSocialNetwork->value = $userProfile->identifier;
        $userSocialNetwork->url = $userProfile->profileURL;
        $userSocialNetwork->name = $userProfile->displayName;

        $userSocialNetwork->user = $user;
        $userSocialNetwork->socialNetwork = $socialNetwork;
        $userSocialNetwork->linkedAt = new DateTime();

        transaction([$user, $userSocialNetwork])->run();

        return $user;
    }

    /**
     * Bind a social network to an existing user.
     * 
     * @param User $user
     * @param string $socialNetworkName
     * @return void
     * @throws Exception
     */
    public function bindSocialNetwork(User $user, string $socialNetworkName): void
    {
        $userProfile = $this->authenticate($socialNetworkName, true);
        $social = $this->retrieveSocialNetwork($socialNetworkName);

        $userSocialNetwork = $this->userSocialNetworkRepository->select()->where([
            'user' => $user->id,
            'socialNetwork' => $social['entity']->id,
        ])->fetchOne();

        if ($userSocialNetwork) {
            $lastLinked = $userSocialNetwork->linkedAt;
            $now = new DateTime();

            if ($social['entity']->cooldownTime > 0 && ($lastLinked && $now->getTimestamp() - $lastLinked->getTimestamp() < $social['entity']->cooldownTime)) {
                throw new Exception(t('profile.errors.social_delay'));
            }

            $userSocialNetwork->value = $userProfile->identifier;
            $userSocialNetwork->url = $userProfile->profileURL;
            $userSocialNetwork->name = $userProfile->displayName;
            $userSocialNetwork->linkedAt = $now;

            transaction($userSocialNetwork)->run();
        } else {
            $userSocialNetwork = new UserSocialNetwork();
            $userSocialNetwork->value = $userProfile->identifier;
            $userSocialNetwork->url = $userProfile->profileURL;
            $userSocialNetwork->name = $userProfile->displayName;
            $userSocialNetwork->user = $user;
            $userSocialNetwork->socialNetwork = $social['entity'];
            $userSocialNetwork->linkedAt = new DateTime();

            transaction($userSocialNetwork)->run();
        }
    }
}
