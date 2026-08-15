<?php

namespace App\Filament\Resources\MeasurementUnits\Pages;

use App\Filament\Resources\MeasurementUnits\MeasurementUnitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMeasurementUnit extends CreateRecord
{
    protected static string $resource = MeasurementUnitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure exactly one detail has is_default = true
        if (isset($data['details'])) {
            $defaultCount = collect($data['details'])->where('is_default', true)->count();
            if ($defaultCount !== 1) {
                throw ValidationException::withMessages([
                    'data.details' => 'You must select exactly one default measurement unit detail.',
                ]);
            }
        }
        
        return $data;
    }
}
