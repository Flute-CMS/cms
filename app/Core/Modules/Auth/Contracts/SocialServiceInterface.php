<?php

namespace Flute\Core\Modules\Auth\Contracts;

use Flute\Core\Database\Entities\SocialNetwork;
use Flute\Core\Database\Entities\User;
use Hybridauth\User\Profile;

interface SocialServiceInterface
{
    public function registerSocialNetwork(array $config): void;

    public function authenticateWithRegister(string $providerName): User;

    public function authenticate(string $providerName, bool $bind = false): array;

    public function registerNewUser(Profile $profile, SocialNetwork $socialNetwork): User;

    public function bindSocialNetwork(User $user, string $providerName): void;

    public function getAllProviders(bool $onlyAllowed = true): array;

    public function isEmpty(): bool;

    public function clearAuthData(): void;
}
