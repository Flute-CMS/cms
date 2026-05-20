<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;

trait RulesArray
{
    public static function arrayRule(FluteValidator $validator, $data, $pattern, $rule)
    {
        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (is_array($value)) {
                continue;
            }

            $validator->addError($attribute, $rule);
        }
    }

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
}
