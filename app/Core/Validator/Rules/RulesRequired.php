<?php

namespace Flute\Core\Validator\Rules;

use Countable;
use Flute\Core\Validator\FluteValidator;
use Flute\Core\Validator\Support\ValidatorStr;
use InvalidArgumentException;
use MadeSimple\Arrays\ArrDots;

trait RulesRequired
{
    public static function present(FluteValidator $validator, $data, $pattern, $rule)
    {
        if (ArrDots::has($data, $pattern, $validator::WILD)) {
            return;
        }

        $validator->addError($pattern, $rule);
    }

    public static function required(FluteValidator $validator, $data, $pattern, $rule)
    {
        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            $validator->addError($pattern, $rule);

            return;
        }

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

    public static function requiredIf(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $values = array_slice($parameters, 1);
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            foreach (FluteValidator::getValues($data, $field) as $fieldAttribute => $fieldValue) {
                if (null === $fieldValue || !in_array($fieldValue, $values)) {
                    continue;
                }

                $attribute = $isWild ? ValidatorStr::overlapLeftMerge($overlap, $fieldAttribute, $pattern) : $pattern;
                $validator->addError($attribute, $rule, [
                    ':field' => $fieldAttribute,
                    '%value' => implode(',', $values),
                ]);
            }

            return;
        }

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

    public static function requiredWith(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
        }

        if (ArrDots::has($data, $field, $validator::WILD) && !ArrDots::has($data, $pattern, $validator::WILD)) {
            $validator->addError($pattern, $rule, [':field' => $field]);
        }

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

    public static function requiredWithAll(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $overlaps = [];
        $longest = 0;
        foreach ($parameters as $k => $field) {
            $isWild = strpos($field, $validator::WILD) !== false;
            $overlaps[$k] = $isWild ? ValidatorStr::overlapLeft($field, $pattern) : null;
            if ($isWild && $overlaps[$k] === false) {
                throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
            }
            $longest = $isWild && strlen($overlaps[$k]) > strlen($overlaps[$longest]) ? $k : $longest;
        }

        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            $required = false;
            foreach (FluteValidator::getValues($data, $parameters[$longest]) as $attribute => $value) {
                $required = true;
                foreach ($parameters as $k => $field) {
                    $fieldAttribute = $overlaps[$k]
                        ? ValidatorStr::overlapLeftMerge($overlaps[$k], $attribute, $field)
                        : $field;
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

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $required = true;
            foreach ($parameters as $k => $field) {
                $fieldAttribute = $overlaps[$k]
                    ? ValidatorStr::overlapLeftMerge($overlaps[$k], $attribute, $field)
                    : $field;
                $fieldValue = ArrDots::get($data, $fieldAttribute);
                $required = $required && static::isFilled($fieldValue);
                if (!$required) {
                    break;
                }
            }

            if ($required && $value === null) {
                $validator->addError($pattern, $rule);
            }
        }
    }

    public static function requiredWithAny(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $overlaps = [];
        $longest = 0;
        foreach ($parameters as $k => $field) {
            $isWild = strpos($field, $validator::WILD) !== false;
            $overlaps[$k] = $isWild ? ValidatorStr::overlapLeft($field, $pattern) : null;
            if ($isWild && $overlaps[$k] === false) {
                throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
            }
            $longest = $isWild && strlen($overlaps[$k]) > strlen($overlaps[$longest]) ? $k : $longest;
        }

        if (!ArrDots::has($data, $pattern, $validator::WILD)) {
            $required = array_reduce(
                $parameters,
                static function ($required, $field) use ($validator, $data) {
                    if (!$required && ArrDots::has($data, $field, $validator::WILD)) {
                        foreach (FluteValidator::getValues($data, $field) as $value) {
                            $required = $required || static::isFilled($value);
                        }
                    }

                    return $required;
                },
                false,
            );

            if ($required) {
                $validator->addError($pattern, $rule);
            }

            return;
        }

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            $required = false;
            foreach ($parameters as $k => $field) {
                $fieldAttribute = $overlaps[$k]
                    ? ValidatorStr::overlapLeftMerge($overlaps[$k], $attribute, $field)
                    : $field;
                $fieldValue = ArrDots::get($data, $fieldAttribute);
                $required = $required || static::isFilled($fieldValue);
                if ($required) {
                    break;
                }
            }

            if ($required && $value === null) {
                $validator->addError($pattern, $rule);
            }
        }
    }

    public static function requiredWithout(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $field = $parameters[0];
        $isWild = strpos($field, $validator::WILD) !== false;
        $overlap = ValidatorStr::overlapLeft($field, $pattern);

        if ($isWild && $overlap === false) {
            throw new InvalidArgumentException('Cannot match pattern (' . $pattern . ') to field (' . $field . ')');
        }

        if (!ArrDots::has($data, $field, $validator::WILD) && !ArrDots::has($data, $pattern, $validator::WILD)) {
            $validator->addError($pattern, $rule, [':field' => $field]);
        }

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

    protected static function isFilled($value)
    {
        if (is_object($value) && method_exists($value, 'getError')) {
            return $value->getError() === UPLOAD_ERR_OK;
        }

        return !(
            is_null($value)
            || is_string($value)
            && $value === ''
            || ( is_array($value) || is_a($value, Countable::class) )
            && empty($value)
        );
    }
}
