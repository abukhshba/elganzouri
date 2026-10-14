<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

trait BuildsSpreadsheets
{
    /**
     * @param  list<list<string|int|float>>  $rows  first row is the header
     */
    protected function xlsxPath(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    /**
     * @param  list<list<string|int|float>>  $rows  first row is the header
     */
    protected function xlsxUpload(array $rows, string $name = 'upload.xlsx'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents($this->xlsxPath($rows)));
    }
}
