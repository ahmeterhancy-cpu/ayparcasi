<?php

namespace App\Filament\Resources\ProductTemplates\Pages;

use App\Filament\Resources\ProductTemplates\ProductTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductTemplate extends EditRecord
{
    protected static string $resource = ProductTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
