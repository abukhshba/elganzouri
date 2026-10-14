<?php

namespace App\Filament\Resources\OpeningBalances\Pages;

use App\Actions\Inventory\PostOpeningBalanceAction;
use App\Exceptions\ImportValidationException;
use App\Filament\Resources\OpeningBalances\OpeningBalanceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CreateOpeningBalance extends CreateRecord
{
    protected static string $resource = OpeningBalanceResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(PostOpeningBalanceAction::class)->execute(
                warehouseId: (int) $data['warehouse_id'],
                issueDate: (string) $data['issue_date'],
                lines: array_values($data['lines']),
                userId: auth()->id(),
                notes: $data['notes'] ?? null,
            );
        } catch (ImportValidationException $e) {
            Notification::make()
                ->title(__('erp.import.opening_failed'))
                ->body(new HtmlString($e->summary()))
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
