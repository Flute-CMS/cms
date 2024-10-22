<?php

namespace Flute\Core\Http\Controllers\Profile;

use Flute\Core\Database\Entities\User;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ImagesController extends AbstractController
{
    /**
     * Updates the user's profile avatar
     * 
     * @param FluteRequest $request
     * 
     * @return mixed
     */
    public function updateAvatar(FluteRequest $request)
    {
        return $this->updateImage($request, 'avatar');
    }

    /**
     * Updates the user's profile banner
     * 
     * @param FluteRequest $request
     * 
     * @return mixed
     */
    public function updateBanner(FluteRequest $request)
    {
        return $this->updateImage($request, 'banner');
    }

    /**
     * Remove the user's profile avatar
     * 
     * @param FluteRequest $request
     * 
     * @return Response
     */
    public function removeAvatar(FluteRequest $request): Response
    {
        return $this->removeImage($request, 'avatar');
    }

    /**
     * Remove the user's profile banner
     * 
     * @param FluteRequest $request
     * 
     * @return Response
     */
    public function removeBanner(FluteRequest $request): Response
    {
        return $this->removeImage($request, 'banner');
    }

    /**
     * Removes an image from the user's profile
     * 
     * @param FluteRequest $request
     * @param string $type
     * 
     * @return Response
     */
    private function removeImage(FluteRequest $request, string $type): Response
    {
        $default = config("profile.default_$type");

        $user = $request->user();
        fs()->remove($user->$type);

        $user->$type = $default;
        transaction($user)->run();

        user()->log('events.profile_' . $type . '_deleted');

        return $this->success();
    }

    /**
     * Update user's profile image
     * 
     * @param FluteRequest $request
     * @param string $imageType 'avatar' or 'banner'
     * 
     * @return mixed
     */
    protected function updateImage(FluteRequest $request, string $imageType)
    {
        try {
            $this->throttle("profile_change_$imageType");
        } catch (\Exception $e) {
            return $this->error(__('auth.too_many_requests'));
        }

        /** @var UploadedFile $file */
        $file = $request->files->get($imageType);

        if ($file === null) {
            return $this->error(__('validator.image'));
        }

        $maxSize = app('profile.max_' . $imageType . '_size') * 1000000;

        if ($file->getSize() > $maxSize) {
            return $this->error(__('validator.max_post_size', ['%d' => $maxSize]));
        }

        $allowedMimeTypes = app('profile.' . $imageType . '_types');
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $allowedMimeTypes)) {
            return $this->error(__('validator.mime_type'));
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            return $this->error(__('validator.image'));
        }

        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            return $this->error(__('validator.image'));
        }

        // Generate a secure file name
        $fileName = hash('sha256', user()->id . time()) . '.' . $extension;

        $destination = BASE_PATH . '/public/assets/uploads';

        if (!fs()->exists($destination)) {
            fs()->mkdir($destination, 0755);
        }

        $newFileDestination = 'assets/uploads/' . $fileName;

        $file->move($destination, $fileName);

        if (in_array($mimeType, ['image/png', 'image/jpeg']) && config('profile.convert_to_webp')) {
            $originalFilePath = $destination . '/' . $fileName;
            $webpFileName = hash('sha256', user()->id . time()) . '.webp';
            $webpFilePath = $destination . '/' . $webpFileName;
            try {
                \WebPConvert\WebPConvert::convert($originalFilePath, $webpFilePath);
                fs()->remove($originalFilePath); // Remove original file after conversion
                $newFileDestination = 'assets/uploads/' . $webpFileName;
            } catch (\Exception $e) {
                logs()->error($e);
                fs()->remove($originalFilePath);
                return $this->error(__('validator.image_conversion_failed'));
            }
        }

        $user = user()->getCurrentUser();

        $oldFilePath = BASE_PATH . '/public/' . $user->{$imageType};
        $uploadsDir = realpath(BASE_PATH . '/public/assets/uploads');
        $oldFileRealPath = realpath($oldFilePath);
        if ($oldFileRealPath && strpos($oldFileRealPath, $uploadsDir) === 0 && fs()->exists($oldFileRealPath) && $user->{$imageType} !== config('profile.default_' . $imageType)) {
            fs()->remove($oldFileRealPath);
        }

        $user->{$imageType} = $newFileDestination;

        transaction($user)->run();

        user()->log('events.profile_' . $imageType . '_updated');

        return $this->success((string) url($newFileDestination));
    }
}