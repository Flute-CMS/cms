<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;

trait RulesNumeric
{
    public static function numeric(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_numeric($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

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

    public static function min(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $min = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || $value != '0' && empty($value)) {
                continue;
            }

            if ($value >= $min) {
                break;
            }

            $validator->addError($attribute, $rule, [':min' => $min]);
        }
    }

    public static function max(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $max = $parameters[0];

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || $value != '0' && empty($value)) {
                continue;
            }

            if ($value <= $max) {
                break;
            }

            $validator->addError($attribute, $rule, [':max' => $max]);
        }
    }

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
}
