<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;
use MadeSimple\Arrays\ArrDots;

trait RulesMisc
{
    public static function nullable(FluteValidator $validator, $data, $pattern, $rule)
    {
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            return;
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
}
