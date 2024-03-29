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
    public function removeAvatar(FluteRequest $request) : Response
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
    public function removeBanner(FluteRequest $request) : Response
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
    private function removeImage(FluteRequest $request, string $type) : Response
    {
        $default = config("profile.default_$type");

        $user = $request->user();
        fs()->remove($user->$type);

        $user->$type = $default;
        transaction($user)->run();

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
        /** @var UploadedFile $file */
        $file = $request->files->get($imageType);

        if ($file === null) {
            return $this->error(__('validator.upload_control_valid'));
        }

        $maxSize = app('profile.max_' . $imageType . '_size') * 1000000;

        if ($file->getSize() > $maxSize) {
            return $this->error(__('validator.max_post_size', ['%d' => $maxSize]));
        }

        try {
            $mimeType = $file->getMimeType();
        } catch(\Exception $e) {
            logs()->error($file->getErrorMessage());

            return $this->error(__('def.unknown_error'));
        }

        if (!in_array($mimeType, app('profile.' . $imageType . '_types'))) {
            return $this->error(__('validator.image'));
        }

        $fileName = hash('sha256', user()->id . $file->getBasename()) . '.' . $file->getClientOriginalExtension();

        $destination = BASE_PATH . '/public/assets/uploads';

        if (!fs()->exists($destination)) {
            fs()->mkdir($destination, 0700);
        }

        $newFileDestination = 'assets/uploads/' . $fileName;

        // If banner, check for possible conversion to webp
        if (in_array($mimeType, ['image/png', 'image/jpeg']) && config('profile.convert_to_webp')) {
            $file->move($destination, $fileName);

            $newFileDestination = 'assets/uploads/' . hash('sha256', user()->id . $file->getBasename()) . '.webp';

            try {
                \WebPConvert\WebPConvert::convert('assets/uploads/' . $fileName, $newFileDestination);
            } catch (\Exception $e) {
                logs()->error($e->getTraceAsString());

                fs()->remove('assets/uploads/' . $fileName);
                return $this->error(__('validator.image_conversion_failed'));
            }

            fs()->remove('assets/uploads/' . $fileName);
        } else {
            $file->move($destination, $fileName);
        }

        $user = rep(User::class)->findByPK(user()->id);

        if ($user->{$imageType} !== config('profile.default_' . $imageType)) {
            fs()->remove($user->{$imageType});
        }

        $user->{$imageType} = $newFileDestination;

        transaction($user)->run();

        user()->log('profile.' . $imageType . '_updated');

        return $this->success((string) url($newFileDestination));
    }
}