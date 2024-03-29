<?php

namespace Flute\Core\Http\Controllers;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;

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

    protected function uploadImage($file)
    {
        // if (!$file instanceof UploadedFile || !$file->isValid()) {
        //     return $this->error('Invalid image file');
        // }

        $maxSize = 5000000;
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];;

        if ($file->getSize() > $maxSize) {
            throw new \Exception(__('validator.max_post_size', ['%d' => $maxSize]));
        }

        try {
            $mimeType = $file->getMimeType();
        } catch (\Exception $e) {
            logs()->error($file->getErrorMessage());

            throw new \Exception(__('def.unknown_error'));
        }

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \Exception(__('validator.image'));
        }

        $fileName = hash('sha256', uniqid()) . '.' . $file->getClientOriginalExtension();
        $destination = BASE_PATH . '/public/assets/uploads';

        if (!file_exists($destination)) {
            mkdir($destination, 0700, true);
        }

        $file->move($destination, $fileName);

        // Конвертация в WebP при необходимости
        $newFileDestination = 'assets/uploads/' . $fileName;
        if (in_array($mimeType, ['image/png', 'image/jpeg']) && config('profile.convert_to_webp')) {
            $webPFileName = hash('sha256', uniqid()) . '.webp';
            try {
                \WebPConvert\WebPConvert::convert($destination . '/' . $fileName, $destination . '/' . $webPFileName);
                fs()->remove($destination . '/' . $fileName); // Удаление исходного файла
                $newFileDestination = 'assets/uploads/' . $webPFileName;
            } catch (\Exception $e) {
                // Обработка ошибок конвертации
            }
        }

        return $newFileDestination;
    }
}