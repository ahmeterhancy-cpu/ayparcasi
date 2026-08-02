<?php

namespace App\Filament\Resources\ProductTemplates\Pages;

use App\Filament\Resources\ProductTemplates\ProductTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductTemplate extends CreateRecord
{
    protected static string $resource = ProductTemplateResource::class;
}
