<?php

namespace Flute\Core\Modules\Auth\Services;

use Carbon\Carbon;
use DateTime;
use DateTimeImmutable;
use Exception;
use Flute\Core\Database\Entities\PasswordResetToken;
use Flute\Core\Database\Entities\RememberToken;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserDevice;
use Flute\Core\Database\Entities\VerificationToken;
use Flute\Core\Database\Repositories\UserRepository;
use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Exceptions\DuplicateEmailException;
use Flute\Core\Exceptions\DuplicateLoginException;
use Flute\Core\Exceptions\IncorrectPasswordException;
use Flute\Core\Exceptions\PasswordResetTokenExpiredException;
use Flute\Core\Exceptions\PasswordResetTokenNotFoundException;
use Flute\Core\Exceptions\TemporaryUserException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Modules\Auth\Events\PasswordResetRequestedEvent;
use Flute\Core\Modules\Auth\Events\UserAuthenticatingEvent;
use Flute\Core\Modules\Auth\Events\UserLoggedInEvent;
use Flute\Core\Modules\Auth\Events\UserLoggedOutEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteredEvent;
use Flute\Core\Modules\Auth\Events\UserRegisteringEvent;
use Flute\Core\Modules\Auth\Events\UserVerifiedEvent;
use Flute\Core\Services\ConfigurationService;
use Flute\Core\Services\CookieService;
use Flute\Core\Support\FluteRequest;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;

use function random_bytes;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

class AuthenticationService
{
    private ?UserRepository $userRepository = null;

    private $rememberTokenRepository = null;

    private $userDeviceRepository = null;

    private Processor $validationProcessor;

    private ConfigurationService $config;

    private FluteRequest $request;

    private SessionInterface $session;

    private CookieService $cookie;

    public function __construct(
        ConfigurationService $config,
        FluteRequest $request,
        SessionInterface $session,
        CookieService $cookie
    ) {
        $this->validationProcessor = new Processor();
        $this->config = $config;
        $this->request = $request;
        $this->session = $session;
        $this->cookie = $cookie;
    }

    /**
     * Register a new user.
     *
     * @param array $credentials The user data.
     * @throws DuplicateEmailException
     * @throws DuplicateLoginException
     * @throws ValidationException
     * @throws TooManyRequestsException
     * @return User The registered user.
     */
    public function register(array $credentials): User
    {
        $this->throttle('register');

        $validationResult = $this->validateRegistrationData($credentials);

        $this->checkUserDuplicity($validationResult->email, $validationResult->login);

        $user = $this->createNewUser($validationResult);

        events()->dispatch(new UserRegisteringEvent($user, $credentials), UserRegisteringEvent::NAME);

        if (!$this->config->get('auth.registration.confirm_email')) {
            $this->setCurrentUser($user);
        }

        events()->dispatch(new UserRegisteredEvent($user), UserRegisteredEvent::NAME);

        return $user;
    }

    /**
     * Authenticate a user.
     *
     * @param array $credentials The user credentials.
     * @param bool $fromSocial Indicates if authentication is from social network.
     * @throws ValidationException
     * @throws UserNotFoundException
     * @throws IncorrectPasswordException
     * @throws AccountNotVerifiedException
     * @throws TooManyRequestsException
     * @return User The authenticated user.
     */
    public function authenticate(array $credentials, bool $fromSocial = false): User
    {
        $this->throttle('login');

        $validationResult = $this->validationProcessor->process($this->getAuthValidator(), $credentials);

        events()->dispatch(new UserAuthenticatingEvent($credentials), UserAuthenticatingEvent::NAME);

        $user = $this->getUserRepository()->getByEmailOrLogin($validationResult->login);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if (!$this->verifyPassword($validationResult->password, $user->password)) {
            throw new IncorrectPasswordException();
        }

        if (!$fromSocial && !$user->verified && $this->config->get('auth.registration.confirm_email')) {
            throw new AccountNotVerifiedException();
        }

        if ($user->isTemporary()) {
            throw new TemporaryUserException();
        }

        $this->setCurrentUser($user);

        events()->dispatch(new UserLoggedInEvent($user), UserLoggedInEvent::NAME);

        return $user;
    }

