<?php


namespace Flute\Core\Services;

use Exception;
use Flute\Core\Auth\AuthenticationService;
use Flute\Core\Database\Entities\PasswordResetToken;
use Flute\Core\Database\Entities\RememberToken;
use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\VerificationToken;
use Flute\Core\Exceptions\AccountNotVerifiedException;
use Flute\Core\Exceptions\PasswordResetTokenExpiredException;
use Flute\Core\Exceptions\PasswordResetTokenNotFoundException;
use Flute\Core\Exceptions\UserNotFoundException;
use Flute\Core\Http\Controllers\Auth\AuthController;
use Flute\Core\Http\Controllers\Auth\PasswordResetController;
use Flute\Core\Http\Controllers\Auth\SocialAuthController;
use Flute\Core\Http\Middlewares\CSRFMiddleware;
use Flute\Core\Http\Middlewares\GuestMiddleware;
use Flute\Core\Http\Middlewares\isAuthenticatedMiddleware;
use Flute\Core\Router\RouteDispatcher;
use Flute\Core\Router\RouteGroup;
use Flute\Core\Exceptions\IncorrectPasswordException;
use Flute\Core\Exceptions\TooManyRequestsException;
use Nette\Schema\ValidationException;
use Throwable;

class AuthService
{
    protected AuthenticationService $auth;
    protected RouteDispatcher $dispatcher;

    /**
     * AuthService constructor.
     *
     * @param AuthenticationService $authenticationService Instance of AuthenticationService
     */
    public function __construct(AuthenticationService $authenticationService, RouteDispatcher $routeDispatcher)
    {
        $this->auth = $authenticationService;
        $this->dispatcher = $routeDispatcher;
    }

    /**
     * Logout the current user.
     *
     * @return void
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
     * 
     * @throws ValidationException If the credentials are not valid.
     * @throws UserNotFoundException If the user is not found.
     * @throws IncorrectPasswordException If the password is incorrect.
     * @throws TooManyRequestsException If too many requests were made.
     * 
     * @return User Authentication result.
     */
    public function authenticate(array $credentials, bool $remember = false): User
    {
        $this->logout();

        $authResult = $this->auth->authenticate($credentials);

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
     * @param int $userId
     * @param bool $remember
     *
     * @return User
     * @throws UserNotFoundException If the user is not found.
     */
    public function authenticateById(int $userId, bool $remember = false): User
    {
        $this->logout();

        $authResult = $this->auth->authenticateByUserId($userId);

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
     * @return User Registration result.
     * @throws TooManyRequestsException
     */
    public function register(array $credentials, bool $remember = false): User
    {
        $this->logout();

        $registerResult = $this->auth->register($credentials);

        if (!app('auth.registration.confirm_email')) {
            // Save user to session
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
     * @return PasswordResetToken The result of creating password reset token.
     * @throws UserNotFoundException
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
     * @return object
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
     * @param $token
     * @return RememberToken
     * @throws Exception
     */
    public function getInfoFromToken($token): RememberToken
    {
        // Get the token repository
        $tokenRepository = rep(RememberToken::class);

        // Find the token
        /** @var RememberToken $rememberToken */
        $rememberToken = $tokenRepository
            ->select()
            ->load(['user', 'userDevice'])
            ->fetchOne(['token' => $token]);

        if (!$rememberToken) {
            throw new Exception("No token found with value {$token}");
        }

        // Return the associated user
        return $rememberToken;
    }

    /**
     * Delete the remember token from the database.
     *
     * @param string $token
     *
     * @return void
     * @throws Throwable
     */
    public function deleteAuthToken(string $token)
    {
        $tokenRepository = rep(RememberToken::class);

        $token = $tokenRepository->findOne(['token' => $token]);

        $token && transaction($token, 'delete')->run();
    }

    /**
     * Verify the specified user.
     *
     * @param string $token The verification token.
     * @throws AccountNotVerifiedException If the token does not match or is expired.
     * 
     * @return bool
     */
    public function verify(string $token): bool
    {
        $user = $this->auth->verifyUser($token);

        if ($user) {
            session()->set('user_id', $user->id);

            if (config('auth.remember_me')) {
                $this->auth->createRememberToken($user);
            }

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

    /**
     * Set routes for authentication.
     */
    public function setRoutes()
    {
        $this->dispatcher->group(function (RouteGroup $routeGroup) {
            if (!user()->hasPermission('admin.pages'))
                $routeGroup->middleware(GuestMiddleware::class);

            // Auth
            $routeGroup->get('/login', [AuthController::class, 'getLogin']);

            if (!config('auth.only_social', false) || (config('auth.only_social') && social()->isEmpty())) {
                $routeGroup->get('/register', [AuthController::class, 'getRegister']);
            }

            $routeGroup->get('/confirm/{token}', [AuthController::class, 'getConfirmation']);

            // Social auth
            $routeGroup->group(function (RouteGroup $routeGroupT) {
                $routeGroupT->middleware(GuestMiddleware::class);
                $routeGroupT->get('/social/{provider}', [SocialAuthController::class, 'redirectToProvider']);
            });

            // Social auth register
            $routeGroup->get('/social/register', [SocialAuthController::class, 'getSocialRegister']);

            if (config('auth.reset_password') && (!config('auth.only_social', false) || (config('auth.only_social') && social()->isEmpty()))) {
                $routeGroup->get('/reset', [PasswordResetController::class, 'getReset']);
                $routeGroup->get('/reset/{token}', [PasswordResetController::class, 'getResetWithToken']);
            }

            // Post routes with CSRF protection
            $routeGroup->group(function (RouteGroup $routeGroup) {
                $routeGroup->middleware(GuestMiddleware::class);
                $routeGroup->middleware(CSRFMiddleware::class);

                if (!config('auth.only_social', false) || (config('auth.only_social') && social()->isEmpty())) {
                    $routeGroup->post('/register', [AuthController::class, 'postRegister']);
                    $routeGroup->post('/login', [AuthController::class, 'postLogin']);

                    if (config('auth.reset_password')) {
                        $routeGroup->post('/reset', [PasswordResetController::class, 'postReset']);
                        $routeGroup->post('/reset/{token}', [PasswordResetController::class, 'postResetWithToken']);
                    }
                }

                $routeGroup->post('/social/register', [SocialAuthController::class, 'postSocialRegister']);
            });
        });

        $this->dispatcher->get('/logout', [AuthController::class, 'getLogout'], [isAuthenticatedMiddleware::class]);
    }
}