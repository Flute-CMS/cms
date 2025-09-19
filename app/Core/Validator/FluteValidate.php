<?php

namespace Flute\Core\Validator;

use Countable;
use DateTime;
use Exception;
use Flute\Core\Validator\Support\ValidatorStr;
use InvalidArgumentException;
use MadeSimple\Arrays\ArrDots;

class FluteValidate
{
    /**
     */
    public static function addRuleSet(FluteValidator $validator)
    {
        $validator
            ->addRule('present', [static::class, 'present'])
            ->addRule('required', [static::class, 'required'])
            ->addRule('required-if', [static::class, 'requiredIf'])
            ->addRule('required-with', [static::class, 'requiredWith'])
            ->addRule('required-with-all', [static::class, 'requiredWithAll'])
            ->addRule('required-with-any', [static::class, 'requiredWithAny'])
            ->addRule('required-without', [static::class, 'requiredWithout'])

            ->addRule('equals', [static::class, 'equals'])
            ->addRule('not-equals', [static::class, 'notEquals'])
            ->addRule('identical', [static::class, 'identical'])
            ->addRule('not-identical', [static::class, 'notIdentical'])

            ->addRule('in', [static::class, 'in'])
            ->addRule('not-in', [static::class, 'notIn'])
            ->addRule('contains', [static::class, 'contains'])
            ->addRule('contains-only', [static::class, 'containsOnly'])
            ->addRule('min-arr-count', [static::class, 'minArrCount'])
            ->addRule('max-arr-count', [static::class, 'maxArrCount'])

            ->addRule('min', [static::class, 'min'])
            ->addRule('max', [static::class, 'max'])
            ->addRule('greater-than', [static::class, 'greaterThan'])
            ->addRule('less-than', [static::class, 'lessThan'])

            ->addRule('alpha', [static::class, 'alpha'])
            ->addRule('alpha-numeric', [static::class, 'alphaNumeric'])
            ->addRule('min-str-len', [static::class, 'minStrLen'])
            ->addRule('max-str-len', [static::class, 'maxStrLen'])
            ->addRule('str-len', [static::class, 'strLen'])
            ->addRule('human-name', [static::class, 'humanName'])

            ->addRule('is', [static::class, 'is'])

            ->addRule('email', [static::class, 'email'])
            ->addRule('date', [static::class, 'date'])
            ->addRule('datetime', [static::class, 'datetime'])
            ->addRule('url', [static::class, 'url'])
            ->addRule('uuid', [static::class, 'uuid'])

            ->addRule('card-number', [static::class, 'cardNumber'])

            ->addRule('regex', [static::class, 'regex'])
            ->addRule('not-regex', [static::class, 'notRegex'])

            ->addRule('confirmed', [static::class, 'confirmed'])

            ->addRule('unique', [static::class, 'unique'])

            ->addRule('nullable', [static::class, 'nullable'])
            ->addRule('image', [static::class, 'image'])
            ->addRule('mimes', [static::class, 'mimes'])
            ->addRule('max-file-size', [static::class, 'maxFileSize'])

            ->addRule('boolean', [static::class, 'boolean'])
            ->addRule('integer', [static::class, 'integer'])
            ->addRule('string', [static::class, 'string'])
            ->addRule('array', [static::class, 'arrayRule'])
            ->addRule('timezone', [static::class, 'timezone'])
            ->addRule('exists', [static::class, 'exists'])

            ->addRule('numeric', [static::class, 'numeric'])
            ->addRule('after', [static::class, 'after']);
    }

    /**
     * numeric
     *
     * Проверяет, является ли значение числом.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function numeric(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_numeric($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * after:<date|field>
     *
     * Проверяет, что дата находится после заданной даты или после другой даты из данных.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array $parameters
     */
    public static function after(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $comparison = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_null($value) || empty($value)) {
                continue;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}(?:\s\d{2}:\d{2}(?::\d{2})?)?$/', $comparison)) {
                $compareValue = FluteValidator::getValue($data, $comparison);

                if (is_null($compareValue) || empty($compareValue)) {
                    continue;
                }

