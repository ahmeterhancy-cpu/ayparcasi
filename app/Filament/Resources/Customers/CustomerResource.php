<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Müşteriler';

    protected static ?string $modelLabel = 'müşteri';

    protected static ?string $pluralModelLabel = 'müşteriler';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'musteriler';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'customer')
            ->withCount('orders')
            ->withSum('orders', 'total');
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    /** Müşteri kayıtları vitrinden gelir; panelden elle açılmaz. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
