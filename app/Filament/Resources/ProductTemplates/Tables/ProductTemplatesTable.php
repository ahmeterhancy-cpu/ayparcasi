<?php

namespace App\Filament\Resources\ProductTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Şablon')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn ($record) => str($record?->short_description ?? '')->limit(70)),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(fn ($state, $record) => filled($record?->variants)
                        ? count($record->variants).' boy'
                        : money($state)),

                TextColumn::make('category_ids')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($record) => count($record?->category_ids ?? []) ?: '—')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Kullanımda')
                    ->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ])
            ->emptyStateHeading('Henüz şablon yok')
            ->emptyStateDescription('Sık eklediğiniz ürün tipleri için bir şablon açın: açıklama, bakım notu, kategori, ek ürünler ve boy seti hazır gelsin.');
    }
}
