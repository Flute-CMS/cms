<?php

namespace Flute\Core\Support;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;
use WebPConvert\WebPConvert;
use ZipArchive;

class FileUploader
{
    private $targetDirectory;

    private $filesystem;

    private $logger;

    private const DANGEROUS_EXTENSIONS = [
        'php',
        'phtml',
        'php3',
        'php4',
        'php5',
        'phps',
        'phar',
        'exe',
        'sh',
        'bat',
        'cmd',
        'com',
        'scr',
        'vbs',
        'jsp',
        'asp',
        'aspx',
        'cgi',
        'pl',
        'py',
        'shtml',
        'svg',
        'htaccess',
        'htpasswd',
    ];

    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger,
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

        $this->validateOriginalName($file->getClientOriginalName());
        $this->validateFileSize($file, $maxSize);

        $mimeType = $file->getMimeType();

        if (
            $extension === null
            || !in_array($mimeType, $allowedMimeTypes, true)
            || !in_array($extension, $allowedExtensions, true)
        ) {
            throw new Exception('Invalid image file type.');
        }

        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new Exception('Uploaded file is not a valid image.');
        }

        // Re-encode the image to strip any injected payloads (polyglot files)
        $this->reencodeImage($file->getPathname(), $mimeType);

        $fileName = $safeFilename . '.' . $extension;
        $file->move($this->getTargetDirectory(), $fileName);

        $filePath = $this->getTargetDirectory() . '/' . $fileName;

        if (in_array($mimeType, ['image/jpeg', 'image/png'], true) && config('app.convert_to_webp')) {
            $webpFileName = $safeFilename . '.webp';
            $webpFilePath = $this->getTargetDirectory() . '/' . $webpFileName;

            try {
                WebPConvert::convert($filePath, $webpFilePath, []);

                $this->filesystem->remove($filePath);

                return 'assets/uploads/' . $webpFileName;
            } catch (Throwable $e) {
                $this->logger->error('WebP conversion failed: ' . $e->getMessage());

                // Clean up partial webp file if it was created
                if (file_exists($webpFilePath)) {
                    $this->filesystem->remove($webpFilePath);
                }

                // Keep the original file on conversion failure instead of losing both
                return 'assets/uploads/' . $fileName;
            }
        }

        return 'assets/uploads/' . $fileName;
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

        $this->validateOriginalName($file->getClientOriginalName());

        if (
            $extension === null
            || !in_array($file->getMimeType(), $allowedMimeTypes, true)
            || !in_array($extension, $allowedExtensions, true)
        ) {
            throw new Exception('Invalid ZIP file type.');
        }

        $this->validateFileSize($file, $maxSize);

        $fileName = $safeFilename . '.' . $extension;
        $file->move($this->getTargetDirectory(), $fileName);

        $filePath = $this->getTargetDirectory() . '/' . $fileName;

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            $this->filesystem->remove($filePath);

            throw new Exception('Invalid ZIP file.');
        }

        // Validate ZIP entries against Zip Slip and dangerous files
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_contains($entryName, "\0")) {
                $zip->close();
                $this->filesystem->remove($filePath);

                throw new Exception('ZIP contains suspicious path.');
            }
        }

        $zip->close();

        return 'assets/uploads/' . $fileName;
    }

    /**
     * Safely extracts a ZIP archive with Zip Slip protection.
     *
     * @param string $zipPath Path to the ZIP file
     * @param string $destination Directory to extract into
     * @return bool True on success
     */
    public function safeExtractZip(string $zipPath, string $destination): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new Exception('Cannot open ZIP file.');
        }

        $realDestination = realpath($destination);
        if ($realDestination === false) {
            $zip->close();

            throw new Exception('Extraction destination does not exist.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', (string) $entryName);

            if (str_starts_with($normalized, '/') || str_contains($normalized, "\0")) {
                $zip->close();

                throw new Exception('ZIP Slip detected.');
            }

            if (!$this->zipArchiveEntryPathStaysInsideRoot($normalized)) {
                $zip->close();

                throw new Exception('ZIP Slip detected.');
            }
        }

        $zip->extractTo($destination);
        $zip->close();

        return true;
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
     * Reject paths that escape the extraction root (works before subdirs exist; handles mixed slashes).
     */
    private function zipArchiveEntryPathStaysInsideRoot(string $normalizedEntryPath): bool
    {
        $parts = array_values(array_filter(
            explode('/', $normalizedEntryPath),
            static fn(string $p): bool => $p !== '',
        ));

        $depth = 0;
        foreach ($parts as $part) {
            if ($part === '.') {
                continue;
            }
            if ($part === '..') {
                $depth--;
                if ($depth < 0) {
                    return false;
                }

                continue;
            }
            $depth++;
        }

        return true;
    }

    /**
     * Re-encodes an image to strip any injected payloads (EXIF, comments, polyglot data).
     */
    private function reencodeImage(string $path, string $mimeType): void
    {
        $image = @imagecreatefromstring(file_get_contents($path));

        if ($image === false) {
            return;
        }

        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $path, 90);

                break;
            case 'image/png':
                imagesavealpha($image, true);
                imagepng($image, $path, 9);

                break;
            case 'image/gif':
                imagegif($image, $path);

                break;
            case 'image/webp':
                imagewebp($image, $path, 90);

                break;
        }

        imagedestroy($image);
    }

    /**
     * Validates the original filename for suspicious extensions.
     */
    private function validateOriginalName(string $originalName): void
    {
        // Strip null bytes
        $originalName = str_replace("\0", '', $originalName);

        // Check for any dangerous extension anywhere in the filename
        $pattern = '/\.(' . implode('|', self::DANGEROUS_EXTENSIONS) . ')(\.|$)/i';
        if (preg_match($pattern, $originalName)) {
            throw new Exception('Suspicious file extension detected.');
        }
    }

    /**
     * Validates file size with safe false-check.
     */
    private function validateFileSize(UploadedFile $file, int $maxSize): void
    {
        $fileSize = $file->getSize();
        $maxSizeBytes = $this->convertMegabytesToBytes($maxSize);

        if ($fileSize === false || $fileSize === 0 || $fileSize > $maxSizeBytes) {
            throw new Exception('File size exceeds the maximum limit of ' . $maxSize . ' MB.');
        }
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
