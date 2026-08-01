<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Müşteri')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (?User $r) => $r?->email),

                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('orders_count')
                    ->label('Sipariş')
                    ->badge()
                    ->sortable(),

                TextColumn::make('orders_sum_total')
                    ->label('Toplam harcama')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => money((float) $state)),

                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d M Y')),
            ])
            ->filters([
                Filter::make('siparis_veren')
                    ->label('Sipariş vermiş olanlar')
                    ->query(fn ($query) => $query->has('orders')),
            ])
            ->recordActions([
                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (User $r) => filled($r->phone))
                    ->url(fn (User $r) => wa_link("Merhaba {$r->name},", $r->phone))
                    ->openUrlInNewTab(),

                ViewAction::make()->label('Aç'),
            ])
            ->emptyStateHeading('Henüz kayıtlı müşteri yok')
            ->emptyStateDescription('Vitrinden hesap oluşturan müşteriler burada listelenir.');
    }
}
