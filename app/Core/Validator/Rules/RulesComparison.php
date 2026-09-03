<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;
use Flute\Core\Validator\Support\ValidatorStr;
use InvalidArgumentException;
use MadeSimple\Arrays\ArrDots;

trait RulesComparison
{
    public static function equals(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue == $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    public static function notEquals(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($pattern, $field);

        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern to field');
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue != $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    public static function identical(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($pattern, $field);

        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern to field');
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue === $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

    public static function notIdentical(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($pattern, $field);

        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern to field');
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $fieldAttribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $attribute, $field) : $field;
            $fieldValue = ArrDots::get($data, $fieldAttribute);

            if ($fieldValue !== $value) {
                continue;
            }

            $validator->addError($attribute, $rule, [':field' => $fieldAttribute]);
        }
    }

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

    public static function is(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $is_a_func = sprintf('is_%s', $parameters[0]);
        if (!function_exists($is_a_func)) {
            return;
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value) {
                continue;
            }
            if (call_user_func($is_a_func, $value)) {
                continue;
            }

            $validator->addError($attribute, $rule, [':type' => $parameters[0]]);
        }
    }
}
