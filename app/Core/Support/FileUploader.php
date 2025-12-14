<?php

namespace Flute\Core\Support;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use WebPConvert\WebPConvert;
use ZipArchive;

class FileUploader
{
    private $targetDirectory;

    private $filesystem;

    private $logger;

    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->targetDirectory = 'public/assets/uploads';
    }

    /**
     * Uploads an image with security checks and optional conversion to WebP.
     *
     * @param int $maxSize Maximum file size in megabytes
     * @return string|null Path to the uploaded file or null in case of an error
     */
    public function uploadImage(UploadedFile $file, int $maxSize): ?string
    {
        $safeFilename = bin2hex(random_bytes(16));
        $extension = $file->guessExtension();

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $mimeType = $file->getMimeType();

        if (in_array($mimeType, $allowedMimeTypes) && in_array($extension, $allowedExtensions)) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo === false) {
                throw new Exception('Uploaded file is not a valid image.');
            }

            $maxSizeBytes = $this->convertMegabytesToBytes($maxSize);

            if ($file->getSize() > $maxSizeBytes) {
                throw new Exception('File size exceeds the maximum limit of ' . $maxSize . ' MB.');
            }

            $fileName = $safeFilename . '.' . $extension;
            $file->move($this->getTargetDirectory(), $fileName);

            $filePath = $this->getTargetDirectory() . '/' . $fileName;
            // if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
            //     $image = imagecreatefromstring(file_get_contents($filePath));
            //     if ($image !== false) {
            //         if ($extension === 'png') {
            //             imagepng($image, $filePath);
            //         } else {
            //             imagejpeg($image, $filePath, 90);
            //         }
            //         imagedestroy($image);
            //     }
            // }

            if (in_array($mimeType, ['image/jpeg', 'image/png']) && config('app.convert_to_webp')) {
                $webpFileName = $safeFilename . '.webp';
                $webpFilePath = $this->getTargetDirectory() . '/' . $webpFileName;

                try {
                    WebPConvert::convert($filePath, $webpFilePath, []);

                    $this->filesystem->remove($filePath);

                    return 'assets/uploads/' . $webpFileName;
                } catch (Exception $e) {
                    $this->logger->error('WebP conversion failed: ' . $e->getMessage());
                    $this->filesystem->remove($filePath);

                    throw new Exception('Image conversion to WebP failed.');
                }
            }

            return 'assets/uploads/' . $fileName;
        }

        throw new Exception('Invalid image file type.');

    }

    /**
     * Uploads a ZIP file with security checks.
     *
     * @param int $maxSize Maximum file size in megabytes
     * @return string|null Path to the uploaded file or null in case of an error
     */
    public function uploadZip(UploadedFile $file, int $maxSize): ?string
    {
        $safeFilename = bin2hex(random_bytes(16));
        $extension = $file->guessExtension();

        $allowedMimeTypes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'];
        $allowedExtensions = ['zip'];

        if (in_array($file->getMimeType(), $allowedMimeTypes) && in_array($extension, $allowedExtensions)) {
            $maxSizeBytes = $this->convertMegabytesToBytes($maxSize);

            if ($file->getSize() > $maxSizeBytes) {
                throw new Exception('File size exceeds the maximum limit of ' . $maxSize . ' MB.');
            }

            $fileName = $safeFilename . '.' . $extension;
            $file->move($this->getTargetDirectory(), $fileName);

            $zip = new ZipArchive();
            if ($zip->open($this->getTargetDirectory() . '/' . $fileName) === true) {
                $zip->close();

                return 'assets/uploads/' . $fileName;
            }
            $this->filesystem->remove($this->getTargetDirectory() . '/' . $fileName);

            throw new Exception('Invalid ZIP file.');

        }

        throw new Exception('Invalid ZIP file type.');

    }

    /**
     * Returns the target directory for file uploads.
     *
     * @return string
     */
    public function getTargetDirectory()
    {
        return BASE_PATH . $this->targetDirectory;
    }

    /**
     * Converts megabytes to bytes.
     *
     * @param float $megabytes Size in megabytes
     * @return int Size in bytes
     */
    private function convertMegabytesToBytes(float $megabytes): int
    {
        return (int) round($megabytes * 1024 * 1024);
    }
}
