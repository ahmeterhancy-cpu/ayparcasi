<?php

namespace App\Filament\Resources\DeliveryZones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveryZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')->label('Bölge')->searchable()->weight('bold'),

                TextColumn::make('fee')
                    ->label('Ücret')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? money($state) : 'Ücretsiz'),

                TextColumn::make('free_over')
                    ->label('Üstü ücretsiz')
                    ->formatStateUsing(fn ($state) => $state ? money($state) : '—'),

                IconColumn::make('same_day')->label('Aynı gün')->boolean(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
