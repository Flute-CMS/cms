<?php

namespace Flute\Admin\Services;

use DateTimeInterface;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Repository;
use Illuminate\Support\Collection;
use Stringable;
use Throwable;

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

        $safeFilename = $this->sanitizeFilename($filename);

        $this->cleanOutputBuffers();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $headers = $exportColumns->map(fn (TD $column) => $this->cleanText($column->getTitle()))->toArray();
        fputcsv($output, $headers, ';');

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

        $safeFilename = $this->sanitizeFilename($filename);

        $this->cleanOutputBuffers();

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';

        echo '<tr>';
        foreach ($exportColumns as $column) {
            echo '<th style="background-color:#f0f0f0;font-weight:bold;">' . htmlspecialchars($this->cleanText($column->getTitle())) . '</th>';
        }
        echo '</tr>';

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

            if ($column->isSelectionColumn()) {
                return false;
            }

            return !($name === 'actions' || str_contains($name, 'action'));
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
        if ($column->hasRender()) {
            try {
                $rendered = $column->renderValue($row);

                return $this->formatValue($rendered);
            } catch (Throwable $e) {
                // Fall through to direct property access
            }
        }

        $name = $column->getName();

        if (str_contains($name, '.')) {
            $parts = explode('.', $name);
            $value = $row;

            foreach ($parts as $part) {
                if (is_array($value)) {
                    $value = $value[$part] ?? null;
                } elseif (is_object($value)) {
                    try {
                        $value = $value->{$part} ?? null;
                    } catch (Throwable $e) {
                        $value = null;

                        break;
                    }
                } else {
                    $value = null;

                    break;
                }
            }

            return $this->formatValue($value);
        }

        if ($row instanceof Repository) {
            $value = $row->getContent($name);
        } elseif (is_array($row)) {
            $value = $row[$name] ?? '';
        } elseif (is_object($row)) {
            try {
                $value = $row->{$name} ?? '';
            } catch (Throwable $e) {
                $value = '';
            }
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

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($value)) {
            if ($value instanceof \Illuminate\Contracts\View\View || $value instanceof \Illuminate\View\View) {
                return strip_tags((string) $value->render());
            }

            if ($value instanceof \Illuminate\Contracts\Support\Renderable) {
                return strip_tags((string) $value->render());
            }

            if ($value instanceof Stringable || method_exists($value, '__toString')) {
                return (string) $value;
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    /**
     * Sanitize a filename for use in Content-Disposition header.
     */
    protected function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename);
        $filename = substr($filename, 0, 255);

        return $filename ?: 'export';
    }

    /**
     * Clean text by stripping HTML and normalizing whitespace.
     * Also prevents CSV formula injection.
     */
    protected function cleanText($text): string
    {
        if ($text === null) {
            return '';
        }

        $text = (string) $text;

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = strip_tags($text);

        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (isset($text[0]) && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $text = "'" . $text;
        }

        return $text;
    }

    /**
     * Discard all output buffers to prevent HTML leaking into export files.
     */
    protected function cleanOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
