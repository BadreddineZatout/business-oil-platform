<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['category'] = $this->record->categories->whereNull('parent_id')->pluck('id')->toArray();
        $data['sub_category'] = $this->record->categories->whereNotNull('parent_id')->pluck('id')->toArray();

        return $data;
    }
}
