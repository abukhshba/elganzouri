<?php

namespace App\Filament\Resources\OpeningBalances\Pages;

use App\Actions\Inventory\ImportOpeningStockAction;
use App\Exceptions\ImportValidationException;
use App\Filament\Resources\OpeningBalances\OpeningBalanceResource;
use App\Models\Item;
use App\Models\Warehouse;
use App\Support\SpreadsheetTemplate;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListOpeningBalances extends ListRecords
{
    protected static string $resource = OpeningBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('downloadOpeningTemplate')
                    ->label(__('erp.import.download_template'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => SpreadsheetTemplate::download(
                        'opening-stock-template.xlsx',
                        ImportOpeningStockAction::HEADERS,
                        // Pre-filled with every item so the user only types quantity, cost and price
                        Item::with('measurementUnit.details')->orderBy('sku')->get()
                            ->map(fn (Item $item) => [
                                $item->sku,
                                $item->name,
                                (string) $item->measurementUnit?->details->firstWhere('is_default', true)?->name,
                                '', '', '',
                            ])
                            ->all(),
                    )),
                Actions\Action::make('importOpeningStock')
                    ->label(__('erp.import.upload_opening'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalDescription(__('erp.import.opening_help'))
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(__('erp.resources.warehouse'))
                            ->options(fn () => Warehouse::query()->orderBy('id')->get()->pluck('name', 'id'))
                            ->default(fn () => Warehouse::query()->orderBy('id')->value('id'))
                            ->required(),
                        DatePicker::make('issue_date')
                            ->label(__('erp.fields.issue_date'))
                            ->default(now())
                            ->required(),
                        FileUpload::make('file')
                            ->label(__('erp.import.file'))
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->storeFiles(false)
                            ->required(),
                        Textarea::make('notes')
                            ->label(__('erp.fields.description'))
                            ->rows(2),
                    ])
                    ->action(function (array $data, Actions\Action $action) {
                        try {
                            $opening = app(ImportOpeningStockAction::class)->execute(
                                path: $data['file']->getRealPath(),
                                warehouseId: (int) $data['warehouse_id'],
                                issueDate: (string) $data['issue_date'],
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

                            $action->halt();
                        }

                        Notification::make()
                            ->title(__('erp.import.opening_imported', ['count' => $opening->items()->count()]))
                            ->success()
                            ->send();

                        return redirect(OpeningBalanceResource::getUrl('view', ['record' => $opening]));
                    }),
            ])
                ->label(__('erp.import.excel'))
                ->icon('heroicon-o-table-cells')
                ->button()
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
}
