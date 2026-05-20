<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;

trait RulesFile
{
    public static function boolean(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_bool($value)) {
                continue;
            }

            if (is_int($value) && ( $value === 1 || $value === 0 )) {
                continue;
            }

            if (is_string($value)) {
                $lowerValue = strtolower($value);
                if (in_array($lowerValue, ['1', '0', 'true', 'false'], true)) {
                    continue;
                }
            }

            $validator->addError($attribute, $rule);
        }
    }

    public static function image(FluteValidator $validator, $data, $pattern, $rule)
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_null($value) || empty($value)) {
                continue;
            }

            if (is_object($value) && method_exists($value, 'getError')) {
                if ($value->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                if ($value->getError() !== UPLOAD_ERR_OK) {
                    $validator->addError($attribute, $rule);

                    continue;
                }
            }

            $isValidImage = false;

            if (
                is_object($value)
                && method_exists($value, 'getMimeType')
                && method_exists($value, 'getClientOriginalExtension')
            ) {
                $mimeType = strtolower($value->getMimeType());
                $extension = strtolower($value->getClientOriginalExtension());

                if (in_array($mimeType, $allowedMimeTypes) && in_array($extension, $allowedExtensions)) {
                    $isValidImage = true;
                }
            } elseif (is_string($value) && is_file($value)) {
                $imageInfo = @getimagesize($value);

                if ($imageInfo !== false) {
                    $mimeType = $imageInfo['mime'] ?? '';

                    if (in_array($mimeType, $allowedMimeTypes)) {
                        $isValidImage = true;
                    }

                    $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                    if (!in_array($extension, $allowedExtensions)) {
                        $isValidImage = false;
                    }
                }

                if (!$isValidImage && function_exists('exif_imagetype')) {
                    $imageType = @exif_imagetype($value);
                    if (in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
                        $isValidImage = true;
                    }
                }
            } elseif (is_string($value)) {
                $extension = strtolower(pathinfo(parse_url($value, PHP_URL_PATH) ?: $value, PATHINFO_EXTENSION));
                if (in_array($extension, $allowedExtensions)) {
                    $isValidImage = true;
                }
            }

            if (!$isValidImage) {
                $validator->addError($attribute, $rule);
            }
        }
    }

    public static function mimes(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $allowedExtensions = array_map('strtolower', $parameters);

        $mimeMap = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'bmp' => ['image/bmp', 'image/x-ms-bmp'],
            'svg' => ['image/svg+xml'],
            'webp' => ['image/webp'],
            'tiff' => ['image/tiff'],
            'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'application/csv'],
            'rtf' => ['application/rtf'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
            'tar' => ['application/x-tar'],
            'gz' => ['application/gzip'],
            '7z' => ['application/x-7z-compressed'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'ogg' => ['audio/ogg'],
            'flac' => ['audio/flac'],
            'm4a' => ['audio/mp4'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'],
            'flv' => ['video/x-flv'],
            'mkv' => ['video/x-matroska'],
            'webm' => ['video/webm'],
            'html' => ['text/html'],
            'css' => ['text/css'],
            'js' => ['application/javascript', 'text/javascript'],
            'json' => ['application/json'],
            'xml' => ['application/xml', 'text/xml'],
            'php' => ['application/x-php', 'text/x-php'],
        ];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_null($value) || empty($value)) {
                continue;
            }

            $isValid = false;
            $fileMime = null;
            $fileExtension = null;

            if (is_object($value) && method_exists($value, 'getMimeType')) {
                $fileMime = strtolower($value->getMimeType());

                if (method_exists($value, 'getClientOriginalName')) {
                    $fileExtension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
                }
            } elseif (is_string($value)) {
                $fileExtension = strtolower(pathinfo($value, PATHINFO_EXTENSION));

                if (file_exists($value)) {
                    $fileMime = strtolower(mime_content_type($value));
                }
            }

            if ($fileExtension && in_array($fileExtension, $allowedExtensions)) {
                $isValid = true;
            } elseif ($fileMime) {
                foreach ($allowedExtensions as $extension) {
                    if (isset($mimeMap[$extension])) {
                        if (in_array($fileMime, $mimeMap[$extension])) {
                            $isValid = true;

                            break;
                        }
                    }
                }
            }

            if ($isValid) {
                continue;
            }

            $validator->addError($attribute, $rule, [':values' => implode(', ', $allowedExtensions)]);
        }
    }

    public static function maxFileSize(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $maxSizeKB = (int) $parameters[0];
        $maxSizeBytes = $maxSizeKB * 1024;

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_null($value) || empty($value)) {
                continue;
            }

            $fileSize = null;

            if (is_object($value) && method_exists($value, 'getSize')) {
                $fileSize = $value->getSize();
            } elseif (is_string($value) && file_exists($value)) {
                $fileSize = filesize($value);
            }

            if ($fileSize !== null && $fileSize > $maxSizeBytes) {
                $validator->addError($attribute, $rule, [':max' => $maxSizeKB . ' KB']);
            }
        }
    }
}
