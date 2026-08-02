<?php

namespace App\Filament\Resources\ProductTemplates;

use App\Filament\Resources\ProductTemplates\Pages\CreateProductTemplate;
use App\Filament\Resources\ProductTemplates\Pages\EditProductTemplate;
use App\Filament\Resources\ProductTemplates\Pages\ListProductTemplates;
use App\Filament\Resources\ProductTemplates\Schemas\ProductTemplateForm;
use App\Filament\Resources\ProductTemplates\Tables\ProductTemplatesTable;
use App\Models\ProductTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Ürün şablonları';

    protected static ?string $modelLabel = 'şablon';

    protected static ?string $pluralModelLabel = 'şablonlar';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProductTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductTemplates::route('/'),
            'create' => CreateProductTemplate::route('/create'),
            'edit' => EditProductTemplate::route('/{record}/edit'),
        ];
    }
}
