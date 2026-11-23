<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvService
{
    /**
     * Parse a CSV file or file path into an array of associative rows based on headers.
     */
    public function parseCsv(string $filePath): array
    {
        $rows = [];
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return $rows;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return $rows;
        }

        // Detect BOM and skip
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Auto-detect delimiter from the header line (, or ; or \t)
        $firstLine = fgets($handle);
        $delimiter = ',';
        if ($firstLine !== false) {
            $semicolons = substr_count($firstLine, ';');
            $commas = substr_count($firstLine, ',');
            $tabs = substr_count($firstLine, "\t");
            if ($semicolons > $commas && $semicolons > $tabs) {
                $delimiter = ';';
            } elseif ($tabs > $commas && $tabs > $semicolons) {
                $delimiter = "\t";
            }
        }
        rewind($handle);
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 8192, $delimiter);
        if (! $headers) {
            fclose($handle);

            return $rows;
        }

        // Clean headers (trim, lowercase, strip spaces)
        $cleanHeaders = array_map(function ($h) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($h))));
        }, $headers);

        while (($data = fgetcsv($handle, 8192, $delimiter)) !== false) {
            if (empty(array_filter($data, fn ($v) => trim($v) !== ''))) {
                continue; // Skip empty lines
            }

            $row = [];
            foreach ($cleanHeaders as $index => $header) {
                $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Generate a downloadable CSV streamed response.
     */
    public function downloadCsv(string $filename, array $headers, array $data): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $data) {
            $handle = fopen('php://output', 'w');

            // Output UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, $headers);

            foreach ($data as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
