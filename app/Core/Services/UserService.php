<?php

namespace Flute\Core\Services;

use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserActionLog;
use Flute\Core\Database\Repositories\UserRepository;
use Flute\Core\Exceptions\BalanceNotEnoughException;
use Flute\Core\Exceptions\UserNotFoundException;
use Throwable;
use WhichBrowser\Parser;

/**
 * Class UserService
 *
 * This class is responsible for handling user authentication and related tasks
 *
 * @package Flute\Core\Services
 */
class UserService
{
    protected $userToken = null;
    protected ?Parser $userDevice = null;
    protected bool $triedToLogin = false;

    /** @var User */
    protected ?User $currentUser = null;
    protected AuthService $authService;
    protected ?UserRepository $userRepository = null;
    protected array $usersCache = [];

    /**
     * UserService constructor.
     *
     * The constructor initializes several class variables and runs the authentication session
     *
     * @param AuthService $authService
     */
    public function __construct(
        AuthService $authService
    ) {
        $this->authService = $authService;
        $this->userToken = cookie()->get('remember_token');
        $this->userDevice = $this->device();

        is_installed() && $this->authSession();
    }

    /**
     * Initializes the user using a token.
     *
     * If the user's token is available and valid, initialize the user and set the session.
     * If the token is invalid, clear the session and destroy the token.
     */
    protected function initializeByToken()
    {
        if (!$this->userToken || !is_installed())
            return;

        try {
            $tokenInfo = $this->authService->getInfoFromToken($this->userToken);

            if (empty($tokenInfo->user))
                return $this->sessionExpired();

            if ((bool) config('auth.security_token') !== true || (bool) config('auth.security_token') === true && ($tokenInfo && $_SERVER['HTTP_USER_AGENT'] === $tokenInfo->userDevice->deviceDetails)) {
                if (config('auth.check_ip') && $tokenInfo->userDevice->ip !== request()->ip()) {
                    return $this->sessionExpired();
                }

                $this->currentUser = $this->get($tokenInfo->user->id);
                session()->set('user_id', $tokenInfo->user->id);
            } else
                $this->sessionExpired();
        } catch (\Exception $e) {
            logs()->info($e);
            $this->sessionExpired();
        }
    }

    /**
     * Initialize user by Session
     *
     * If the session contains a valid user, initialize the user.
     * If the session is invalid, clear the session and destroy the token.
     */
    public function initializeBySession()
    {
        if (!is_installed())
            return;

        if ($userId = session()->get('user_id')) {
            $this->currentUser = $this->get($userId);

            if (!$this->currentUser) {
                $this->sessionExpired();
                return;
            }
        }
    }

    public function getByRoute(string $route, bool $force = false): ?User
    {
        if (isset($this->usersCache[$route]) && !$force) {
            return $this->usersCache[$route];
        }

        $db = $this->getUserRepository()->select()
            ->load([
                'socialNetworks',
                'socialNetworks.socialNetwork',
                'roles',
                'rememberTokens',
                'userDevices',
                'blocksGiven',
                'blocksReceived'
            ])->fetchOne([
                    "uri" => $route
                ]);

        if ($db) {
            $this->usersCache[$db->id] = $db;
        }

        $this->usersCache[$route] = $db;

        return $this->usersCache[$route];
    }

    public function get(int $userId, bool $force = false): ?User
    {
        if (isset($this->usersCache[$userId]) && !$force) {
            return $this->usersCache[$userId];
        }

        $db = $this->getUserRepository()->select()
            ->load([
                'socialNetworks',
                'socialNetworks.socialNetwork',
                'roles',
                'rememberTokens',
                'userDevices',
                'blocksGiven',
                'blocksReceived'
            ])->fetchOne([
                    "id" => $userId
                ]);

        if ($db) {
            $this->usersCache[$db->getUrl()] = $db;
        }

        $this->usersCache[$userId] = $db;

        return $this->usersCache[$userId];
    }

    /**
     * Handles session expiration
     *
     * Clears the session, removes the token, and informs the user about the expired session
     * @throws Throwable
     */
    protected function sessionExpired()
    {
        auth()->logout();
        
        flash()->add('info', __('validator.session_expired'));

        if ($this->getUserToken())
            $this->authService->deleteAuthToken($this->getUserToken());
    }

    /**
     * Returns the current user
     *
     * If the current user is not initialized, run the authentication session first
     *
     * @return User
     */
    public function getCurrentUser(): ?User
    {
        if (!$this->currentUser) {
            $this->authSession();
        }

        return $this->currentUser;
    }

    /**
     * Set a current user. CATION - VERY DANGEROUS, IT CAN KILL YOU
     * 
     * @param User $user
     * 
     * @return self
     */
    public function setCurrentUser(User $user): self
    {
        $this->currentUser = $user;

        return $this;
    }

    /**
     * Checks if the user is logged in
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        if (!$this->currentUser)
            $this->authSession();

        return $this->currentUser !== null;
    }

    /**
     * This method checks if the user has already attempted to login and if not, it initializes the user.
     */
    public function authSession(bool $force = false)
    {
        if ($this->triedToLogin && !$force)
            return;

        $this->userToken ? $this->initializeByToken() : $this->initializeBySession();

        if ($this->currentUser) {
            template()->getBlade()->setAuth($this->currentUser->name, null, $this->getPermissions());
            $this->updateLastLogged();
        }

        $this->triedToLogin = true;
    }

