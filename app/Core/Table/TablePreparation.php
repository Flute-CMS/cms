<?php

namespace Flute\Core\Table;

/**
 * Класс, служащащий для нормализации данных для будущего восприятия DataTable'ом
 */
class TablePreparation
{
    public static function normalize(array $columns, array $data)
    {
        $result = [];

        foreach ($data as $item) {
            $columns_r = [];

            foreach ($columns as $column) {
                if (is_array($item)) {
                    $columns_r[] = isset($item[$column]) ? $item[$column] : '';
                } else {
                    $columns_r[] = isset($item->{$column}) ? $item->{$column} : '';
                }
            }

            $result[] = $columns_r;
        }

        return $result;
    }
}