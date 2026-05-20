<?php

namespace Flute\Core\Validator\Rules;

use DateTime;
use Flute\Core\Validator\FluteValidator;
use Throwable;

trait RulesDateTime
{
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
                } catch (Throwable) {
                    $validator->addError($attribute, $rule);
                }
            }
        }
    }

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

                $date1 = DateTime::createFromFormat('Y-m-d H:i:s', $value) ?: DateTime::createFromFormat(
                    'Y-m-d',
                    $value,
                );
                $date2 = DateTime::createFromFormat('Y-m-d H:i:s', $compareValue) ?: DateTime::createFromFormat(
                    'Y-m-d',
                    $compareValue,
                );

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
                } catch (Throwable $e) {
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
}
