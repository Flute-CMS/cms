<?php

namespace Flute\Core\Modules\Profile\Components;

use Flute\Core\Database\Entities\User;
use Flute\Core\Support\FileUploader;
use Flute\Core\Support\FluteComponent;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EditMainComponent extends FluteComponent
{
    public ?string $name = null;
    public ?string $email = null;
    public ?string $login = null;
    public ?string $uri = null;
    public ?string $theme = null;
    public ?string $privacy = null;

    public ?string $current_password = null;
    public ?string $new_password = null;
    public ?string $new_password_confirmation = null;
    public ?string $delete_confirmation = null;

    /**
     * @var UploadedFile|null
     */
    public $avatar = null;

    /**
     * @var UploadedFile|null
     */
    public $banner = null;

    public ?User $user = null;

    /**
     * Initialize the component.
     */
    public function mount()
    {
        $this->user = user()->getCurrentUser();
    }

    /**
     * Render the component view.
     *
     * @return mixed
     */
    public function render()
    {
        return $this->view('flute::components.profile-tabs.edit.main', [
            'user' => $this->user,
        ]);
    }

    /**
     * Save the main user information.
     */
    public function saveMain()
    {
        if ($this->validateSaveMain()) {
            try {
                $this->updateUserMainInfo();
                user()->updateUser($this->user);
                $this->flashMessage(__('profile.edit.main.basic_information.save_changes_success'), 'success');
            } catch (\Exception $e) {
                $this->inputError('name', $e->getMessage());
            }
        }
    }

    public function saveTheme()
    {
        if (
            $this->validate([
                'theme' => 'required|in:dark,light,system',
            ])
        ) {
            if ($this->theme === 'system') {
                cookie()->remove('theme');
            } else {
                cookie()->set('theme', $this->theme, httpOnly: false);

                $this->dispatchBrowserEvent('switch-theme', [
                    'theme' => $this->theme,
                ]);
            }

            $this->flashMessage(__('def.success'));
        }
    }

    public function savePrivacy()
    {
        if (
            $this->validate([
                'privacy' => 'required|in:hidden,visible',
            ])
        ) {
            $this->user->hidden = $this->privacy === 'hidden';

            user()->updateUser($this->user);

            $this->flashMessage(__('def.success'));
        }
    }

    /**
     * Update the main user information.
     */
    protected function updateUserMainInfo()
    {
        $this->user->name = $this->name;

        if ($this->email !== $this->user->email) {
            $this->user->email = $this->email;

            if (config('auth.registration.confirm_email')) {
                $this->user->verified = false;
            }
        }

        $this->user->login = $this->login;

        if ($this->uri) {
            $this->user->uri = $this->uri;
        }
    }

    /**
     * Save the user's profile images (avatar and banner).
     */
    public function saveImages()
    {
        if (!$this->validateImages()) {
            return;
        }

        /** @var FileUploader $uploader */
        $uploader = app(FileUploader::class);
        $uploadsDir = realpath(BASE_PATH . '/public/assets/uploads');

        if ($uploadsDir === false) {
            $this->addUploadDirectoryError();

            return;
        }

        $avatarError = $this->processImageUpload('avatar', $uploader, $uploadsDir, config('profile.max_avatar_size'), 'events.profile_avatar_updated');
        $bannerError = $this->processImageUpload('banner', $uploader, $uploadsDir, config('profile.max_banner_size'), 'events.profile_banner_updated');

        if ($avatarError || $bannerError) {
            $this->handleImageErrors($avatarError, $bannerError);

            return;
        }

        try {
            user()->updateUser($this->user);
            $this->flashMessage(__('profile.edit.main.profile_images.save_changes_success'), 'success');
        } catch (\Exception $e) {
            logs()->error($e);
            $this->addUnknownError();
        }
    }

    /**
     * Handle the upload of a single image.
     *
     * @param string $field
     * @param FileUploader $uploader
     * @param string $uploadsDir
     * @param int $maxSize
     * @param string $logEvent
     * @return string|null
     */
    protected function processImageUpload(string $field, FileUploader $uploader, string $uploadsDir, int $maxSize, string $logEvent): ?string
    {
        $file = $this->$field;
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $newFile = $uploader->uploadImage($file, $maxSize);

                if ($newFile === null) {
                    throw new \RuntimeException(__('profile.edit.main.upload_failed', ['field' => $field]));
                }

                $this->removeOldFile($field, $uploadsDir);
                $this->user->$field = $newFile;

                return null;
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    /**
     * Remove the old image file.
     *
     * @param string $field
     * @param string $uploadsDir
     */
    protected function removeOldFile(string $field, string $uploadsDir): void
    {
        $oldFile = $this->user->$field;
        if ($oldFile && $oldFile !== config("profile.default_{$field}")) {
            $fullPath = BASE_PATH . '/public/' . $oldFile;
            if (realpath($fullPath) && strpos(realpath($fullPath), $uploadsDir) === 0 && fs()->exists($fullPath)) {
                fs()->remove($fullPath);
            }
        }
    }

    /**
     * Add errors when the upload directory does not exist.
     */
    protected function addUploadDirectoryError(): void
    {
        $this->inputError('avatar', __('profile.edit.main.upload_directory_error'));
        $this->inputError('banner', __('profile.edit.main.upload_directory_error'));
    }

    /**
     * Handle and display image upload errors.
     *
     * @param string|null $avatarError
     * @param string|null $bannerError
     */
    protected function handleImageErrors(?string $avatarError, ?string $bannerError): void
    {
        if ($avatarError) {
            $this->inputError('avatar', $avatarError);
        }
        if ($bannerError) {
            $this->inputError('banner', $bannerError);
        }
    }

    /**
     * Add a generic unknown error.
     */
    protected function addUnknownError(): void
    {
        $this->inputError('avatar', __('def.unknown_error'));
        $this->inputError('banner', __('def.unknown_error'));
    }

    /**
     * Save the new user password.
     */
    public function savePassword()
    {
        if (!$this->validateSavePassword()) {
            return;
        }

        if (!empty($this->user->password) && !password_verify($this->current_password, $this->user->password)) {
            $this->inputError('current_password', __('profile.edit.main.change_password.current_password_incorrect'));

            return;
        }

        try {
            $this->user->setPassword($this->new_password);
            user()->updateUser($this->user);

            $this->flashMessage(__('profile.edit.main.change_password.save_changes_success'), 'success');
        } catch (\Exception $e) {
            $this->inputError('new_password', $e->getMessage());
        }
    }

    /**
     * Delete the user account.
     */
    public function deleteAccount()
    {
        if ($this->validateDeleteAccount()) {
            if (!$this->confirmed('delete_account_confirmation')) {
                $this->confirm(
                    actionKey: 'delete_account_confirmation',
                    message: __('profile.edit.main.delete_account.confirm_message'),
                    type: 'error',
                    confirmText: __('def.delete'),
                    cancelText: __('def.cancel')
                );

                return;
            }

            try {
                user()->deleteUser($this->user);

                auth()->logout();

                $this->flashMessage(__('profile.edit.main.delete_account.delete_success'), 'success');
            } catch (\Exception $e) {
                $this->inputError('delete_confirmation', __('profile.edit.main.delete_account.delete_failed'));
            }
        }
    }

    /**
     * Validate the main user information.
     *
     * @return bool
     */
    protected function validateSaveMain(): bool
    {
        return $this->validate([
            'name' => [
                'required',
                'human-name',
                'min-str-len:' . config('auth.validation.name.min_length'),
                'max-str-len:' . config('auth.validation.name.max_length'),
            ],
            'login' => [
                'required',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'min-str-len:' . config('auth.validation.login.min_length'),
                'max-str-len:' . config('auth.validation.login.max_length'),
                'unique:users,login,' . $this->user->id,
            ],
            'email' => [
                'required',
                'email',
                'max-str-len:255',
                'unique:users,email,' . $this->user->id,
            ],
            'uri' => 'nullable|string|max-str-len:60|regex:/^[a-zA-Z0-9._-]+$/|unique:users,uri,' . $this->user->id,
        ]);
    }

    /**
     * Validate the uploaded images.
     *
     * @return bool
     */
    protected function validateImages(): bool
    {
        $this->avatar = request()->files->get('avatar');
        $this->banner = request()->files->get('banner');

        $rules = [
            'avatar' => $this->avatar
                ? 'image|max-file-size:' . (config('profile.max_avatar_size') * 1024)
                : 'nullable|image|max-file-size:' . (config('profile.max_avatar_size') * 1024),
            'banner' => $this->banner
                ? 'image|max-file-size:' . (config('profile.max_banner_size') * 1024)
                : 'nullable|image|max-file-size:' . (config('profile.max_banner_size') * 1024),
        ];

        return $this->validate($rules);
    }

    /**
     * Validate the password change data.
     *
     * @return bool
     */
    protected function validateSavePassword(): bool
    {
        if (empty($this->user->login) || empty($this->user->email)) {
            $this->flashMessage(__('profile.edit.main.password_change.login_and_email_required'), 'error');

            return false;
        }

        $requiresCurrentPassword = !empty($this->user->password);

        $rules = [
            'new_password' => [
                'required',
                'confirmed',
                'min-str-len:' . config('auth.validation.password.min_length'),
                'max-str-len:' . config('auth.validation.password.max_length'),
            ],
            'new_password_confirmation' => 'required|string|min-str-len:' . config('auth.validation.password.min_length'),
        ];

        if ($requiresCurrentPassword) {
            $rules['current_password'] = 'required|string';
        }

        $validation = $this->validate($rules, null, [
            'new_password.confirmed' => __('profile.edit.main.change_password.passwords_do_not_match'),
        ]);

        return $validation;
    }

    /**
     * Validate the account deletion confirmation.
     *
     * @return bool
     */
    protected function validateDeleteAccount(): bool
    {
        return $this->validate([
            'delete_confirmation' => 'required|in:' . $this->user->login,
        ], null, [
            'delete_confirmation.in' => __('profile.edit.main.delete_account.confirmation_error'),
        ]);
    }
}
