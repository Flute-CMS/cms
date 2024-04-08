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
        $this->registeredProviders[$socialNetwork->key] = array_merge([
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
    public function getAll(): array
    {
        return $this->registeredProviders;
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
        $userProfile = $this->authenticate($socialNetworkName);
        $social = $this->retrieveSocialNetwork($socialNetworkName);

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

        $adapter = $this->hybridauth->authenticate($socialNetworkName);

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

    /**
     * Display the registered social media platforms.
     * @return array
     */
    public function toDisplay(): array
    {
        $result = [];

        foreach ($this->registeredProviders as $provider) {
            $result[$provider['entity']->key] = $provider['entity']->icon;
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
        if (!$this->hybridauth)
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
        $user->email = $email;
        $user->avatar = $userProfile->photoURL ?? config('profile.default_avatar');
        $user->banner = config('profile.default_banner');
        $user->verified = true;

        $userSocialNetwork = new UserSocialNetwork();
        $userSocialNetwork->value = $userProfile->identifier;
        $userSocialNetwork->url = $userProfile->profileURL;
        $userSocialNetwork->name = $userProfile->displayName;

        $userSocialNetwork->user = $user;
        $userSocialNetwork->socialNetwork = $socialNetwork;

        transaction([$user, $userSocialNetwork])->run();

        return $user;
    }
}
