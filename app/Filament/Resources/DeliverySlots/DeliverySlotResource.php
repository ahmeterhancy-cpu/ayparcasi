<?php

namespace App\Filament\Resources\DeliverySlots;

use App\Filament\Resources\DeliverySlots\Pages\CreateDeliverySlot;
use App\Filament\Resources\DeliverySlots\Pages\EditDeliverySlot;
use App\Filament\Resources\DeliverySlots\Pages\ListDeliverySlots;
use App\Filament\Resources\DeliverySlots\Schemas\DeliverySlotForm;
use App\Filament\Resources\DeliverySlots\Tables\DeliverySlotsTable;
use App\Models\DeliverySlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliverySlotResource extends Resource
{
    protected static ?string $model = DeliverySlot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Teslimat';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Saat aralıkları';

    protected static ?string $modelLabel = 'saat aralığı';

    protected static ?string $pluralModelLabel = 'saat aralıkları';

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return DeliverySlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliverySlotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliverySlots::route('/'),
            'create' => CreateDeliverySlot::route('/create'),
            'edit' => EditDeliverySlot::route('/{record}/edit'),
        ];
    }
}
