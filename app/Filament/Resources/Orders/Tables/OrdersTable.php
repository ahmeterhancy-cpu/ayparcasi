<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('number')
                    ->label('Sipariş')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Order $r) => $r->created_at?->translatedFormat('d M, H:i')),

                TextColumn::make('customer_name')
                    ->label('Müşteri')
                    ->searchable()
                    ->description(fn (Order $r) => $r->customer_phone),

                TextColumn::make('recipient_name')
                    ->label('Alıcı')
                    ->searchable()
                    ->description(fn (Order $r) => $r->delivery_zone_name),

                TextColumn::make('delivery_date')
                    ->label('Teslim')
                    ->sortable()
                    ->formatStateUsing(fn ($state, Order $r) => ($state?->translatedFormat('d M') ?? '—')
                        .($r->delivery_slot ? "\n".$r->delivery_slot : ''))
                    ->color(fn (Order $r) => $r->delivery_date?->isToday() ? 'warning' : null)
                    ->weight(fn (Order $r) => $r->delivery_date?->isToday() ? 'bold' : null),

                TextColumn::make('total')
                    ->label('Tutar')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => money($state)),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'preparing' => 'info',
                        'on_the_way' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->label('Ödeme')
                    ->badge()
                    ->formatStateUsing(fn ($state, Order $r) => (Order::PAYMENT_STATUSES[$state] ?? $state)
                        .' · '.$r->payment_method_label)
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Order::STATUSES)
                    ->multiple(),

                SelectFilter::make('payment_status')
                    ->label('Ödeme durumu')
                    ->options(Order::PAYMENT_STATUSES)
                    ->multiple(),

                SelectFilter::make('delivery_zone_id')
                    ->label('Bölge')
                    ->relationship('zone', 'name'),

                Filter::make('bugun')
                    ->label('Bugün teslim edilecekler')
                    ->query(fn ($query) => $query->whereDate('delivery_date', today())),

                Filter::make('acik')
                    ->label('Açık siparişler')
                    ->default()
                    ->query(fn ($query) => $query->whereNotIn('status', ['delivered', 'cancelled'])),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Order $r) => wa_link(
                        "Merhaba {$r->customer_name}, {$r->number} numaralı siparişiniz hakkında yazıyoruz.",
                        $r->customer_phone
                    ))
                    ->openUrlInNewTab(),

                Action::make('ilerlet')
                    ->label('Durumu ilerlet')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->visible(fn (Order $r) => ! in_array($r->status, ['delivered', 'cancelled'], true))
                    ->action(function (Order $record) {
                        $flow = ['pending', 'confirmed', 'preparing', 'on_the_way', 'delivered'];
                        $i = array_search($record->status, $flow, true);
                        $next = $flow[$i + 1] ?? 'delivered';

                        $record->update(['status' => $next]);

                        Notification::make()
                            ->title($record->number.' → '.Order::STATUSES[$next])
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('Aç'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('hazirlaniyor')
                        ->label('Hazırlanıyor olarak işaretle')
                        ->icon('heroicon-o-clock')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'preparing']))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('teslim')
                        ->label('Teslim edildi olarak işaretle')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'delivered']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Henüz sipariş yok');
    }
}
