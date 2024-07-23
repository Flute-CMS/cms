<?php

namespace Flute\Core\Auth;

use Cycle\ORM\Select\Repository;
use DI\DependencyException;
use DI\NotFoundException;
use Flute\Core\Auth\Events\{
    PasswordResetRequestedEvent,
    UserLoggedInEvent,
    UserLoggedOutEvent,
    UserRegisteredEvent,
    UserVerifiedEvent
};
use Flute\Core\Database\Entities\{
    PasswordResetToken,
    RememberToken,
    UserDevice,
    User,
    VerificationToken,
    UserBlock
};
use Flute\Core\Exceptions\{
    AccountNotVerifiedException,
    IncorrectPasswordException,
    PasswordResetTokenExpiredException,
    PasswordResetTokenNotFoundException,
    TooManyRequestsException,
    UserNotFoundException,
    DuplicateLoginException,
    DuplicateEmailException
};
use Nette\Schema\{
    Expect,
    Processor,
    Schema,
    ValidationException
};

use Flute\Core\Database\Repositories\UserRepository;
use Throwable;

class AuthenticationService
{
    private ?UserRepository $userRepository = null;
    private ?Repository $rememberTokenRepository = null;
    private ?Repository $userDeviceRepository = null;
    private Processor $validationProcessor;

    public function __construct()
    {
        $this->validationProcessor = new Processor;
    }

    /**
     * Register a new user.
     *
     * @param array $credentials The user data.
     * @return User The registered user.
     * @throws DuplicateEmailException
     * @throws DuplicateLoginException
     */
    public function register(array $credentials): User
    {
        // Throttle requests
        $this->throttle('register');

        // Validate the user data
        $validationResult = $this->validateRegistrationData($credentials);

        // Check for duplicate email or login
        $this->checkUserDuplicity($validationResult->email, $validationResult->login);

        // Create a new user
        $user = $this->createNewUser($validationResult);
        
        $user = user()->get($user->id);

        user()->setCurrentUser($user);

        events()->dispatch(new UserRegisteredEvent($user), UserRegisteredEvent::NAME);

        return $user;
    }

    /**
     * Authenticate a user.
     *
     * @param array $credentials The user credentials.
     * 
     * @throws ValidationException If the credentials are not valid.
     * @throws UserNotFoundException If the user is not found.
     * @throws IncorrectPasswordException If the password is incorrect.
     * @throws AccountNotVerifiedException If too many requests were made.
     * 
     * @return User The authenticated user.
     */
    public function authenticate(array $credentials): User
    {
        // Throttle requests
        $this->throttle('login');

        // Validate the user credentials
        $validationResult = $this->validationProcessor->process($this->getAuthValidator(), $credentials);

        // Retrieve the user
        $user = $this->getUserRepository()->getByEmailOrLogin($validationResult->login);

        if ($user === null) {
            throw new UserNotFoundException($validationResult->login);
        }

        $this->getUserRepository()->checkUserPasswordHash($validationResult->password, $user->password);

        if (!$user->verified && config('auth.registration.confirm_email')) {
            throw new AccountNotVerifiedException();
        }

        $user = user()->get($user->id);

        user()->setCurrentUser($user);

        events()->dispatch(new UserLoggedInEvent($user), UserLoggedInEvent::NAME);

        return $user;
    }

    /**
     * Validates registration data using validationProcessor.
     *
     * @param array $data The data to validate.
     * @return mixed The result of the validation.
     */

    private function validateRegistrationData(array $data)
    {
        return $this->validationProcessor->process($this->getRegisterValidator(), $data);
    }

    /**
     * Checks for user duplicity in the database based on email and login.
     *
     * @param string $email The user's email.
     * @param string $login The user's login.
     * @throws DuplicateEmailException|DuplicateLoginException If a user with the given email or login already exists.
     */
    private function checkUserDuplicity(string $email, string $login): void
    {
        $this->getUserRepository()->checkDuplicity($email);
        $this->getUserRepository()->checkDuplicity($login);
    }