    /**
     * Authenticate a user by user ID.
     *
     * @param int $userId The user ID.
     * @param bool $fromSocial Indicates if authentication is from social network.
     * @throws UserNotFoundException
     * @throws AccountNotVerifiedException
     * @throws TooManyRequestsException
     * @return User The authenticated user.
     */
    public function authenticateByUserId(int $userId, bool $fromSocial = false): User
    {
        $this->throttle('login');

        $user = $this->getUserRepository()->findByPK($userId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        if (!$fromSocial && !$user->verified && $this->config->get('auth.registration.confirm_email')) {
            throw new AccountNotVerifiedException();
        }

        $this->setCurrentUser($user);

        events()->dispatch(new UserLoggedInEvent($user), UserLoggedInEvent::NAME);

        return $user;
    }

    /**
     * Remember the authenticated user by creating and storing a token.
     *
     * @param User $user The authenticated user.
     * @param UserDevice $device The user's device.
     * @throws Throwable
     * @return string|null The remember token.
     */
    public function rememberUser(User $user, UserDevice $device): ?string
    {
        if (!$this->config->get('auth.remember_me')) {
            return null;
        }

        $existingTokens = RememberToken::findAll(['user_id' => $user->id, 'userDevice_id' => $device->id, 'userDevice.ip' => $this->request->getClientIp()]);

        foreach ($existingTokens as $token) {
            transaction($token, 'delete')->run();
        }

        $rememberToken = $this->generateRandomToken();

        $tokenEntity = new RememberToken();
        $tokenEntity->user = $user;
        $tokenEntity->userDevice = $device;
        $tokenEntity->token = hash('sha256', $rememberToken);
        $tokenEntity->lastUsedAt = new DateTimeImmutable();

        transaction($tokenEntity)->run();

        $this->cookie->set(
            name: 'remember_token',
            value: $rememberToken,
            expire: $this->config->get('auth.remember_me_duration'),
            httpOnly: true,
            sameSite: 'Strict'
        );

        return $rememberToken;
    }

    /**
     * Create the remember token for authentication.
     *
     * @param User $user The user to remember.
     * @throws Throwable
     * @return string|null The remember token.
     */
    public function createRememberToken(User $user): ?string
    {
        $deviceDetails = $this->request->headers->get('User-Agent');
        $ip = $this->request->getClientIp();

        $userDevice = UserDevice::findOne(['deviceDetails' => $deviceDetails, 'ip' => $ip]);

        if (!$userDevice) {
            $userDevice = new UserDevice();
            $userDevice->user = $user;
            $userDevice->deviceDetails = $deviceDetails;
            $userDevice->ip = $ip;

            transaction($userDevice)->run();
        }

        return $this->rememberUser($user, $userDevice);
    }

    /**
     * Verify the specified user.
     *
     * @param string $token The verification token.
     * @throws AccountNotVerifiedException
     * @throws ValidationException
     * @throws PasswordResetTokenNotFoundException
     * @throws Throwable
     * @return User The verified user.
     */
    public function verifyUser(string $token): User
    {
        if (!$this->config->get('auth.registration.confirm_email')) {
            throw new AccountNotVerifiedException('Email confirmation is not required.');
        }

        $verificationToken = VerificationToken::query()
            ->where(['token' => $token])
            ->load(['user', 'user.roles'])
            ->fetchOne();

        if ($verificationToken === null || $verificationToken->expiresAt < new DateTime()) {
            throw new AccountNotVerifiedException('Invalid or expired verification token.');
        }

        $user = $verificationToken->user;
        $user->verified = true;

        transaction($user)->run();
        transaction($verificationToken, 'delete')->run();

        events()->dispatch(new UserVerifiedEvent($user), UserVerifiedEvent::NAME);

        return $user;
    }

    /**
     * Create a verification token for the given user.
     *
     * @param User $user The user to verify.
     * @throws Throwable
     * @return VerificationToken The created verification token.
     */
    public function createVerificationToken(User $user): VerificationToken
    {
        $existingVerificationToken = VerificationToken::query()
            ->where(['user_id' => $user->id])
            ->orderBy(['expiresAt' => 'DESC'])
            ->fetchOne();

        if ($existingVerificationToken && $existingVerificationToken->expiresAt->modify('+24 hours') > new DateTime()) {
            throw new AccountNotVerifiedException('Verification token already exists and is not expired.');
        }

        $verificationTokenValue = $this->generateRandomToken();

        $expiresAt = Carbon::now()->addHours(24);

        $verificationToken = new VerificationToken();
        $verificationToken->user = $user;
        $verificationToken->token = $verificationTokenValue;
        $verificationToken->expiresAt = $expiresAt->toDateTimeImmutable();

        try {
            transaction($verificationToken)->run();
        } catch (Throwable $e) {
            throw $e;
        }

        return $verificationToken;
    }

    /**
     * Logout the user by clearing the remember token and the session.
     */
    public function logout(): void
    {
        if ($this->config->get('auth.remember_me') && $token = $this->cookie->get('remember_token')) {
            $rememberToken = RememberToken::findOne(['token' => hash('sha256', $token)]);

            if ($rememberToken) {
                transaction($rememberToken, 'delete')->run();
            }
        }

        $this->session->clear();
        $this->cookie->remove('remember_token');

        user()->clearCurrentUser();

        events()->dispatch(new UserLoggedOutEvent(), UserLoggedOutEvent::NAME);
    }

    /**
     * Create a password reset token.
     *
     * @param string $loginOrEmail The user's login or email.
     * @throws UserNotFoundException
     * @throws TooManyRequestsException
     * @throws Throwable
     * @return PasswordResetToken The created password reset token.
     */
    public function createPasswordResetToken(string $loginOrEmail): PasswordResetToken
    {
        $this->throttle('reset_password', 3, 60, 1);

        $expiresAt = Carbon::now()->addHours(24);

        $user = $this->getUserRepository()->getByEmailOrLogin($loginOrEmail);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $passwordResetTokenValue = $this->generateRandomToken();

        $passwordResetToken = new PasswordResetToken();
        $passwordResetToken->user = $user;
        $passwordResetToken->token = $passwordResetTokenValue;
        $passwordResetToken->expiry = $expiresAt->toDateTimeImmutable();

        try {
            transaction($passwordResetToken)->run();
        } catch (Throwable $e) {
            throw $e;
        }

        events()->dispatch(new PasswordResetRequestedEvent($user, $passwordResetToken), PasswordResetRequestedEvent::NAME);

        return $passwordResetToken;
    }

    /**
     * Check if a password reset token is valid.
     *
     * @param string $token The password reset token.
     * @throws PasswordResetTokenNotFoundException
     * @return PasswordResetToken The password reset token.
     */
    public function checkPasswordResetToken(string $token): PasswordResetToken
    {
        // Eager load user and related data to prevent N+1 queries
        $passwordResetToken = PasswordResetToken::query()
            ->where(['token' => $token])
            ->load(['user', 'user.roles'])
            ->fetchOne();

        if ($passwordResetToken === null) {
            throw new PasswordResetTokenNotFoundException();
        }

        return $passwordResetToken;
    }

    /**
     * Reset the user's password.
     *
     * @param string $token The password reset token.
     * @param string $newPassword The new password.
     * @throws PasswordResetTokenNotFoundException
     * @throws PasswordResetTokenExpiredException
     * @throws Throwable
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $passwordResetToken = $this->checkPasswordResetToken($token);

        if ($passwordResetToken->expiry < new DateTime()) {
            throw new PasswordResetTokenExpiredException();
        }

        $user = $passwordResetToken->user;
        $user->setPassword($newPassword);

        try {
            transaction($passwordResetToken, 'delete')->run();
            transaction($user)->run();
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Throttle the requests to limit the number of attempts per minute.
     *
     * @param string $key The action key.
     * @param int $maxRequest The maximum number of requests allowed.
     * @param int $perMinute The time period in minutes.
     * @param int $burstiness The maximum number of requests in a burst.
     * @throws TooManyRequestsException
     */
    protected function throttle(string $key, int $maxRequest = 5, int $perMinute = 60, int $burstiness = 5): void
    {
        try {
            throttler()->throttle(
                ['action' => $key, 'ip' => $this->request->getClientIp()],
                $maxRequest,
                $perMinute,
                $burstiness
            );
        } catch (TooManyRequestsException $e) {
            throw $e;
        }
    }

    /**
     * Generate a random token.
     *
     * @throws Exception
     * @return string The generated token.
     */
    protected function generateRandomToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the user repository.
     */
    protected function getUserRepository(): UserRepository
    {
        if ($this->userRepository === null) {
            /** @var UserRepository $userRepository */
            $userRepository = rep(User::class);
            $this->userRepository = $userRepository;
        }

        return $this->userRepository;
    }

    /**
     * Get the registration validator schema.
     */
    protected function getRegisterValidator(): Schema
    {
        return Expect::structure([
            'email' => Expect::email()->required(),
            'login' => Expect::string()
                ->min($this->config->get('auth.validation.login.min_length'))
                ->max($this->config->get('auth.validation.login.max_length'))
                ->required(),
            'name' => Expect::string()
                ->min($this->config->get('auth.validation.name.min_length'))
                ->max($this->config->get('auth.validation.name.max_length'))
                ->required(),
            'password' => Expect::string()
                ->min($this->config->get('auth.validation.password.min_length'))
                ->max($this->config->get('auth.validation.password.max_length'))
                ->required(),
            'remember_me' => Expect::bool()->required(),
        ])->otherItems();
    }

    /**
     * Get the authentication validator schema.
     */
    protected function getAuthValidator(): Schema
    {
        return Expect::structure([
            'login' => Expect::string()->required(),
            'password' => Expect::string()->required(),
            'remember_me' => Expect::bool()->required(),
        ])->otherItems();
    }

    /**
     * Verify the provided password against the stored hash.
     *
     * @param string $password The provided password.
     * @param string $hashedPassword The stored hashed password.
     */
    protected function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Set the current user in the session.
     *
     * @param User $user The user to set.
     */
    protected function setCurrentUser(User $user): void
    {
        $this->session->set('user_id', $user->id);
        $this->session->set('just_logged_in_at', time());

        user()->setCurrentUser($user);

        $roleId = config('auth.default_role');

        if ($roleId) {
            try {
                $role = \Flute\Core\Database\Entities\Role::findByPK($roleId);

                if ($role && !$user->hasRole($role->name) && !$user->isTemporary()) {
                    $user->addRole($role);
                    transaction($user)->run();
                }
            } catch (Throwable $e) {
                logs()->warning($e);
            }
        }
    }

    /**
     * Validate registration data.
     *
     * @param array $data The data to validate.
     * @throws ValidationException
     * @return object The validated data.
     */
    private function validateRegistrationData(array $data): object
    {
        return $this->validationProcessor->process($this->getRegisterValidator(), $data);
    }

    /**
     * Check for user duplicity based on email and login.
     *
     * @param string $email The user's email.
     * @param string $login The user's login.
     * @throws DuplicateEmailException
     * @throws DuplicateLoginException
     */
    private function checkUserDuplicity(string $email, string $login): void
    {
        if ($this->getUserRepository()->findByEmail($email)) {
            throw new DuplicateEmailException();
        }

        if ($this->getUserRepository()->findByLogin($login)) {
            throw new DuplicateLoginException();
        }
    }

    /**
     * Create a new user entity and save it to the database.
     *
     * @param object $userData The validated user data.
     * @throws Throwable
     * @return User The created user entity.
     */
    private function createNewUser(object $userData): User
    {
        $user = new User();
        $user->email = $userData->email;
        $user->login = $userData->login;
        $user->name = $userData->name;
        $user->avatar = $this->config->get('profile.default_avatar');
        $user->banner = $this->config->get('profile.default_banner');
        $user->verified = !$this->config->get('auth.registration.confirm_email');

        foreach ($userData as $key => $value) {
            if (property_exists($user, $key) && in_array($key, ['email', 'login', 'name', 'password'])) {
                continue;
            }
            $user->$key = $value;
        }

        $user->setPassword($userData->password);

        transaction($user)->run();

        return $user;
    }
}
