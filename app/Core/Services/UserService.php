<?php

namespace Flute\Core\Services;

use DateTimeImmutable;
use Flute\Core\Database\Entities\User;
use Flute\Core\Events\UserChangedEvent;
use Flute\Core\Exceptions\BalanceNotEnoughException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Modules\Auth\Services\AuthService;
use InvalidArgumentException;
use Jenssegers\Agent\Agent;
use Throwable;

enum UserPermission: string
{
    case ADMIN_BOSS = 'admin.boss';
}

/**
 * Class UserService
 *
 * This class is responsible for managing user authentication and related tasks.
 *
 * @package Flute\Core\Services
 */
class UserService
{
    /**
     * User token for authentication.
     *
     * @var string|null
     */
    protected ?string $userToken = null;

    /**
     * User's device information.
     *
     * @var Agent|null
     */
    protected ?Agent $userDevice = null;

    /**
     * Flag indicating if a login attempt has been made.
     *
     * @var bool
     */
    protected bool $triedToLogin = false;

    /**
     * Current authenticated user.
     *
     * @var User|null
     */
    protected ?User $currentUser = null;

    /**
     * Authentication service.
     *
     * @var AuthService
     */
    protected readonly AuthService $authService;

    /**
     * Cache for user objects.
     *
     * @var array
     */
    protected array $usersCache = [];

    /**
     * Cache for current user's permissions.
     *
     * @var array|null
     */
    protected ?array $permissionsCache = null;

    /**
     * Cache for current user's roles.
     *
     * @var array|null
     */
    protected ?array $rolesCache = null;

    /**
     * Highest role priority of the current user.
     *
     * @var int|null
     */
    protected ?int $highestPriority = null;

    /**
     * Constructor for UserService.
     *
     * Initializes class variables and starts an authentication session.
     *
     * @param AuthService $authService Authentication service.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
        $this->userToken = cookie()->get('remember_token');
        $this->userDevice = $this->device();
        $this->triedToLogin = false;
    }

    /**
     * Initializes the user based on the provided token.
     *
     * Validates the token and sets up the user session if valid; otherwise, clears the session and destroys the token.
     *
     * @return void
     */
    protected function initializeByToken(): void
    {
        $t0 = microtime(true);
        if (!$this->userToken || !is_installed()) {
            return;
        }

        try {
            $tokenInfo = $this->authService->getInfoFromToken($this->userToken);

            if (empty($tokenInfo->user)) {
                logs()->warning('auth.token.no_user_for_token');
                $this->sessionExpired();

                return;
            }

            $securityTokenEnabled = (bool) config('auth.security_token');

            if ($securityTokenEnabled) {
                $currentDeviceDetails = $this->userDevice->getUserAgent();

                if ($currentDeviceDetails !== $tokenInfo->userDevice->deviceDetails) {
                    logs()->warning('auth.token.device_mismatch');
                    $this->sessionExpired();

                    return;
                }
            }

            if (config('auth.check_ip')) {
                if ($tokenInfo->userDevice->ip !== request()->ip()) {
                    logs()->warning('auth.token.ip_mismatch', ['expected' => $tokenInfo->userDevice->ip, 'actual' => request()->ip()]);
                    $this->sessionExpired();

                    return;
                }
            }

            $this->currentUser = $this->get((int) $tokenInfo->user->id, false, ['roles', 'roles.permissions', 'socialNetworks', 'socialNetworks.socialNetwork']);

            if (!$this->currentUser) {
                $this->sessionExpired();

                return;
            }

            session()->set('user_id', $tokenInfo->user->id);

            $dt = (int) round((microtime(true) - $t0) * 1000);
            if ($dt >= 20) {
                logs()->info('auth.token.initialize_success', ['ms' => $dt, 'user_id' => (int) $tokenInfo->user->id]);
            } else {
                logs()->debug('auth.token.initialize_success', ['ms' => $dt, 'user_id' => (int) $tokenInfo->user->id]);
            }
        } catch (Throwable $e) {
            logs()->error($e);
            $this->sessionExpired();
        }
    }

