<?php

namespace App\Filament\Resources\Addons;

use App\Filament\Resources\Addons\Pages\CreateAddon;
use App\Filament\Resources\Addons\Pages\EditAddon;
use App\Filament\Resources\Addons\Pages\ListAddons;
use App\Filament\Resources\Addons\Schemas\AddonForm;
use App\Filament\Resources\Addons\Tables\AddonsTable;
use App\Models\Addon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Ek ürünler';

    protected static ?string $modelLabel = 'ek ürün';

    protected static ?string $pluralModelLabel = 'ek ürünler';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AddonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddonsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAddons::route('/'),
            'create' => CreateAddon::route('/create'),
            'edit' => EditAddon::route('/{record}/edit'),
        ];
    }
}
