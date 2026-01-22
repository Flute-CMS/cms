<?php

namespace Flute\Admin\Services;

use DateTimeInterface;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Repository;
use Illuminate\Support\Collection;

class TableExportService
{
    /**
     * Export data to CSV format.
     *
     * @param Collection $rows Data rows to export
     * @param Collection $columns Column definitions
     * @param string $filename Output filename
     */
    public function exportCsv(Collection $rows, Collection $columns, string $filename = 'export.csv'): void
    {
        $exportColumns = $this->filterExportableColumns($columns);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8 Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write headers
        $headers = $exportColumns->map(fn (TD $column) => $this->cleanText($column->getTitle()))->toArray();
        fputcsv($output, $headers, ';');

        // Write data rows
        foreach ($rows as $row) {
            $rowData = $this->extractRowData($row, $exportColumns);
            fputcsv($output, $rowData, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Export data to Excel format (simple HTML table that Excel can read).
     *
     * @param Collection $rows Data rows to export
     * @param Collection $columns Column definitions
     * @param string $filename Output filename
     */
    public function exportExcel(Collection $rows, Collection $columns, string $filename = 'export.xlsx'): void
    {
        $exportColumns = $this->filterExportableColumns($columns);

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';

        // Write headers
        echo '<tr>';
        foreach ($exportColumns as $column) {
            echo '<th style="background-color:#f0f0f0;font-weight:bold;">' . htmlspecialchars($this->cleanText($column->getTitle())) . '</th>';
        }
        echo '</tr>';

        // Write data rows
        foreach ($rows as $row) {
            echo '<tr>';
            $rowData = $this->extractRowData($row, $exportColumns);
            foreach ($rowData as $value) {
                echo '<td>' . htmlspecialchars($value) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table>';
        echo '</body></html>';
        exit;
    }

    /**
     * Filter columns that should be exported (exclude actions, selection, etc.)
     */
    protected function filterExportableColumns(Collection $columns): Collection
    {
        return $columns->filter(static function (TD $column) {
            $name = strtolower($column->getName());

            // Exclude selection columns
            if ($column->isSelectionColumn()) {
                return false;
            }

            // Exclude actions columns
            return !($name === 'actions' || str_contains($name, 'action'))



            ;
        });
    }

    /**
     * Extract data from a row based on column definitions.
     */
    protected function extractRowData($row, Collection $columns): array
    {
        $data = [];

        foreach ($columns as $column) {
            $value = $this->getColumnValue($row, $column);
            $data[] = $this->cleanText($value);
        }

        return $data;
    }

    /**
     * Get the value of a column from a row.
     */
    protected function getColumnValue($row, TD $column): string
    {
        $name = $column->getName();

        // Handle nested properties (e.g., 'user.name')
        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $value = $row;

            foreach ($parts as $part) {
                if (is_array($value)) {
                    $value = $value[$part] ?? null;
                } elseif (is_object($value)) {
                    $value = $value->{$part} ?? null;
                } else {
                    $value = null;

                    break;
                }
            }

            return $this->formatValue($value);
        }

        // Direct property access
        if ($row instanceof Repository) {
            $value = $row->getContent($name);
        } elseif (is_array($row)) {
            $value = $row[$name] ?? '';
        } elseif (is_object($row)) {
            $value = $row->{$name} ?? '';
        } else {
            $value = '';
        }

        return $this->formatValue($value);
    }

    /**
     * Format value for export.
     */
    protected function formatValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? __('def.yes') : __('def.no');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y H:i:s');
        }

        if (is_array($value) || is_object($value)) {
            if (is_object($value) && method_exists($value, '__toString')) {
                return (string) $value;
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    /**
     * Clean text by stripping HTML and normalizing whitespace.
     */
    protected function cleanText($text): string
    {
        if ($text === null) {
            return '';
        }

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Strip HTML tags
        $text = strip_tags($text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }
}
