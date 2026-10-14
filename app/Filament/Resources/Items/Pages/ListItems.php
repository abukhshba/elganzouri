<?php

namespace App\Filament\Resources\Items\Pages;

use App\Actions\Inventory\ImportItemsAction;
use App\Exceptions\ImportValidationException;
use App\Filament\Resources\Items\ItemResource;
use App\Support\SpreadsheetTemplate;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('downloadItemsTemplate')
                    ->label(__('erp.import.download_template'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => SpreadsheetTemplate::download('items-template.xlsx', ImportItemsAction::HEADERS)),
                Actions\Action::make('importItems')
                    ->label(__('erp.import.import_items'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalDescription(__('erp.import.items_help'))
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('erp.import.file'))
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->action(function (array $data, Actions\Action $action): void {
                        try {
                            $count = app(ImportItemsAction::class)->execute($data['file']->getRealPath());
                        } catch (ImportValidationException $e) {
                            Notification::make()
                                ->title(__('erp.import.failed'))
                                ->body(new HtmlString($e->summary()))
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title(__('erp.import.items_imported', ['count' => $count]))
                            ->success()
                            ->send();
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