                $date1 = DateTime::createFromFormat('Y-m-d H:i:s', $value) ?: DateTime::createFromFormat('Y-m-d', $value);
                $date2 = DateTime::createFromFormat('Y-m-d H:i:s', $compareValue) ?: DateTime::createFromFormat('Y-m-d', $compareValue);

                if (!$date1 || !$date2 || $date1 <= $date2) {
                    $validator->addError($attribute, $rule, [
                        ':date' => $comparison,
                    ]);
                }
            } else {
                try {
                    $compareDate = \Carbon\Carbon::parse($comparison);
                    $currentDate = \Carbon\Carbon::parse($value);

                    if ($currentDate->lte($compareDate)) {
                        $validator->addError($attribute, $rule, [
                            ':date' => $comparison,
                        ]);
                    }
                } catch (Exception $e) {
                    if (is_debug()) {
                        throw $e;
                    }

                    $validator->addError($attribute, $rule, [
                        ':date' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * boolean
     *
     * Проверяет, является ли значение булевым.
     * Допустимые значения: true, false, 1, 0, "1", "0", "true", "false"
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function boolean(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_bool($value)) {
                continue;
            }

            if (is_int($value) && ($value === 1 || $value === 0)) {
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

    /**
     * integer
     *
     * Проверяет, является ли значение целым числом.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function integer(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_int($value)) {
                continue;
            }

            if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * string
     *
     * Проверяет, является ли значение строкой.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function string(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_string($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * array
     *
     * Проверяет, является ли значение массивом.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function arrayRule(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_array($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * timezone
     *
     * Проверяет, является ли значение допустимой временной зоной.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function timezone(FluteValidator $validator, $data, $pattern, $rule)
    {
        $validTimezones = timezone_identifiers_list();

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (!is_string($value)) {
                $validator->addError($attribute, $rule);

                continue;
            }

            if (in_array($value, $validTimezones, true)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * nullable
     *
     * Позволяет полю быть null или пустым. Если поле не пустое, продолжает проверку остальных правил.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function nullable(FluteValidator $validator, $data, $pattern, $rule)
    {
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            return;
        }
    }

    /**
     * image
     *
     * Проверяет, является ли файл изображением по MIME типу.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
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

            if (is_object($value) && method_exists($value, 'getMimeType') && method_exists($value, 'getClientOriginalExtension')) {
                $mimeType = strtolower($value->getMimeType());
                $extension = strtolower($value->getClientOriginalExtension());

                if (in_array($mimeType, $allowedMimeTypes) && in_array($extension, $allowedExtensions)) {
                    $isValidImage = true;
                }
            } elseif (is_string($value)) {
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
            }

            if (!$isValidImage) {
                $validator->addError($attribute, $rule);
            }
        }
    }

    /**
     * Validate that an attribute has a file of a given MIME type.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array $parameters
     */
    public static function mimes(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $allowedExtensions = array_map('strtolower', $parameters);

        $mimeMap = [
            // Images
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'bmp' => ['image/bmp', 'image/x-ms-bmp'],
            'svg' => ['image/svg+xml'],
            'webp' => ['image/webp'],
            'tiff' => ['image/tiff'],
            'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],

            // Documents
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

            // Archives
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
            'tar' => ['application/x-tar'],
            'gz' => ['application/gzip'],
            '7z' => ['application/x-7z-compressed'],

            // Audio
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'ogg' => ['audio/ogg'],
            'flac' => ['audio/flac'],
            'm4a' => ['audio/mp4'],

            // Video
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'wmv' => ['video/x-ms-wmv'],
            'flv' => ['video/x-flv'],
            'mkv' => ['video/x-matroska'],
            'webm' => ['video/webm'],

            // Code
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

    /**
     * present
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function present(FluteValidator $validator, $data, $pattern, $rule)
    {
        if (ArrDots::has($data, $pattern, $validator::WILD)) {
            return;
        }

        $validator->addError($pattern, $rule);
    }

    /**
     * required
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function required(FluteValidator $validator, $data, $pattern, $rule)
    {
        // Check pattern is present
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            $validator->addError($pattern, $rule);

            return;
        }

        // Check value is not null
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_object($value) && method_exists($value, 'getError')) {
                if ($value->getError() === UPLOAD_ERR_NO_FILE) {
                    $validator->addError($attribute, $rule);
                } elseif ($value->getError() !== UPLOAD_ERR_OK) {
                    $validator->addError($attribute, $rule);
                }

                continue;
            }

            if (static::isFilled($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * required-if:another-field(,value)+
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function requiredIf(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $values = array_slice($parameters, 1);
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        // If pattern is not present
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            foreach (FluteValidator::getValues($data, $field) as $fieldAttribute => $fieldValue) {
                if (null === $fieldValue || !in_array($fieldValue, $values)) {
                    continue;
                }

                $attribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $fieldAttribute, $pattern) : $pattern;
                $validator->addError($attribute, $rule, [':field' => $fieldAttribute, '%value' => implode(',', $values)]);
            }

            return;
        }

        // Check value is not null
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if (!static::isFilled($fieldValue) || !in_array($fieldValue, $values)) {
                continue;
            }

            if (static::isFilled($value)) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute, '%value' => implode(',', $values)]);
        }
    }

    /**
     * required-with:another-field
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function requiredWith(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        // Check that the pattern and field can be compared
        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
        }

        // If the required with field exists and the pattern field does not
        if (ArrDots::has($data, $field, $validator::WILD) && !ArrDots::has($data, $pattern, $validator::WILD)) {
            $validator->addError($pattern, $rule, [':field' => $field]);
        }

        // Check value is not null
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if (!static::isFilled($fieldValue)) {
                continue;
            }
            if (static::isFilled($value)) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    /**
     * required-with-all:another-field(,another-field)*
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function requiredWithAll(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        // Find the overlaps and if the fields are wild
        $overlaps = [];
        $longest = 0;
        foreach ($parameters as $k => $field) {
            $isWild = strpos($field, $validator::WILD) !== false;
            $overlaps[$k] = $isWild ? ValidatorStr::overlapLeft($field, $pattern) : null;
            if ($isWild && $overlaps[$k] === false) {
                throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
            }
            // Store the longest overlap
            $longest = $isWild && strlen($overlaps[$k]) > strlen($overlaps[$longest]) ? $k : $longest;
        }

        // If the pattern field does not exist
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            // Check that all "required with" fields are present and not null
            $required = false;
            foreach (FluteValidator::getValues($data, $parameters[$longest]) as $attribute => $value) {
                $required = true;
                foreach ($parameters as $k => $field) {
                    $fieldAttribute = $overlaps[$k] ? ValidatorStr::overlapLeftMerge($overlaps[$k], $attribute, $field) : $field;
                    $fieldValue = ArrDots::get($data, $fieldAttribute);
                    $required = $required && static::isFilled($fieldValue);
                    if (!$required) {
                        break;
                    }
                }
                if ($required) {
                    break;
                }
            }

            if ($required) {
                $validator->addError($pattern, $rule);
            }

            return;
        }



        // Check value is required and not null
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            // Check that all "required with" fields are present and not null
            $required = true;
            foreach ($parameters as $k => $field) {
                $fieldAttribute = $overlaps[$k] ? ValidatorStr::overlapLeftMerge($overlaps[$k], $attribute, $field) : $field;
                $fieldValue = ArrDots::get($data, $fieldAttribute);
                $required = $required && static::isFilled($fieldValue);
                if (!$required) {
                    break;
                }
            }

            // If required and value is null
            if ($required && $value === null) {
                $validator->addError($pattern, $rule);
            }
        }
    }

    /**
     * required-with-any:another-field(,another-field)*
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function requiredWithAny(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        // Find the overlaps and if the fields are wild
        $overlaps = [];
        $longest = 0;
        foreach ($parameters as $k => $field) {
            $isWild = strpos($field, $validator::WILD) !== false;
            $overlaps[$k] = $isWild ? ValidatorStr::overlapLeft($field, $pattern) : null;
            if ($isWild && $overlaps[$k] === false) {
                throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
            }
            // Store the longest overlap
            $longest = $isWild && strlen($overlaps[$k]) > strlen($overlaps[$longest]) ? $k : $longest;
        }

        // If the pattern field does not exist
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            // Check that any "required with" fields are present and not null
            $required = array_reduce($parameters, static function ($required, $field) use ($validator, $data) {
                if (!$required && ArrDots::has($data, $field, $validator::WILD)) {
                    foreach (FluteValidator::getValues($data, $field) as $value) {
                        $required = $required || static::isFilled($value);
                    }

                }

                return $required;
            }, false);

            if ($required) {
                $validator->addError($pattern, $rule);
            }

            return;
        }



        // Check value is required and not null
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            // Check that any "required with" fields are present and not null
            $required = false;
            foreach ($parameters as $k => $field) {
                $fieldAttribute = $overlaps[$k] ? ValidatorStr::overlapLeftMerge($overlaps[$k], $attribute, $field) : $field;
                $fieldValue = ArrDots::get($data, $fieldAttribute);
                $required = $required || static::isFilled($fieldValue);
                if ($required) {
                    break;
                }
            }

            // If required and value is null
            if ($required && $value === null) {
                $validator->addError($pattern, $rule);
            }
        }
    }

    /**
     * required-without:another-field
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function requiredWithout(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        // Check that the pattern and field can be compared
        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
        }

        // If the required with field exists and the pattern field does not
        if (!ArrDots::has($data, $field, $validator::WILD) && !ArrDots::has($data, $pattern, $validator::WILD)) {
            $validator->addError($pattern, $rule, [':field' => $field]);
        }

        // Check value is not null
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (static::isFilled($value)) {
                continue;
            }

            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);
            if (static::isFilled($fieldValue)) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    /**
     * equals:another-field
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function equals(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        // Check that the pattern and field can be compared
        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
        }

        // Check values are equal
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue == $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    /**
     * not-equals:another-field
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function notEquals(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($pattern, $field);

        // Check that the pattern and field can be compared
        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern to field');
        }

        // Check values are equal
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue != $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    /**
     * identical:another-field
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function identical(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($pattern, $field);

        // Check that the pattern and field can be compared
        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern to field');
        }

        // Check values are equal
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue === $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    /**
     * not-identical:another-field
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function notIdentical(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($pattern, $field);

        // Check that the pattern and field can be compared
        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern to field');
        }

        // Check values are equal
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue !== $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    /**
     * in:<value>(,<value>)*
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function in(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value) {
                continue;
            }
            if (in_array($value, $parameters)) {
                continue;
            }

            $validator->addError($attribute, $rule, ['%values' => implode(', ', $parameters)]);
        }
    }

    /**
     * not-in:<value>(,<value>)*
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function notIn(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value) {
                continue;
            }
            if (!in_array($value, $parameters)) {
                continue;
            }

            $validator->addError($attribute, $rule, ['%values' => implode(', ', $parameters)]);
        }
    }

    /**
     * contains:<value>(,<value>)*
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function contains(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (is_countable($value) && count($parameters) == count(array_intersect($value, $parameters))) {
                continue;
            }

            $validator->addError($attribute, $rule, [':values' => implode(', ', $parameters)]);
        }
    }

    /**
     * contains-only:<value>(,<value>)*
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function containsOnly(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (is_countable($value) && count($value) == count(array_intersect($value, $parameters))) {
                continue;
            }

            $validator->addError($attribute, $rule, [':values' => implode(', ', $parameters)]);
        }
    }

    /**
     * min-arr-count:<minimum_value>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function minArrCount(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $min = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value) {
                continue;
            }

            if (is_countable($value) && count($value) >= $min) {
                break;
            }

            $validator->addError($attribute, $rule, [':min' => $min]);
        }
    }

    /**
     * max-arr-count:<minimum_value>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function maxArrCount(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $max = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value) {
                continue;
            }

            if (is_countable($value) && count($value) <= $max) {
                break;
            }

            $validator->addError($attribute, $rule, [':max' => $max]);
        }
    }

    /**
     * min:<minimum-value>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function min(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $min = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || ($value != '0' && empty($value))) {
                continue;
            }

            if ($value >= $min) {
                break;
            }

            $validator->addError($attribute, $rule, [':min' => $min]);
        }
    }

    /**
     * max:<minimum_value>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function max(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $max = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || ($value != '0' && empty($value))) {
                continue;
            }

            if ($value <= $max) {
                break;
            }

            $validator->addError($attribute, $rule, [':max' => $max]);
        }
    }

    /**
     * maxFileSize:<max_size_in_kb>
     *
     * Проверяет, что размер файла не превышает заданное значение (в килобайтах).
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array $parameters
     */
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

    /**
     * greater-than:<another_field>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function greaterThan(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $lowerBound = FluteValidator::getValue($data, $parameters[0]);
        if (null === $lowerBound) {
            return;
        }
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if ($value > $lowerBound) {
                continue;
            }
            $validator->addError($attribute, $rule, [':value' => $value]);
        }
    }

    /**
     * less-than:<another_field>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function lessThan(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $upperBound = FluteValidator::getValue($data, $parameters[0]);
        if (null === $upperBound) {
            return;
        }
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if ($value < $upperBound) {
                continue;
            }

            $validator->addError($attribute, $rule, [':value' => $value]);
        }
    }

    /**
     * alpha
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function alpha(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (preg_match('/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $value) === 1) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * alpha-numeric
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function alphaNumeric(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $value) === 1) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * min-str-len:<minimum_value>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function minStrLen(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $min = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }

            if (strlen($value) >= $min) {
                break;
            }

            $validator->addError($attribute, $rule, [':min' => $min]);
        }
    }

    /**
     * max-str-len:<minimum_value>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function maxStrLen(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $max = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }

            if (strlen($value) <= $max) {
                break;
            }

            $validator->addError($attribute, $rule, [':max' => $max]);
        }
    }

    /**
     * str-len:<exact-length>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function strLen(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $length = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (strlen($value) === (int) $length) {
                continue;
            }

            $validator->addError($attribute, $rule, [':length' => $length]);
        }
    }

    /**
     * human-name
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function humanName(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (
                preg_match('/^([\p{L}\p{N} _\'.-])+$/u', $value) === 1
            ) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * is:<type>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function is(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $is_a_func = sprintf('is_%s', $parameters[0]);
        if (!function_exists($is_a_func)) {
            return;
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            // As "is:<type>" is validating value type only ignore null
            if (null === $value) {
                continue;
            }
            if (call_user_func($is_a_func, $value)) {
                continue;
            }

            $validator->addError($attribute, $rule, [':type' => $parameters[0]]);
        }
    }

    /**
     * email
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function email(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (false !== filter_var($value, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * date:(format)?
     *
     * @link http://php.net/manual/en/datetime.createfromformat.php
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function date(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $format = !empty($parameters[0]) ? $parameters[0] : 'Y-m-d';
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            $d = DateTime::createFromFormat($format, $value);
            if ($d && $d->format($format) == $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':format' => $format]);
        }
    }

    /**
     * datetime:<format>
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array $parameters
     */
    public static function datetime(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $format = !empty($parameters[0]) ? $parameters[0] : null;

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            if ($format) {
                $dateTime = DateTime::createFromFormat($format, $value);
                $errors = DateTime::getLastErrors();

                if (!$dateTime || $errors['error_count'] > 0 || $errors['warning_count'] > 0) {
                    $validator->addError($attribute, $rule, [':format' => $format]);
                }
            } else {
                try {
                    new DateTime($value);
                } catch (Exception $e) {
                    $validator->addError($attribute, $rule);
                }
            }
        }
    }

    /**
     * url
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function url(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (false !== filter_var($value, FILTER_VALIDATE_URL)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * uuid
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function uuid(FluteValidator $validator, $data, $pattern, $rule)
    {
        $uuidPattern = '/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/';

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (is_string($value) && 1 === preg_match($uuidPattern, $value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * card-number
     *
     * @see http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     */
    public static function cardNumber(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }

            // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
            $number = preg_replace('/\D/', '', $value);

            // Set the string length and parity
            $numberLength = strlen($number);
            $parity = $numberLength % 2;

            // Loop through each digit and do the maths
            $total = 0;
            for ($i = 0; $i < $numberLength; $i++) {
                $digit = $number[$i];
                // Multiply alternate digits by two
                if ($i % 2 == $parity) {
                    $digit *= 2;
                    // If the sum is two digits, add them together (in effect)
                    if ($digit > 9) {
                        $digit -= 9;
                    }
                }
                // Total up the digits
                $total += $digit;
            }

            // If the total mod 10 equals 0, the number is valid
            if ($total % 10 == 0) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * custom regex validation
     *
     * When using the regex / not_regex patterns, it may be necessary to specify
     * rules in an array instead of using | delimiters, especially if the regular
     * expression contains a | character.
     *
     * @see https://www.php.net/preg_match
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function regex(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $regexPattern = join(',', $parameters);

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (is_string($value) && preg_match($regexPattern, $value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    /**
     * custom regex validation
     *
     * When using the regex / not_regex patterns, it may be necessary to specify
     * rules in an array instead of using | delimiters, especially if the regular
     * expression contains a | character.
     *
     * @see https://www.php.net/preg_match
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array  $parameters
     */
    public static function notRegex(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $regexPattern = join(',', $parameters);

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (is_string($value) && !preg_match($regexPattern, $value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

    public static function unique(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $table = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || $value === '') {
                continue;
            }

            $attributeParts = explode('.', $attribute);
            $defaultColumn = end($attributeParts);
            $column = $parameters[1] ?? $defaultColumn;

            $except = $parameters[2] ?? null;
            $idColumn = $parameters[3] ?? 'id';

            $query = db()->select()->from($table)->where($column, $value);

            if ($except !== null) {
                $query->where($idColumn, '!=', $except);
            }

            if ($query->count() > 0) {
                $validator->addError($attribute, $rule);
            }
        }
    }

    /**
     * exists:<table>,<column>,<except>,<idColumn>
     *
     * Проверяет, существует ли значение в указанной таблице и столбце базы данных.
     *
     * @param array $data
     * @param string $pattern
     * @param string $rule
     * @param array $parameters [table, column, except (optional), idColumn (optional)]
     */
    public static function exists(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        if (count($parameters) < 2) {
            throw new InvalidArgumentException("Правило 'exists' требует как минимум два параметра: таблица и столбец.");
        }

        $table = $parameters[0];
        $column = $parameters[1];
        $except = $parameters[2] ?? null;
        $idColumn = $parameters[3] ?? 'id';

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            $query = db()->select()->from($table)->where($column, $value);

            if ($except !== null) {
                $query->where($idColumn, '!=', $except);
            }

            if (!$query->count()) {
                $validator->addError($attribute, $rule, [
                    ':table' => $table,
                    ':column' => $column,
                ]);
            }
        }
    }

    public static function confirmed(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value) {
                continue;
            }

            $confirmationAttribute = $attribute . '_confirmation';
            $confirmationValue = ArrDots::get($data, $confirmationAttribute);

            if ($confirmationValue === null || $confirmationValue !== $value) {
                $validator->addError($attribute, $rule, [
                    ':confirmation_field' => $confirmationAttribute,
                ]);
            }
        }
    }

    /**
     * Checks whether or not $value is filled,
     * i.e. $value is no empty string, array or Countable and not null.
     *
     * @param mixed $value
     *
     * @return bool true when $value is filled, elsewise false
     */
    protected static function isFilled($value)
    {
        if (is_object($value) && method_exists($value, 'getError')) {
            return $value->getError() === UPLOAD_ERR_OK;
        }

        return !(
            (is_null($value)) ||
            (is_string($value) && $value === '') ||
            ((is_array($value) || is_a($value, Countable::class)) && empty($value))
        );
    }
}
