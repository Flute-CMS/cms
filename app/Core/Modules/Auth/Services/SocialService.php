<?php

namespace Flute\Core\Modules\Auth\Services;

use Exception;
use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Flute\Core\Services\DiscordService;
use Flute\Core\Exceptions\NeedRegistrationException;
use Flute\Core\Exceptions\SocialNotFoundException;
use Flute\Core\Modules\Auth\Contracts\SocialServiceInterface;
use Flute\Core\Modules\Auth\Events\SocialProviderAddedEvent;
use Flute\Core\Modules\Auth\Hybrid\Storage\StorageSession;
use Hybridauth\Hybridauth;
use Hybridauth\User\Profile;

class SocialService implements SocialServiceInterface
{
    /** @var Hybridauth */
    private $hybridauth;

    /** @var array */
    private array $registeredProviders = [];

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->initializeProviders();
        $this->overrideDefaultProviders();
    }

    // ===== Initialization =====

    /**
     * Initializes registered social providers.
     */
    private function initializeProviders(): void
    {
        $providers = SocialNetwork::findAll(['enabled' => true]);

        foreach ($providers as $socialNetwork) {
            $this->registerSocial($socialNetwork);
        }
    }

    /**
     * Overrides default social providers.
     */
    private function overrideDefaultProviders(): void
    {
        $this->replaceDiscordProvider();
        $this->initializePSR();
    }

    /**
     * Initializes PSR-4 autoloading for custom providers.
     */
    private function initializePSR()
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, 'Hybridauth\\Provider\\');
        app()->getLoader()->addPsr4('Hybridauth\\Provider\\', BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'Hybrid');
    }

    /**
     * Replaces the standard Discord provider with a custom one.
     */
    private function replaceDiscordProvider()
    {
        $loader = app()->getLoader();

        $path = str_replace('\\', DIRECTORY_SEPARATOR, 'Hybridauth\\Provider\\Discord');

        $loader->addClassMap([
            $path => BASE_PATH . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'Hybrid' . DIRECTORY_SEPARATOR . 'Discord.php'
        ]);

        $loader->register();
    }

    // ===== Registering Providers =====

    /**
     * Registers a new social network.
     *
     * @param array $config Configuration settings for the social network.
     */
    public function registerSocialNetwork(array $config): void
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $config['key'];
        $socialNetwork->settings = json_encode($config['settings'] ?? []);
        $socialNetwork->icon = $config['icon'] ?? '';
        $socialNetwork->enabled = $config['enabled'] ?? true;
        $socialNetwork->allowToRegister = $config['allowToRegister'] ?? true;
        $socialNetwork->cooldownTime = $config['cooldownTime'] ?? 0;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);
    }

    /**
     * Registers all active social networks.
     */
    public function registerSocials()
    {
        $providers = SocialNetwork::findAll(['enabled' => true]);

        foreach ($providers as $socialNetwork) {
            $this->registerSocial($socialNetwork);
        }
    }

    /**
     * Registers a single social network.
     *
     * @param SocialNetwork $socialNetwork The social network entity to register.
     */
    public function registerSocial(SocialNetwork $socialNetwork)
    {
        $providerName = $this->normalizeProviderName($socialNetwork->key);
        $this->registeredProviders[$providerName] = array_merge([
            'enabled' => true,
            'entity' => $socialNetwork,
        ], json_decode($socialNetwork->settings, true) ?? []);

        events()->dispatch(new SocialProviderAddedEvent($socialNetwork), SocialProviderAddedEvent::NAME);
    }

    /**
     * Adds a new social network.
     *
     * @param string $key Unique key identifier for the social network.
     * @param array $settings Configuration settings for the social network.
     * @param string $icon Icon URL or path for the social network.
     * @param bool $enabled Whether the social network is enabled.
     * @return SocialNetwork The newly added social network entity.
     */
    public function addSocial(string $key, array $settings, string $icon, bool $enabled = true): SocialNetwork
    {
        $socialNetwork = new SocialNetwork();
        $socialNetwork->key = $key;
        $socialNetwork->settings = json_encode($settings);
        $socialNetwork->icon = $icon;
        $socialNetwork->enabled = $enabled;

        transaction($socialNetwork)->run();

        $this->registerSocial($socialNetwork);

        return $socialNetwork;
    }

    // ===== Getting Providers =====

    /**
     * Retrieves all registered providers.
     *
     * @param bool $onlyAllowed Whether to retrieve only providers allowed for registration.
     * @return array List of registered providers.
     */
    public function getAll(bool $onlyAllowed = true): array
    {
        return $onlyAllowed ? $this->getAllowedProviders() : $this->registeredProviders;
    }

    /**
     * Retrieves all providers (alias for getAll).
     *
     * @param bool $onlyAllowed Whether to retrieve only providers allowed for registration.
     * @return array List of providers.
     */
    public function getAllProviders(bool $onlyAllowed = true): array
    {
        return $this->getAll($onlyAllowed);
    }

    /**
     * Retrieves allowed providers for registration.
     *
     * @return array List of allowed providers.
     */
    protected function getAllowedProviders(): array
    {
        $result = [];

        foreach ($this->registeredProviders as $key => $provider) {
            if ($provider['entity']->allowToRegister === true) {
                $result[$key] = $provider;
            }
        }

        return $result;
    }

    /**
     * Checks if there are any registered providers.
     *
     * @return bool True if no providers are registered, false otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->registeredProviders);
    }

    /**
     * Converts providers for display purposes.
     *
     * @return array List of providers formatted for display.
     */
    public function toDisplay(): array
    {
        $result = [];

        foreach ($this->getAll() as $provider) {
            $key = $provider['entity']->key;
            if ($key === 'HttpsSteam') {
                $result['Steam'] = $provider['entity']->icon;
            } else {
                $result[$key] = $provider['entity']->icon;
            }
        }

        return $result;
    }

    /**
     * Retrieves a provider by name.
     *
     * @param string $socialNetworkName The name of the social network.
     * @return array The provider's configuration.
     * @throws SocialNotFoundException If the provider is not found.
     */
    public function retrieveSocialNetwork(string $socialNetworkName): array
    {
        if (! isset($this->registeredProviders[$socialNetworkName])) {
            throw new SocialNotFoundException($socialNetworkName);
        }

        return $this->registeredProviders[$socialNetworkName];
    }

    // ===== Authentication =====

    /**
     * Authenticates a user via a social network with possible registration.
     *
     * @param string $providerName The name of the social provider.
     * @return User The authenticated user.
     * @throws Exception If registration is not allowed or other errors occur.
     */
    public function authenticateWithRegister(string $providerName): User
    {
        $social = $this->retrieveSocialNetwork($this->normalizeProviderName($providerName));

        $this->ensureRegistrationAllowed($social);

        $authData = $this->authenticate($providerName);

        $authData['adapter']->disconnect();

        try {
            $authData['adapter']->getStorage()->clear();
        } catch (\Throwable $e) {
            logs()->warning($e);
        }

        $existingUser = $this->findUserBySocialProfile($authData['profile']);

        if ($existingUser) {
            return $existingUser;
        }

        if ($this->requiresAdditionalRegistration()) {
            throw new NeedRegistrationException($authData['profile']);
        }

        return $this->registerNewUser($authData['profile'], $social['entity']);
    }

    /**
     * Authenticates a user via a social network.
     *
     * @param string $providerName The name of the social provider.
     * @param bool $bind Whether to bind the social account to an existing user.
     * @return array Authentication data including user profile and adapter.
     * @throws Exception If user profile cannot be loaded.
     */
    public function authenticate(string $providerName, bool $bind = false): array
    {
        $this->initializeHybridAuth($providerName, $bind);

        $adapter = $this->hybridauth->authenticate($this->normalizeProviderName($providerName));
        $userProfile = $adapter->getUserProfile();

        $this->clearAuthData();

        if (!$userProfile) {
            throw new Exception('Failed to load user profile.');
        }

        return [
            'profile' => $userProfile,
            'adapter' => $adapter,
        ];
    }

    /**
     * Initializes Hybridauth with the given provider.
     *
     * @param string|null $providerName The name of the social provider.
     * @param bool $bind Whether to bind the social account to an existing user.
     */
    private function initializeHybridAuth(string $providerName = null, bool $bind = false): void
    {
        $callbackUrl = $bind
            ? url("profile/social/bind/$providerName")->get()
            : url("social/$providerName")->get();

        $this->hybridauth = new Hybridauth([
            'callback' => $callbackUrl,
            'providers' => $this->registeredProviders,
        ], null, new StorageSession());
    }

    /**
     * Finds a user by their social profile.
     *
     * @param Profile $profile The user's social profile.
     * @return User|null The user if found, null otherwise.
     */
    private function findUserBySocialProfile(Profile $profile): ?User
    {
        $userSocial = UserSocialNetwork::query()
            ->load(['user', 'user.roles'])
            ->where('user.isTemporary', false)
            ->where(['value' => $profile->identifier])
            ->fetchOne();

        return $userSocial ? $userSocial->user : null;
    }

    /**
     * Registers a new user via a social network.
     *
     * @param Profile $userProfile The user's social profile.
     * @param SocialNetwork $socialNetwork The social network entity.
     * @return User The newly registered user.
     */
    public function registerNewUser(Profile $userProfile, SocialNetwork $socialNetwork): User
    {
        $email = $userProfile->email;

        if ($email) {
            $existingUser = User::query()
                ->where(['email' => $email])
                ->fetchOne();

            if ($existingUser) {
                $email = null;
            }
        }

        $avatarPath = $userProfile->photoURL ?? config('profile.default_avatar');

        $this->findAndDeleteTemporaryUser($socialNetwork->key, $userProfile->identifier);

        $user = new User();
        $user->name = mb_substr($userProfile->displayName, 0, 255);
        $user->email = $email;
        $user->uri = null;
        $user->login = null;
        $user->avatar = $avatarPath;
        $user->banner = config('profile.default_banner');
        $user->verified = true;

        $userSocialNetwork = new UserSocialNetwork();
        $userSocialNetwork->value = $userProfile->identifier;
        $userSocialNetwork->url = $userProfile->profileURL;
        $userSocialNetwork->name = $userProfile->displayName;
        $userSocialNetwork->user = $user;
        $userSocialNetwork->socialNetwork = $socialNetwork;
        $userSocialNetwork->linkedAt = new \DateTimeImmutable();

        transaction([$user, $userSocialNetwork])->run();

        if ($socialNetwork->key === "Discord") {
            app()->get(DiscordService::class)->linkRoles($user, $user->roles);
        }

        return $user;
    }

    /**
     * Finds and deletes a temporary user by social network key and identifier.
     *
     * @param string $key The social network key.
     * @param string $identifier The user identifier.
     * @return void
     */
    private function findAndDeleteTemporaryUser(string $key, string $identifier): void
    {
        try {
            $userSocialNetwork = UserSocialNetwork::query()
                ->where(['socialNetwork.key' => $key, 'value' => $identifier, 'user.isTemporary' => true])
                ->load(['user'])
                ->fetchOne();

            if ($userSocialNetwork) {
                $userId = $userSocialNetwork->user->id;

                transaction($userSocialNetwork, 'delete')->run();

                $user = User::findByPK($userId);
                if ($user) {
                    transaction($user, 'delete')->run();
                }
            }
        } catch (\Exception $e) {
            logs()->error("Error deleting temporary user: " . $e->getMessage());
        }
    }

    /**
     * Binds a social network to an existing user.
     *
     * @param User $user The user to bind the social network to.
     * @param string $socialNetworkName The name of the social network.
     * @throws Exception If binding fails due to cooldown or other issues.
     */
    public function bindSocialNetwork(User $user, string $socialNetworkName): void
    {
        $authData = $this->authenticate($socialNetworkName, true);
        $social = $this->retrieveSocialNetwork($socialNetworkName);

        $userSocialNetwork = UserSocialNetwork::query()
            ->where([
                'user' => $user->id,
                'socialNetwork' => $social['entity']->id,
                'user.isTemporary' => false,
            ])
            ->fetchOne();

        $profile = $authData['profile'];
        $token = $authData['adapter']->getAccessToken();

        $now = new \DateTimeImmutable();

        if ($userSocialNetwork) {
            $lastLinked = $userSocialNetwork->linkedAt;

            if ($social['entity']->cooldownTime > 0 && $lastLinked && ($now->getTimestamp() - $lastLinked->getTimestamp() < $social['entity']->cooldownTime)) {
                throw new Exception(__('profile.errors.social_delay'));
            }

            $userSocialNetwork->value = $profile->identifier;
            $userSocialNetwork->url = $profile->profileURL;
            $userSocialNetwork->name = $profile->displayName;
            $userSocialNetwork->linkedAt = new \DateTimeImmutable();

            if ($token) {
                $userSocialNetwork->additional = json_encode($token);
            }

            transaction($userSocialNetwork)->run();
        } else {
            $userSocialNetwork = new UserSocialNetwork();
            $userSocialNetwork->value = $profile->identifier;
            $userSocialNetwork->url = $profile->profileURL;
            $userSocialNetwork->name = $profile->displayName;
            $userSocialNetwork->user = $user;
            $userSocialNetwork->socialNetwork = $social['entity'];
            $userSocialNetwork->linkedAt = new \DateTimeImmutable();

            if ($token) {
                $userSocialNetwork->additional = json_encode($token);
            }

            transaction($userSocialNetwork)->run();
        }

        if (!isset($user->roles)) {
            $user = User::query()->load(['roles'])->where(['id' => $user->id])->fetchOne();
        }

        if ($social['entity']->key === "Discord") {
            app()->get(DiscordService::class)->linkRoles($user, $user->roles);
        }

        $authData['adapter']->disconnect();

        try {
            $authData['adapter']->getStorage()->clear();
        } catch (\Throwable $e) {
            logs()->warning($e);
        }
    }

    // ===== Utilities =====

    /**
     * Normalizes the provider name.
     *
     * @param string $providerName The original provider name.
     * @return string The normalized provider name.
     */
    private function normalizeProviderName(string $providerName): string
    {
        return $providerName === 'Steam' ? 'HttpsSteam' : $providerName;
    }

    /**
     * Ensures that registration via the social network is allowed.
     *
     * @param array $social The social network configuration.
     * @throws Exception If registration is not allowed.
     */
    private function ensureRegistrationAllowed(array $social): void
    {
        if (! $social['entity']->allowToRegister) {
            throw new Exception(__('def.not_found'));
        }
    }

    /**
     * Checks if additional registration steps are required.
     *
     * @return bool True if additional registration is required, false otherwise.
     */
    private function requiresAdditionalRegistration(): bool
    {
        return config('auth.registration.social_supplement');
    }

    /**
     * Replaces the name of the social network.
     *
     * @param string $socialName The original social network name.
     * @return string The replaced social network name.
     */
    protected function replaceName(string $socialName)
    {
        return $this->normalizeProviderName($socialName);
    }

    /**
     * Clears authentication data.
     */
    public function clearAuthData(): void
    {
        try {
            if ($this->hybridauth) {
                foreach ($this->hybridauth->getConnectedAdapters() as $adapter) {
                    $adapter->disconnect();
                    try {
                        $adapter->getStorage()->clear();
                    } catch (\Throwable $e) {
                        logs()->warning($e);
                    }
                }
            }
        } catch (\Hybridauth\Exception\InvalidArgumentException $e) {
            logs()->warning($e);
        }
    }
}
