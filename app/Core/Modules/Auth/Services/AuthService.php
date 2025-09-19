<?php

namespace Flute\Core\Modules\Auth\Services;

use Exception;
use Flute\Core\Database\Entities\PasswordResetToken;
use Flute\Core\Database\Entities\RememberToken;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\VerificationToken;
use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Exceptions\IncorrectPasswordException;
use Flute\Core\Exceptions\PasswordResetTokenExpiredException;
use Flute\Core\Exceptions\PasswordResetTokenNotFoundException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Router\Contracts\RouterInterface;
use Throwable;

class AuthService
{
    protected AuthenticationService $auth;

    protected RouterInterface $dispatcher;

    /**
     * AuthService constructor.
     *
     * @param AuthenticationService $authenticationService Instance of AuthenticationService
     */
    public function __construct(AuthenticationService $authenticationService, RouterInterface $routeDispatcher)
    {
        $this->auth = $authenticationService;
        $this->dispatcher = $routeDispatcher;
    }

    /**
     * Logout the current user.
     */
    public function logout(): void
    {
        $this->auth->logout();
    }

    /**
     * Authenticates a user with provided credentials.
     * If successful, sets the user session and, if 'remember' is true, creates a remember token.
     *
     * @param array $credentials User credentials.
     * @param bool $remember Flag indicating if remember token should be created.
     * @param bool $fromSocial Flag indicating if authentication is from social network.
     *
     * @throws UserNotFoundException If the user is not found.
     * @throws IncorrectPasswordException If the password is incorrect.
     * @throws TooManyRequestsException If too many requests were made.
     *
     * @return User Authentication result.
     */
    public function authenticate(array $credentials, bool $remember = false, bool $fromSocial = false): User
    {
        $this->logout();

        $authResult = $this->auth->authenticate($credentials, $fromSocial);

        // Save user to session
        session()->set('user_id', $authResult->id);

        if ($remember) {
            $this->auth->createRememberToken($authResult);
        }

        return $authResult;
    }

    /**
     * Authenticate user by userId.
     *
     * @param bool $fromSocial Flag indicating if authentication is from social network.
     *
     * @throws UserNotFoundException If the user is not found.
     */
    public function authenticateById(int $userId, bool $remember = false, bool $fromSocial = false): User
    {
        $this->logout();

        $authResult = $this->auth->authenticateByUserId($userId, $fromSocial);

        // Save user to session
        session()->set('user_id', $authResult->id);

        if ($remember) {
            $this->auth->createRememberToken($authResult);
        }

        return $authResult;
    }

    /**
     * Register a user with provided credentials.
     * If successful, sets the user session and, if 'remember' is true, creates a remember token.
     * Does not set session or create token if email confirmation is required.
     *
     * @param array $credentials User credentials.
     * @param bool $remember Flag indicating if remember token should be created.
     *
     * @throws TooManyRequestsException
     * @return User Registration result.
     */
    public function register(array $credentials, bool $remember = false): User
    {
        $this->logout();

        $registerResult = $this->auth->register($credentials);

        if (!app('auth.registration.confirm_email')) {
            session()->set('user_id', $registerResult->id);

            if ($remember) {
                $this->auth->createRememberToken($registerResult);
            }
        }

        return $registerResult;
    }

    /**
     * Generate a password reset token for the user identified by the given login or email.
     *
     * @param string $loginOrEmail User's login or email.
     *
     * @throws UserNotFoundException
     * @return PasswordResetToken The result of creating password reset token.
     */
    public function resetPassword(string $loginOrEmail): PasswordResetToken
    {
        return $this->auth->createPasswordResetToken($loginOrEmail);
    }

    /**
     * Check valid token for reset password.
     *
     * @param string $token Password reset token.
     *
     * @throws PasswordResetTokenNotFoundException If the token is not found.
     */
    public function checkPasswordResetToken(string $token): object
    {
        return $this->auth->checkPasswordResetToken($token);
    }

    /**
     * Reset password by the given token.
     *
     * @param string $token Password reset token.
     * @param string $newPassword new password.
     *
     * @throws PasswordResetTokenExpiredException
     */
    public function resetPasswordToken(string $token, string $newPassword)
    {
        $this->auth->resetPassword($token, $newPassword);
    }

    /**
     * Get user information from token.
     * @throws Exception
     */
    public function getInfoFromToken($token): RememberToken
    {
        $rememberToken = RememberToken::query()
            ->load(['user', 'user.roles', 'userDevice'])
            ->fetchOne(['token' => hash('sha256', $token)]);

        if (!$rememberToken) {
            throw new Exception("No token found with value {$token}");
        }

        return $rememberToken;
    }

    /**
     * Delete the remember token from the database.
     *
     * @throws Throwable
     * @return void
     */
    public function deleteAuthToken(string $token)
    {
        $token = RememberToken::findOne(['token' => hash('sha256', $token)]);

        $token && transaction($token, 'delete')->run();
    }

    /**
     * Verify the specified user.
     *
     * @param string $token The verification token.
     * @throws AccountNotVerifiedException If the token does not match or is expired.
     */
    public function verify(string $token): bool
    {
        $user = $this->auth->verifyUser($token);

        if ($user && !user()->isLoggedIn()) {
            $this->auth->authenticateByUserId($user->id);

            return true;
        }

        return false;
    }

    /**
     * Create a verification token for the given user.
     *
     * @param User $user The user for whom the verification token is to be created.
     *
     * @return VerificationToken The created verification token.
     */
    public function createVerificationToken(User $user): VerificationToken
    {
        return $this->auth->createVerificationToken($user);
    }
}