    /**
     * Initializes the user based on session data.
     *
     * If a valid user ID is found in the session, it fetches the user.
     * If the session is invalid, it clears the session and destroys the token.
     *
     * @return void
     */
    public function initializeBySession(): void
    {
        $t0 = microtime(true);
        if (!is_installed()) {
            return;
        }

        $userId = session()->get('user_id');

        if ($userId) {
            if (!is_int($userId) || $userId <= 0) {
                $this->sessionExpired();

                return;
            }

            $this->currentUser = $this->get($userId, false, ['roles', 'roles.permissions', 'socialNetworks', 'socialNetworks.socialNetwork']);

            if (!$this->currentUser) {
                $this->sessionExpired();
            }
        }
    }

    /**
     * Retrieves a user based on a route (URI).
     *
     * Utilizes caching to minimize database queries.
     *
     * @param string $route Route of the user.
     * @param bool $force Force data refresh from the database.
     * @return User|null
     */
    public function getByRoute(string $route, bool $force = false): ?User
    {
        if (isset($this->usersCache[$route]) && !$force) {
            return $this->usersCache[$route];
        }

        $user = User::query()->where(['uri' => $route])->fetchOne();

        if ($user) {
            $this->usersCache[$user->id] = $user;
            $this->usersCache[$route] = $user;
        }

        return $this->usersCache[$route] ?? null;
    }

    /**
     * Retrieves a user by ID, optionally loading specified relationships.
     *
     * @param int $userId User ID.
     * @param bool $force Force data refresh from the database.
     * @param array $with Load specified relationships.
     * @return User|null
     */
    public function get(int $userId, bool $force = false, array $with = ['roles', 'socialNetworks', 'userDevices', 'actionLogs', 'invoices']): ?User
    {
        if (isset($this->usersCache[$userId]) && !$force) {
            return $this->usersCache[$userId];
        }

        $query = User::query();

        if (!empty($with)) {
            $query->load($with);
        }

        $user = $query->where(['id' => $userId])->fetchOne();

        if ($user) {
            $this->usersCache[$userId] = $user;
        }

        return $this->usersCache[$userId] ?? null;
    }

    /**
     * Handles session expiration.
     *
     * Clears the session, deletes the token, and informs the user of the session expiration.
     *
     * @return void
     */
    protected function sessionExpired(): void
    {
        auth()->logout();
        flash()->add('info', __('auth.session_expired'));

        if ($this->getUserToken()) {
            $this->authService->deleteAuthToken($this->getUserToken());
        }

        session()->invalidate();
    }

    /**
     * Returns the current authenticated user.
     *
     * If the current user is not initialized, it starts the authentication session.
     *
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        if (!$this->currentUser) {
            $this->authSession();
        }

        return $this->currentUser;
    }

    public function setCurrentUser(User $user): self
    {
        $this->currentUser = $user;

        // Dispatch user changed event
        events()->dispatch(new UserChangedEvent($user), UserChangedEvent::NAME);

        return $this;
    }

    /**
     * Checks if the user is currently authenticated.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (!$this->currentUser) {
            $this->authSession();
        }

        return $this->currentUser !== null;
    }

    /**
     * Initializes the authentication session.
     *
     * Checks if a login attempt has already been made, and if not, initializes the user.
     *
     * @param bool $force Force initialization.
     * @return void
     */
    public function authSession(bool $force = false): void
    {
        if ($this->triedToLogin && !$force) {
            return;
        }

        if (!is_installed()) {
            return;
        }

        if (session()->has('user_id')) {
            $this->initializeBySession();
        } elseif ($this->userToken) {
            $this->initializeByToken();
        }

        if ($this->currentUser) {
            $this->updateLastLogged();
        }

        $this->triedToLogin = true;
    }

