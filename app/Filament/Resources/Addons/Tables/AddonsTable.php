<?php

namespace App\Filament\Resources\Addons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->imageSize(40),
                TextColumn::make('name')->label('Ek ürün')->weight('bold')->description(fn ($r) => $r?->description),
                TextColumn::make('price')->label('Fiyat')->formatStateUsing(fn ($state) => '+'.money($state)),
                // Ek ürün artık ürüne bağlı; hiçbir ürüne seçilmemişse
                // vitrinde hiç görünmez, bu yüzden sayı burada duruyor.
                TextColumn::make('products_count')
                    ->label('Ürün')
                    ->counts('products')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'gray' : 'warning')
                    ->tooltip(fn ($state) => $state > 0
                        ? null
                        : 'Hiçbir ürüne seçilmemiş — vitrinde görünmüyor.'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
