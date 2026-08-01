<?php

namespace App\Filament\Resources\StockInquiries;

use App\Filament\Resources\StockInquiries\Pages\ListStockInquiries;
use App\Filament\Resources\StockInquiries\Tables\StockInquiriesTable;
use App\Models\StockInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StockInquiryResource extends Resource
{
    protected static ?string $model = StockInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Stok soruları';

    protected static ?string $modelLabel = 'stok sorusu';

    protected static ?string $pluralModelLabel = 'stok soruları';

    public static function table(Table $table): Table
    {
        return StockInquiriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('product');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('handled', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockInquiries::route('/'),
        ];
    }
}
