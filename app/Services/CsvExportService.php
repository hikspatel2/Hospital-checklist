<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

/**
 * Secure CSV Export Service
 * Protects against CSV Formula Injection (CWE-1236) across all export streams.
 * Prevents unauthorized public file generation and audits all export events.
 */
class CsvExportService
{
    /**
     * Dangerous trigger characters that spreadsheet engines (Excel, LibreOffice, Google Sheets)
     * interpret as formulas or execution triggers.
     */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Sanitize a single cell value to neutralize CSV formula injection
     */
    public static function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        $str = (string)$value;
        // Strip null bytes
        $str = str_replace("\0", '', $str);

        if ($str === '') {
            return '';
        }

        // Check if raw string or space-trimmed string starts with dangerous formula trigger
        $firstChar = $str[0];
        $trimmed = ltrim($str, " ");
        $trimmedFirstChar = $trimmed !== '' ? $trimmed[0] : '';

        if (in_array($firstChar, self::FORMULA_TRIGGERS, true) || in_array($trimmedFirstChar, self::FORMULA_TRIGGERS, true)) {
            // Neutralize by prepending a single quote
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Sanitize an entire row of data
     */
    public static function sanitizeRow(array $row): array
    {
        $sanitized = [];
        foreach ($row as $cell) {
            $sanitized[] = self::sanitizeCell($cell);
        }
        return $sanitized;
    }

    /**
     * Stream CSV directly to browser with security headers and UTF-8 BOM
     */
    public static function stream(
        string $filename,
        array $headers,
        array $rows,
        ?int $userId = null,
        string $reportType = 'general_report'
    ): void {
        // Log export event to immutable audit trail
        AuditLog::record(
            $userId,
            AuditLog::EVENT_REPORT_EXPORTED,
            'reports',
            null,
            null,
            [
                'report_type' => $reportType,
                'filename'    => $filename,
                'rows_count'  => count($rows),
                'columns'     => $headers,
                'exported_at' => date('Y-m-d H:i:s'),
            ]
        );

        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        if (!str_ends_with(strtolower($safeFilename), '.csv')) {
            $safeFilename .= '.csv';
        }

        // Set security headers to prevent caching and force attachment download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            throw new \RuntimeException('Failed to open output stream for CSV generation.');
        }

        // UTF-8 Byte Order Mark (BOM) for correct Gujarati / Unicode character rendering in Microsoft Excel
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Output Headers
        fputcsv($out, self::sanitizeRow($headers));

        // Output Rows
        foreach ($rows as $row) {
            fputcsv($out, self::sanitizeRow($row));
        }

        fclose($out);
        exit;
    }

    /**
     * Generate temporary export file outside public web root and purge stale temp exports
     */
    public static function generateTempFile(string $prefix, array $headers, array $rows): string
    {
        $tempDir = dirname(__DIR__, 2) . '/storage/temp/exports';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0750, true);
        }

        // Self-purging: remove export files older than 1 hour
        self::purgeStaleTempFiles($tempDir, 3600);

        $randomSuffix = bin2hex(random_bytes(8));
        $filePath = $tempDir . '/' . $prefix . '_' . date('Ymd_His') . '_' . $randomSuffix . '.csv';

        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Unable to open temp file for writing: {$filePath}");
        }

        // UTF-8 BOM
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write Headers
        fputcsv($handle, self::sanitizeRow($headers));

        // Write Rows
        foreach ($rows as $row) {
            fputcsv($handle, self::sanitizeRow($row));
        }

        fclose($handle);

        return $filePath;
    }

    /**
     * Purge temporary export files older than specified seconds
     */
    public static function purgeStaleTempFiles(string $tempDir, int $maxAgeSeconds = 3600): void
    {
        if (!is_dir($tempDir)) {
            return;
        }

        $now = time();
        $files = glob($tempDir . '/*.csv');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAgeSeconds) {
                @unlink($file);
            }
        }
    }
}
