<?php

namespace App\Actions\Inventory;

use App\Exceptions\ImportValidationException;
use App\Models\Category;
use App\Models\Item;
use App\Models\MeasurementUnit;
use App\Support\SpreadsheetTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create items from the items template. All rows are validated first; nothing is saved if any row fails.
 */
class ImportItemsAction
{
    public const HEADERS = ['name_ar', 'name_en', 'sku', 'category', 'unit', 'barcode', 'min_stock_alert'];

    /**
     * @return int number of items created
     */
    public function execute(string $path): int
    {
        $rows = SpreadsheetTemplate::read($path);

        if ($rows === []) {
            throw new ImportValidationException([__('erp.import.empty_file')]);
        }

        $units = $this->nameIndex(MeasurementUnit::all(), withSlug: true);
        $categories = $this->nameIndex(Category::all());
        $existingSkus = array_flip(array_map('strtolower', Item::withTrashed()->pluck('sku')->all()));

        $errors = [];
        $seenSkus = [];

        foreach ($rows as $line => $row) {
            $sku = $row['sku'] ?? '';
            $rowErrors = [];

            if (($row['name_ar'] ?? '') === '' && ($row['name_en'] ?? '') === '') {
                $rowErrors[] = __('erp.import.name_required');
            }

            if ($sku === '') {
                $rowErrors[] = __('erp.import.sku_required');
            } elseif (isset($existingSkus[strtolower($sku)])) {
                $rowErrors[] = __('erp.import.sku_exists', ['sku' => $sku]);
            } elseif (isset($seenSkus[strtolower($sku)])) {
                $rowErrors[] = __('erp.import.sku_duplicate', ['sku' => $sku, 'row' => $seenSkus[strtolower($sku)]]);
            }

            if (($row['category'] ?? '') === '') {
                $rowErrors[] = __('erp.import.category_required');
            }

            if (($row['unit'] ?? '') === '') {
                $rowErrors[] = __('erp.import.unit_required');
            } elseif (! isset($units[mb_strtolower($row['unit'])])) {
                $rowErrors[] = __('erp.import.unit_unknown', ['unit' => $row['unit']]);
            }

            if (($row['min_stock_alert'] ?? '') !== '' && ! is_numeric($row['min_stock_alert'])) {
                $rowErrors[] = __('erp.import.not_a_number', ['field' => 'min_stock_alert']);
            }

            foreach ($rowErrors as $error) {
                $errors[] = __('erp.import.row', ['row' => $line]).': '.$error;
            }

            if ($sku !== '') {
                $seenSkus[strtolower($sku)] ??= $line;
            }
        }

        if ($errors !== []) {
            throw new ImportValidationException($errors);
        }

        return DB::transaction(function () use ($rows, $units, $categories) {
            foreach ($rows as $row) {
                $categoryKey = mb_strtolower($row['category']);
                $categories[$categoryKey] ??= $this->createCategory($row['category'])->id;

                $nameAr = $row['name_ar'] !== '' ? $row['name_ar'] : $row['name_en'];
                $nameEn = $row['name_en'] !== '' ? $row['name_en'] : $row['name_ar'];

                Item::create([
                    'name' => ['ar' => $nameAr, 'en' => $nameEn],
                    'sku' => $row['sku'],
                    'barcode' => ($row['barcode'] ?? '') !== '' ? $row['barcode'] : null,
                    'category_id' => $categories[$categoryKey],
                    'measurement_unit_id' => $units[mb_strtolower($row['unit'])],
                    'min_stock_alert' => (float) (($row['min_stock_alert'] ?? '') !== '' ? $row['min_stock_alert'] : 0),
                    'is_active' => true,
                ]);
            }

            return count($rows);
        });
    }

    /**
     * Map every Arabic/English name (and optionally slug), lowercased, to the model id.
     *
     * @return array<string, int>
     */
    protected function nameIndex(iterable $models, bool $withSlug = false): array
    {
        $index = [];

        foreach ($models as $model) {
            foreach ($model->getTranslations('name') as $name) {
                if ($name !== '' && $name !== null) {
                    $index[mb_strtolower(trim($name))] ??= $model->id;
                }
            }

            if ($withSlug && $model->slug) {
                $index[mb_strtolower($model->slug)] ??= $model->id;
            }
        }

        return $index;
    }

    protected function createCategory(string $name): Category
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;

        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return Category::create(['name' => ['ar' => $name, 'en' => $name], 'slug' => $slug]);
    }
}