    /**
     * Creates a new user entity and saves it to the database.
     *
     * @param object $userData The validated user data.
     * @return User The created user entity.
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    private function createNewUser(object $userData): User
    {
        $user = new User();
        $user->email = $userData->email;
        $user->login = $userData->login;
        $user->name = $userData->name;
        $user->avatar = config('profile.default_avatar');
        $user->banner = config('profile.default_banner');
        $user->verified = !app('auth.registration.confirm_email');

        $user->setPassword($userData->password);

        transaction($user)->run();

        return $user;
    }
    
    /**
     * Authenticate a user by userid.
     * 
     * @param int $userId The user id.
     * 
     * @throws UserNotFoundException|AccountNotVerifiedException If the user is not found.
     * 
     * @return User The authenticated user.
     */
    public function authenticateByUserId(int $userId): User
    {
        // Throttle requests
        $this->throttle('login');

        // Retrieve the user
        $user = user()->get($userId);

        if ($user === null) {
            throw new UserNotFoundException($userId);
        }

        if (!$user->verified && config('auth.registration.confirm_email')) {
            throw new AccountNotVerifiedException();
        }

        user()->setCurrentUser($user);

        events()->dispatch(new UserLoggedInEvent($user), UserLoggedInEvent::NAME);

        return $user;
    }

    /**
     * Remember the authenticated user by creating and storing a token.
     *
     * @param User $user The authenticated user.
     * @param UserDevice $device The user's device.
     *
     * @return string|null The remember token.
     * @throws Throwable
     */
    public function rememberUser(User $user, UserDevice $device): ?string
    {
        if (!config('auth.remember_me')) {
            return null;
        }

        // Create a remember token
        $rememberToken = $this->generateRandomToken();

        // Create and save the remember token
        $tokenEntity = new RememberToken();
        $tokenEntity->user = $user;
        $tokenEntity->userDevice = $device;
        $tokenEntity->token = $rememberToken;
        $tokenEntity->lastUsedAt = new \DateTime();

        transaction($tokenEntity)->run();

        cookie()->set('remember_token', $rememberToken, time() + config('auth.remember_me_duration'));

        return $rememberToken;
    }

    /**
     * Create the remember token for authentication.
     *
     * @param User $user - Who will be remembered.
     *
     * @return string|null The remember token.
     * @throws Throwable
     */
    public function createRememberToken(User $user): ?string
    {
        $deviceDetails = $_SERVER['HTTP_USER_AGENT']; // temporarily
        $ip = request()->getClientIp();

        // Find if there's already a device with these details and IP
        $userDevice = $this->getUserDeviceRepository()->findOne(['deviceDetails' => $deviceDetails, 'ip' => $ip]);

        // If no device found, create a new one
        if (!$userDevice) {
            $userDevice = new UserDevice();
            $userDevice->user = $user;
            $userDevice->deviceDetails = $deviceDetails;
            $userDevice->ip = $ip;

            // Persist the UserDevice
            transaction($userDevice)->run();
        }

        return $this->rememberUser($user, $userDevice);
    }

