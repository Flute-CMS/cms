<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PagesController extends AbstractController
{
    public function saveEdit(FluteRequest $request)
    {
        if (!page()->hasAccessToEdit())
            return $this->error('access denied');

        $page = (string) $request->input('page');
        $data = json_decode($request->input('data', '{}'), true);

        if (empty($data) || empty($page) || (!empty($data) && !isset($data['blocks'])))
            return $this->error('invalid data');

        page()->savePageBlocks($data['blocks'], $page);

        user()->log('events.custom_pages_edited', $page);

        return $this->success();
    }

    public function saveImage(FluteRequest $request)
    {
        $file = $request->files->get('image');

        try {
            $upload = $this->uploadImage($file);

            return $this->json([
                "success" => 1,
                "file" => [
                    'url' => url($upload)->get()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => 0,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    protected function uploadImage(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new \Exception(__('validator.invalid_file'));
        }

        $maxSize = 5000000;
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            throw new \Exception(__('validator.invalid_file_extension'));
        }

        if ($file->getSize() > $maxSize) {
            throw new \Exception(__('validator.max_post_size', ['%d' => $maxSize]));
        }

        try {
            $mimeType = $file->getMimeType();
        } catch (\Exception $e) {
            logs()->error($e);
            throw new \Exception(__('def.unknown_error'));
        }

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \Exception(__('validator.mime_type'));
        }

        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \Exception(__('validator.invalid_image'));
        }

        $fileName = hash('sha256', uniqid('', true)) . '.' . $extension;
        $destination = public_path('assets/uploads');

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $fileName);
        chmod($destination . '/' . $fileName, 0644);

        $newFileDestination = 'assets/uploads/' . $fileName;

        // Convert to WebP if necessary
        if (in_array($mimeType, ['image/png', 'image/jpeg']) && config('profile.convert_to_webp')) {
            $webPFileName = hash('sha256', uniqid('', true)) . '.webp';
            try {
                \WebPConvert\WebPConvert::convert($destination . '/' . $fileName, $destination . '/' . $webPFileName);
                unlink($destination . '/' . $fileName);
                $newFileDestination = 'assets/uploads/' . $webPFileName;
            } catch (\Exception $e) {
                logs()->error($e);
                throw new \Exception(__('validator.image_conversion_failed'));
            }
        }

        return $newFileDestination;
    }

}