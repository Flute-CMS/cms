<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;

trait RulesString
{
    public static function string(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_string($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

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

    public static function humanName(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || empty($value)) {
                continue;
            }
            if (preg_match('/^([\p{L}\p{N} _\'.-])+$/u', $value) === 1) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

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
}
