<?php

namespace Flute\Core\Modules\Auth\Services\Concerns;

use Flute\Core\Database\Entities\User;
use Flute\Core\Database\Entities\UserSocialNetwork;
use Hybridauth\User\Profile;
use Throwable;

trait HandlesSocialProfileSync
{
    private function findUserBySocialProfile(Profile $profile): ?User
    {
        $userSocial = UserSocialNetwork::query()
            ->load(['user', 'user.roles'])
            ->where('user.isTemporary', false)
            ->where(['value' => $profile->identifier])
            ->fetchOne();

        return $userSocial ? $userSocial->user : null;
    }

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
        } catch (Throwable $e) {
            logs()->error('Error deleting temporary user: ' . $e->getMessage());
        }
    }

    private function updateAvatarFromProfileIfNeeded(User $user, Profile $profile): void
    {
        try {
            $currentAvatar = $user->avatar ?? '';
            $defaultAvatar = config('profile.default_avatar');

            $isDefault =
                empty($currentAvatar)
                || $currentAvatar === $defaultAvatar
                || str_contains($currentAvatar, basename($defaultAvatar));

            $photoUrl = $profile->photoURL ?? null;

            if ($isDefault && $photoUrl) {
                $user->avatar = $photoUrl;

                try {
                    transaction($user)->run();
                } catch (Throwable $e) {
                    logs()->warning('Failed to update user avatar from social profile: ' . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            logs()->warning('Error while updating avatar from social profile: ' . $e->getMessage());
        }
    }

    private function updateBannerFromProfileIfNeeded(User $user, Profile $profile): void
    {
        try {
            $currentBanner = $user->banner ?? '';
            $defaultBanner = config('profile.default_banner');

            $isDefault =
                empty($currentBanner)
                || $currentBanner === $defaultBanner
                || str_contains($currentBanner, basename($defaultBanner));

            $bannerUrl = $profile->data['bannerURL'] ?? null;

            if ($isDefault && $bannerUrl) {
                $user->banner = $bannerUrl;

                try {
                    $user->saveOrFail();
                } catch (Throwable $e) {
                    logs()->warning('Failed to update user banner from social profile: ' . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            logs()->warning('Error while updating banner from social profile: ' . $e->getMessage());
        }
    }
}
