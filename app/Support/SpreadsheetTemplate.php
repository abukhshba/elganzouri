<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Minimal .xlsx template download and upload parsing (header row + data rows).
 */
class SpreadsheetTemplate
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float>>  $exampleRows
     */
    public static function download(string $filename, array $headers, array $exampleRows = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $exampleRows) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($exampleRows as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Read the first sheet into rows keyed by header name, skipping blank rows.
     *
     * @return array<int, array<string, string>> keyed by the Excel row number
     */
    public static function read(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);

        $headers = null;
        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $index => $row) {
                $values = array_map(fn ($value) => trim((string) ($value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)), $row->toArray());

                if ($headers === null) {
                    $headers = array_map(fn ($header) => strtolower($header), $values);

                    continue;
                }

                if (implode('', $values) === '') {
                    continue;
                }

                $rows[$index] = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), ''));
            }

            break;
        }

        $reader->close();

        return $rows;
    }
}
