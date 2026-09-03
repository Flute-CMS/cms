<?php

namespace Flute\Admin\Packages\MainSettings\Screens\Concerns;

use Flute\Core\Support\FileUploader;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

trait HasImageUploads
{
    public function saveFluteImages()
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

        $avatarError = $this->processImageUpload('logo', $uploader, $uploadsDir);
        $logoLightError = $this->processImageUpload('logo_light', $uploader, $uploadsDir);
        $bannerError = $this->processImageUpload('bg_image', $uploader, $uploadsDir);
        $bannerLightError = $this->processImageUpload('bg_image_light', $uploader, $uploadsDir);
        $faviconError = $this->processFixedFileReplace('favicon', BASE_PATH . '/public/favicon.ico');
        $socialImageError = $this->processFixedFileReplace(
            'social_image',
            BASE_PATH . '/public/assets/img/social-image.png',
        );

        if (
            $avatarError
            || $logoLightError
            || $bannerError
            || $bannerLightError
            || $faviconError
            || $socialImageError
        ) {
            $this->flashMessage(
                $avatarError ?? $logoLightError ?? $bannerError ?? $bannerLightError ?? $faviconError
                    ?? $socialImageError,
                'error',
            );

            return;
        }

        if (!isset($this->logo)) {
            $logoClear = request()->input('logo_clear');
            if ($logoClear === '1') {
                config()->set('app.logo', 'assets/img/logo.svg');
            }
        }

        if (!isset($this->logo_light)) {
            $logoLightClear = request()->input('logo_light_clear');
            if ($logoLightClear === '1') {
                config()->set('app.logo_light', 'assets/img/logo-light.svg');
            }
        }

        if (!isset($this->bg_image)) {
            $bgImageClear = request()->input('bg_image_clear');
            if ($bgImageClear === '1') {
                config()->set('app.bg_image', '');
            }
        }

        if (!isset($this->bg_image_light)) {
            $bgImageLightClear = request()->input('bg_image_light_clear');
            if ($bgImageLightClear === '1') {
                config()->set('app.bg_image_light', '');
            }
        }

        try {
            config()->save();
            $this->invalidateConfig('app');
            $this->invalidateSettingsCache();
            $this->flashMessage(__('admin-main-settings.messages.flute_images_saved'), 'success');
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    public function saveProfileImages()
    {
        if (!$this->validateProfileImages()) {
            return;
        }

        /** @var FileUploader $uploader */
        $uploader = app(FileUploader::class);
        $uploadsDir = realpath(BASE_PATH . '/public/assets/uploads');

        if ($uploadsDir === false) {
            $this->addProfileUploadDirectoryError();

            return;
        }

        $avatarError = $this->processProfileImageUpload(
            'default_avatar',
            $uploader,
            $uploadsDir,
            'profile.default_avatar',
            'assets/img/no_avatar.webp',
        );
        $bannerError = $this->processProfileImageUpload(
            'default_banner',
            $uploader,
            $uploadsDir,
            'profile.default_banner',
            'assets/img/no_banner.webp',
        );

        if ($avatarError || $bannerError) {
            $this->flashMessage($avatarError ?? $bannerError, 'error');

            return;
        }

        try {
            config()->save();
            $this->flashMessage(__('admin-main-settings.messages.profile_images_saved'), 'success');

            $this->clearCache();
        } catch (Throwable $e) {
            logs()->error($e);
            $this->flashMessage(__('admin-main-settings.messages.unknown_error'), 'error');
        }
    }

    protected function validateImages(): bool
    {
        $this->logo = request()->files->get('logo');
        $this->logo_light = request()->files->get('logo_light');
        $this->bg_image = request()->files->get('bg_image');
        $this->bg_image_light = request()->files->get('bg_image_light');
        $this->favicon = request()->files->get('favicon');
        $this->social_image = request()->files->get('social_image');

        $rules = [
            'logo' => $this->logo ? 'image|max-file-size:10240' : 'nullable|image|max-file-size:10240',
            'logo_light' => $this->logo_light ? 'image|max-file-size:10240' : 'nullable|image|max-file-size:10240',
            'bg_image' => $this->bg_image ? 'image|max-file-size:10240' : 'nullable|image|max-file-size:10240',
            'bg_image_light' => $this->bg_image_light
                ? 'image|max-file-size:10240'
                : 'nullable|image|max-file-size:10240',
            'favicon' => $this->favicon ? 'mimes:ico|max-file-size:2048' : 'nullable|mimes:ico|max-file-size:2048',
            'social_image' => $this->social_image
                ? 'image|mimes:png|max-file-size:10240'
                : 'nullable|image|mimes:png|max-file-size:10240',
        ];

        return $this->validate($rules);
    }

    protected function processImageUpload(string $field, FileUploader $uploader, string $uploadsDir): ?string
    {
        $file = $this->$field;
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $newFile = $uploader->uploadImage($file, 10);

                if ($newFile === null) {
                    throw new RuntimeException(__('admin-main-settings.messages.upload_failed', ['field' => $field]));
                }

                $oldFile = config("app.{$field}");
                config()->set("app.{$field}", $newFile);
                $uploader->removeUploadedFile($oldFile);

                return null;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    protected function processFixedFileReplace(string $field, string $absoluteTargetPath): ?string
    {
        $file = $this->$field;
        $clearFlag = request()->input($field . '_clear');

        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $dir = dirname($absoluteTargetPath);
                $filesystem = fs();
                if (!is_dir($dir)) {
                    $filesystem->mkdir($dir, 0o755);
                }

                if (file_exists($absoluteTargetPath)) {
                    $filesystem->remove($absoluteTargetPath);
                }

                $file->move($dir, basename($absoluteTargetPath));

                return null;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        if ($clearFlag === '1' && file_exists($absoluteTargetPath)) {
            try {
                fs()->remove($absoluteTargetPath);
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    protected function addUploadDirectoryError(): void
    {
        $this->inputError('logo', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('logo_light', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('bg_image', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('bg_image_light', __('admin-main-settings.messages.upload_directory_error'));
    }

    protected function validateProfileImages(): bool
    {
        $this->default_avatar = request()->files->get('default_avatar');
        $this->default_banner = request()->files->get('default_banner');

        $rules = [
            'default_avatar' => $this->default_avatar
                ? 'image|mimes:jpeg,png,jpg,gif,svg,webp|max-file-size:10240'
                : 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max-file-size:10240',
            'default_banner' => $this->default_banner
                ? 'image|mimes:jpeg,png,jpg,gif,webp|max-file-size:10240'
                : 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max-file-size:10240',
        ];

        return $this->validate($rules);
    }

    protected function processProfileImageUpload(
        string $field,
        FileUploader $uploader,
        string $uploadsDir,
        string $configKey,
        string $defaultFile,
    ): ?string {
        $file = $this->$field;
        if ($file instanceof UploadedFile && $file->isValid()) {
            try {
                $newFile = $uploader->uploadImage($file, 10);

                if ($newFile === null) {
                    throw new RuntimeException(__('admin-main-settings.messages.upload_failed', ['field' => $field]));
                }

                $oldFile = config($configKey);
                config()->set($configKey, $newFile);
                $uploader->removeUploadedFile($oldFile);

                return null;
            } catch (Throwable $e) {
                return $e->getMessage();
            }
        }

        return null;
    }

    protected function addProfileUploadDirectoryError(): void
    {
        $this->inputError('default_avatar', __('admin-main-settings.messages.upload_directory_error'));
        $this->inputError('default_banner', __('admin-main-settings.messages.upload_directory_error'));
    }
}
