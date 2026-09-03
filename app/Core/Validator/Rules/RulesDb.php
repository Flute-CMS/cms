<?php

namespace Flute\Core\Validator\Rules;

use Flute\Core\Validator\FluteValidator;
use InvalidArgumentException;

trait RulesDb
{
    public static function unique(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        $table = $parameters[0];
        static::validateIdentifier($table);

        foreach (FluteValidator::getValues($data, $pattern) as $attribute => $value) {
            if (null === $value || $value === '') {
                continue;
            }

            $attributeParts = explode('.', $attribute);
            $defaultColumn = end($attributeParts);
            $column = $parameters[1] ?? $defaultColumn;
            static::validateIdentifier($column);

            $except = $parameters[2] ?? null;
            $idColumn = $parameters[3] ?? 'id';
            static::validateIdentifier($idColumn);

            $query = db()->select()->from($table)->where($column, $value);

            if ($except !== null) {
                $query->where($idColumn, '!=', $except);
            }

            if ($query->count() > 0) {
                $validator->addError($attribute, $rule);
            }
        }
    }

    public static function exists(FluteValidator $validator, $data, $pattern, $rule, $parameters)
    {
        if (count($parameters) < 2) {
            throw new InvalidArgumentException(
                "Правило 'exists' требует как минимум два параметра: таблица и столбец.",
            );
        }

        $table = $parameters[0];
        $column = $parameters[1];
        $except = $parameters[2] ?? null;
        $idColumn = $parameters[3] ?? 'id';

        static::validateIdentifier($table);
        static::validateIdentifier($column);
        static::validateIdentifier($idColumn);

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

    protected static function validateIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $identifier)) {
            throw new InvalidArgumentException(
                "Invalid SQL identifier: '{$identifier}'. Only alphanumeric characters, underscores, and dots are allowed.",
            );
        }
    }
}