    /**
     * Updates the last login timestamp of the current user.
     *
     * @return void
     */
    public function updateLastLogged(): void
    {
        if (!$this->currentUser || $this->currentUser->isOnline()) {
            return;
        }

        try {
            $this->currentUser->last_logged = new DateTimeImmutable();
            transaction($this->currentUser)->run();
        } catch (Throwable $e) {
            logs()->error($e);
        }
    }

    /**
     * Retrieves and caches the permissions of the current user.
     *
     * @return array
     */
    protected function getPermissions(): array
    {
        if (!$this->currentUser) {
            return [];
        }

        if ($this->permissionsCache !== null) {
            return $this->permissionsCache;
        }

        $this->permissionsCache = [];

        // Ensure roles and permissions are already loaded to prevent N+1 queries
        if (!isset($this->currentUser->roles[0]->permissions)) {
            $this->currentUser = $this->get($this->currentUser->id, true, ['roles', 'roles.permissions']);
        }

        foreach ($this->currentUser->getPermissions() as $permission) {
            $this->permissionsCache[] = $permission->name;
        }

        return $this->permissionsCache;
    }

    protected function checkUserPermission(string $permission)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $permissions = $this->getPermissions();

        return in_array(UserPermission::ADMIN_BOSS->value, $permissions, true) || in_array($permission, $permissions, true);
    }

    /**
     * Checks if the user has specific permission(s) and executes a callback if they do.
     *
     * @param UserPermission|string|array|User $permissions Permission name, enum, or array of permissions.
     * @param callable|null $callback Function to execute if the permission(s) are granted.
     * @return bool
     */
    public function can(UserPermission|string|array|User $permissions, ?callable $callback = null): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            $permissionName = $permission instanceof UserPermission ? $permission->value : $permission;

            if ($permissionName instanceof User) {
                if (!$this->canEditUser($permissionName)) {
                    return false;
                }
            } else {
                if (!$this->checkUserPermission($permissionName)) {
                    return false;
                }
            }
        }

        if ($callback) {
            try {
                $callback();
            } catch (Throwable $e) {
            }
        }

        return true;
    }

    /**
     * Adds a specified amount to the user's balance.
     *
     * @param float $sum Amount to add.
     * @param User|null $user User to add balance to. If null, the current user is used.
     * @return void
     * @throws Throwable
     */
    public function topup(float $sum, ?User $user = null): void
    {
        if ($sum <= 0) {
            throw new InvalidArgumentException('The sum must be a positive number.');
        }

        $balanceUser = $user ?? $this->getCurrentUser();

        if (!$balanceUser) {
            throw new UserNotFoundException();
        }

        $balanceUser->balance += $sum;
        transaction($balanceUser)->run();

        // Dispatch user changed event
        events()->dispatch(new UserChangedEvent($balanceUser), UserChangedEvent::NAME);
    }

    /**
     * Deducts a specified amount from the user's balance.
     *
     * @param float $sum Amount to deduct.
     * @param User|null $user User to deduct balance from. If null, the current user is used.
     * @return void
     * @throws BalanceNotEnoughException
     * @throws Throwable
     */
    public function unbalance(float $sum, ?User $user = null): void
    {
        if ($sum <= 0) {
            throw new InvalidArgumentException('The sum must be a positive number.');
        }

        $balanceUser = $user ?? $this->getCurrentUser();

        if (!$balanceUser) {
            throw new UserNotFoundException();
        }

        if ($balanceUser->balance < $sum) {
            $neededSum = $sum - $balanceUser->balance;

            throw (new BalanceNotEnoughException())->setNeededSum($neededSum);
        }

        $balanceUser->balance -= $sum;
        transaction($balanceUser)->run();

        // Dispatch user changed event
        events()->dispatch(new UserChangedEvent($balanceUser), UserChangedEvent::NAME);
    }

    /**
     * Update user information and dispatch change event.
     *
     * @param User $user User to update
     * @return void
     * @throws Throwable
     */
    public function updateUser(User $user): void
    {
        transaction($user)->run();

        // Dispatch user changed event
        events()->dispatch(new UserChangedEvent($user), UserChangedEvent::NAME);
    }

    /**
     * Retrieves the highest role priority of a user.
     *
     * @param User|null $user User to check. If null, the current user is used.
     * @return int
     */
    public function getHighestPriority(?User $user = null): int
    {
        $userToCheck = $user ?? $this->currentUser;

        if (!$userToCheck) {
            return 0;
        }

        if ($userToCheck === $this->currentUser && $this->highestPriority !== null) {
            return $this->highestPriority;
        }

        $highestPriority = 0;

        foreach ($userToCheck->roles as $role) {
            if ($role->priority > $highestPriority) {
                $highestPriority = $role->priority;
            }
        }

        if ($userToCheck === $this->currentUser) {
            $this->highestPriority = $highestPriority;
        }

        return $highestPriority;
    }

    /**
     * Determines if the current user can edit another user based on role priorities.
     *
     * @param User $userToEdit User to edit.
     * @return bool
     */
    public function canEditUser(User $userToEdit): bool
    {
        $currentUserHighestPriority = $this->getHighestPriority();
        $userToEditHighestPriority = $this->getHighestPriority($userToEdit);

        return $currentUserHighestPriority > $userToEditHighestPriority || $this->can(UserPermission::ADMIN_BOSS->value);
    }

    /**
     * Checks if the current user has a specific permission.
     *
     * @param string $permission Permission name.
     *
     * @deprecated Use `can` instead
     *
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    /**
     * Checks if the current user possesses a specific role.
     *
     * @param string $role Role name.
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if ($this->rolesCache !== null) {
            return in_array($role, $this->rolesCache, true);
        }

        $this->rolesCache = [];

        foreach ($this->currentUser->roles as $userRole) {
            $this->rolesCache[] = $userRole->name;
        }

        return in_array($role, $this->rolesCache, true);
    }

    /**
     * Checks if the user has linked a specific social network.
     *
     * @param string $key Social network key.
     * @return bool
     */
    public function hasSocialNetwork(string $key): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return !empty($this->currentUser->getSocialNetwork($key));
    }

    /**
     * Checks if the current user has enough balance to purchase a product.
     *
     * @param float $sum Amount to check.
     * @return bool
     */
    public function hasEnoughBalance(float $sum): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return ($this->currentUser->balance ?? 0) >= $sum;
    }

    /**
     * Retrieves the device information of the current user.
     *
     * @return Agent
     */
    public function device(): Agent
    {
        if ($this->userDevice !== null) {
            return $this->userDevice;
        }

        $agent = new Agent();
        $agent->setHttpHeaders(request()->headers->all());

        $this->userDevice = $agent;

        return $this->userDevice;
    }

    /**
     * Returns the user's authentication token.
     *
     * Works only with "remember me" tokens.
     *
     * @return string|null
     */
    public function getUserToken(): ?string
    {
        return $this->userToken;
    }

    public function __call(string $name, array $args)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        return call_user_func_array([$this->currentUser, $name], $args);
    }

    public function __get(string $name)
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->currentUser->$name ?? null;
    }

    /**
     * Update a specific property of a user and dispatch change event.
     *
     * @param User $user User to update
     * @param string $property Property name
     * @param mixed $value New value
     * @return void
     * @throws Throwable
     */
    public function updateUserProperty(User $user, string $property, $value): void
    {
        if (!property_exists($user, $property)) {
            throw new \InvalidArgumentException("Property $property does not exist on User entity");
        }

        $user->$property = $value;
        $this->updateUser($user);
    }

    /**
     * Clears the cached current user and related caches.
     *
     * This should be called on logout to ensure that subsequent requests
     * do not operate on a stale user instance.
     *
     * @return void
     */
    public function clearCurrentUser(): void
    {
        $this->currentUser = null;
        $this->permissionsCache = null;
        $this->rolesCache = null;
        $this->highestPriority = null;
        $this->triedToLogin = false;
    }
}
