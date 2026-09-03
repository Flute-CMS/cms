<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;

trait RulesPattern
{
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

    public static function cardNumber(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }

            $number = preg_replace('/\D/', '', $value);

            $numberLength = strlen($number);
            $parity = $numberLength % 2;

            $total = 0;
            for ($i = 0; $i < $numberLength; $i++) {
                $digit = $number[$i];
                if (( $i % 2 ) == $parity) {
                    $digit *= 2;
                    if ($digit > 9) {
                        $digit -= 9;
                    }
                }
                $total += $digit;
            }

            if (( $total % 10 ) == 0) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }
}
