<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingDeliveries extends TableWidget
{
    protected static ?string $heading = 'Yaklaşan teslimatlar';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->whereNotIn('status', ['delivered', 'cancelled'])
                    ->whereNotNull('delivery_date')
                    ->orderBy('delivery_date')
                    ->orderBy('created_at')
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('delivery_date')
                    ->label('Teslim')
                    ->formatStateUsing(fn ($state, Order $r) => $state?->translatedFormat('d M')
                        .($r->delivery_slot ? ' · '.$r->delivery_slot : ''))
                    ->color(fn (Order $r) => match (true) {
                        $r->delivery_date?->isPast() && ! $r->delivery_date?->isToday() => 'danger',
                        (bool) $r->delivery_date?->isToday() => 'warning',
                        default => null,
                    })
                    ->weight('bold'),

                TextColumn::make('number')->label('Sipariş'),

                TextColumn::make('recipient_name')
                    ->label('Alıcı')
                    ->description(fn (Order $r) => $r->delivery_zone_name),

                TextColumn::make('delivery_address')->label('Adres')->limit(46)->wrap(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'on_the_way' => 'primary',
                        default => 'info',
                    }),

                TextColumn::make('total')
                    ->label('Tutar')
                    ->formatStateUsing(fn ($state) => money($state)),
            ])
            ->recordActions([
                Action::make('ac')
                    ->label('Aç')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Order $r) => OrderResource::getUrl('edit', ['record' => $r])),
            ])
            ->emptyStateHeading('Bekleyen teslimat yok')
            ->emptyStateDescription('Tüm siparişler teslim edilmiş görünüyor.');
    }
}