    /**
     * Update the last_logged timestamp for the current user.
     *
     * @return void
     */
    public function updateLastLogged(): void
    {
        if (!$this->currentUser) {
            return;
        }

        try {
            $this->currentUser->last_logged = new \DateTime();
            transaction($this->currentUser)->run();
        } catch (Throwable $e) {
            // Handle exception if necessary
        }
    }

    /**
     * User permissions to normal array
     * 
     * @return array
     */
    protected function getPermissions(): array
    {
        $result = [];

        foreach ($this->currentUser->getPermissions()->toArray() as $permission) {
            $result[] = $permission->name;
        }

        return $result;
    }

    public function can(string $permission, ?callable $callback = null): bool
    {
        $perm = $this->hasPermission($permission);

        if ($perm && $callback) {
            $callback();
        }

        return $perm;
    }

    public function topup($sum, ?User $user = null)
    {
        $balanceUser = $user ? $user : $this->getCurrentUser();

        $balanceUser->balance = $balanceUser->balance + $sum;

        transaction($balanceUser)->run();
    }

    public function unbalance($sum, ?User $user = null)
    {
        $balanceUser = $user ? $user : $this->getCurrentUser();

        if ($balanceUser->balance < $sum) {
            $exception = (new BalanceNotEnoughException())
                ->setNeededSum($sum - $balanceUser->balance);

            throw $exception;
        }

        $balanceUser->balance = $balanceUser->balance - $sum;

        transaction($balanceUser)->run();
    }

    public function getHighestPriority(?User $user = null): int
    {
        $userToCheck = !$user ? $this->currentUser : $user;

        if (!$userToCheck)
            return 0;

        $highestPriority = 0;
        foreach ($userToCheck->getRoles() as $role) {
            if ($role->priority > $highestPriority) {
                $highestPriority = $role->priority;
            }
        }
        return $highestPriority;
    }

    /**
     * Check if the current user can edit the given user.
     *
     * @param User $userToEdit
     * 
     * @return bool
     */
    public function canEditUser(User $userToEdit): bool
    {
        $currentUserHighestPriority = $this->getHighestPriority();
        $userToEditHighestPriority = $this->getHighestPriority($userToEdit);

        return $currentUserHighestPriority > $userToEditHighestPriority || $this->hasPermission('admin.boss');
    }

    /**
     * Has permission function
     * 
     * @param string $permission
     * 
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isLoggedIn())
            return false;

        return $this->currentUser->hasPermission('admin.boss') || $this->currentUser->hasPermission($permission);
    }

    /**
     * Has role function
     * 
     * @param string $role
     * 
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn())
            return false;

        return $this->currentUser->hasRole($role);
    }

    /**
     * Has social network
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function hasSocialNetwork(string $key): bool
    {
        return !empty($this->currentUser->getSocialNetwork($key));
    }

    /**
     * Log some user actions
     *
     * @param string $action
     * @param ?string $details
     * @param ?string $url
     * @param int $userId
     *
     * @return void
     * @throws Throwable
     * @throws UserNotFoundException
     */
    public function log(string $action, ?string $details = null, ?string $url = null, int $userId = 0)
    {
        if (!$this->isLoggedIn())
            return;

        $user = $userId !== 0 ? user()->get($userId) : $this->currentUser;

        if (!$user)
            throw new UserNotFoundException;

        $table = db()->table('user_action_logs');

        $table->insertOne([
            'action' => $action,
            'details' => $details,
            'url' => $url,
            'user_id' => $user->id,
            'action_date' => new \DateTime()
        ]);
    }

    /**
     * This method retrieves the device details of the current user.
     *
     * @return Parser
     */
    public function device(): Parser
    {
        $result = new Parser();
        $result->setCache(cache()->getAdapter());
        $result->analyse($_SERVER['HTTP_USER_AGENT']);

        return $result;
    }

    protected function getUserRepository(): UserRepository
    {
        if ($this->userRepository !== null)
            return $this->userRepository;

        /** @var UserRepository $userRepository */
        $userRepository = rep(User::class);

        return $this->userRepository = $userRepository;
    }

    /**
     * This method calls methods for the current user.
     * If the user is not logged in, an exception is thrown.
     *
     * @param string $name
     * @param array $args
     * @throws \RuntimeException
     * @return mixed
     */
    public function __call(string $name, array $args)
    {
        if (!$this->isLoggedIn())
            // throw new \RuntimeException('You must be logged in to call methods for checking users.');
            return false;

        return call_user_func_array([$this->currentUser, $name], $args);
    }
    public function __get(string $name)
    {
        if (!$this->isLoggedIn())
            // throw new \RuntimeException('You must be logged in to call methods for checking users.');
            return false;

        return $this->currentUser->$name;
    }

    /**
     * This method returns the user's remember token.
     * It is important to note that it only works with remember tokens.
     *
     * @return string|null
     */
    public function getUserToken(): ?string
    {
        return $this->userToken;
    }
}