    /**
     * Verify the specified user.
     *
     * @param string $token The verification token.
     * @throws AccountNotVerifiedException|Throwable If the token does not match or is expired.
     * 
     * @return User|false The verified user.
     */
    public function verifyUser(string $token)
    {
        if (!config('auth.registration.confirm_email')) {
            return false;
        }

        $verificationTokenRepository = rep(VerificationToken::class);

        $verificationToken = $verificationTokenRepository->select()->where(['token' => $token])->load('user')->fetchOne();

        if ($verificationToken === null || $verificationToken->expiresAt < new \DateTime()) {
            throw new AccountNotVerifiedException();
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
     * @param User $user The user for whom the verification token is to be created.
     *
     * @return VerificationToken The created verification token.
     * @throws Throwable
     */
    public function createVerificationToken(User $user): VerificationToken
    {
        // Generate a random token
        $verificationTokenValue = $this->generateRandomToken();

        // Set expiration date to 24 hours from now
        $expiresAt = new \DateTime();
        $expiresAt->modify('+24 hours');

        // Create a new VerificationToken entity
        $verificationToken = new VerificationToken();
        $verificationToken->user = $user;
        $verificationToken->token = $verificationTokenValue;
        $verificationToken->expiresAt = $expiresAt;

        // Save the VerificationToken
        transaction($verificationToken)->run();

        return $verificationToken;
    }

    /**
     * Logout the user by clearing the remember token and the session.
     */
    public function logout(): void
    {
        if (config('auth.remember_me') && $token = user()->getUserToken()) {
            $rep = $this->getRememberTokenRepository()->findOne(['token' => $token]);

            $rep && transaction($rep, 'delete')->run();
        }

        session()->remove('user_id');
        cookie()->remove('remember_token');

        events()->dispatch(new UserLoggedOutEvent(), UserLoggedOutEvent::NAME);
    }

    /**
     * @throws Throwable
     * @throws UserNotFoundException
     */
    public function createPasswordResetToken(string $loginOrEmail): PasswordResetToken
    {
        $this->throttle('reset_password', 3, 60, 1);

        // Set expiration date to 24 hours from now
        $expiresAt = new \DateTime();
        $expiresAt->modify('+24 hours');

        // Retrieve the user
        $user = $this->getUserRepository()->getByEmailOrLogin($loginOrEmail);

        if ($user === null) {
            throw new UserNotFoundException($loginOrEmail);
        }

        // Generate a random token
        $passwordResetTokenValue = $this->generateRandomToken();

        // Create a new PasswordResetToken entity
        $passwordResetToken = new PasswordResetToken();
        $passwordResetToken->user = $user;
        $passwordResetToken->token = $passwordResetTokenValue;
        $passwordResetToken->expiry = $expiresAt;

        // Save the PasswordResetToken
        transaction($passwordResetToken)->run();

        events()->dispatch(new PasswordResetRequestedEvent($user, $passwordResetToken), PasswordResetRequestedEvent::NAME);

        return $passwordResetToken;
    }

    /**
     * @throws PasswordResetTokenNotFoundException
     */
    public function checkPasswordResetToken(string $token): object
    {
        $passwordResetTokenRepository = rep(PasswordResetToken::class);

        $passwordResetToken = $passwordResetTokenRepository->select()->where(['token' => $token])->load('user')->fetchOne();

        if ($passwordResetToken === null) {
            throw new PasswordResetTokenNotFoundException($token);
        }

        return $passwordResetToken;
    }

    /**
     * @throws Throwable
     * @throws PasswordResetTokenNotFoundException
     * @throws PasswordResetTokenExpiredException
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        // Retrieve the password reset token
        $passwordResetToken = $this->checkPasswordResetToken($token);

        // Check if the token is expired
        $isExpired = $passwordResetToken->expiry < new \DateTime();

        // Delete the password reset token
        transaction($passwordResetToken, 'delete')->run();

        if ($isExpired) {
            throw new PasswordResetTokenExpiredException();
        }

        // Retrieve the user
        $user = $passwordResetToken->user;

        // Reset the password
        $user->setPassword($newPassword);

        // Save the user
        transaction($user)->run();
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
        throttler()->throttle(
            ['action' => $key, request()->ip()],
            $maxRequest,
            $perMinute,
            $burstiness
        );
    }

    /**
     * Generate a random token.
     *
     * @return string The generated token.
     */
    protected function generateRandomToken(): string
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    protected function getUserDeviceRepository(): Repository
    {
        if( $this->userDeviceRepository !== null )
            return $this->userDeviceRepository;
        
        return $this->userDeviceRepository = rep(UserDevice::class);
    }

    protected function getRememberTokenRepository(): Repository
    {
        if( $this->rememberTokenRepository !== null )
            return $this->rememberTokenRepository;
        
        return $this->rememberTokenRepository = rep(RememberToken::class);
    }

    protected function getUserRepository(): UserRepository
    {
        if( $this->userRepository !== null )
            return $this->userRepository;

        /** @var UserRepository $userRepository */
        $userRepository = rep(User::class);
        
        return $this->userRepository = $userRepository;
    }

    protected function getRegisterValidator(): Schema
    {
        return Expect::structure([
            'email' => Expect::email()->required(),
            'login' => Expect::string()
                ->min(config('auth.validation.login.min_length'))
                ->max(config('auth.validation.login.max_length'))
                ->assert(function ($value) {
                    return preg_match('/^[a-zA-Z0-9]*$/', $value);
                }, __('auth.registration.login_symbols'))
                ->required(),
            'name' => Expect::string()
                ->min(config('auth.validation.name.min_length'))
                ->max(config('auth.validation.name.max_length'))
                ->required(),
            'password' => Expect::string()
                ->min(config('auth.validation.password.min_length'))
                ->max(config('auth.validation.password.max_length'))
                ->required(),
            'remember_me' => Expect::bool()->required(),
            'x_csrf_token' => Expect::string()
        ]);
    }

    protected function getAuthValidator(): Schema
    {
        // Зачем нам ВАЛИДАЦИЯ ДЛЯ ЛОГИНА ВОПРОС?
        return Expect::structure([
            'login' => Expect::string()
                // ->min(config('auth.validation.login.min_length'))
                // ->max(config('auth.validation.login.max_length'))
                ->required(),
            'password' => Expect::string()
                // ->min(config('auth.validation.password.min_length'))
                // ->max(config('auth.validation.password.max_length'))
                ->required(),
            'x_csrf_token' => Expect::string(),
            'remember_me' => Expect::bool()->required(),
        ]);
    }
}