<?php

namespace Flute\Admin\Http\Controllers;

use Exception;
use Flute\Core\Support\BaseController;
use Flute\Core\Support\FileUploader;
use Symfony\Component\HttpFoundation\Response;

class ImageUploadController extends BaseController
{
    public function upload(): Response
    {
        $file = request()->files->get('image');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid image file provided.',
            ], 400);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid image type.',
            ], 400);
        }

        try {
            $uploader = app(FileUploader::class);
            $path = $uploader->uploadImage($file, 5);

            return response()->json([
                'success' => true,
                'url' => (string) url($path),
            ]);
        } catch (Throwable $e) {
            logs()->error('Image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('def.upload_error'),
            ], 400);
        }
    }
}
