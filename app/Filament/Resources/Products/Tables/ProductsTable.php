<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('hero_image')
                    ->label('')
                    ->disk('public')
                    ->imageSize(48),

                TextColumn::make('name')
                    ->label('Ürün')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record?->categories->pluck('name')->implode(' · ')),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record?->has_variants
                        ? money($record->display_price).' başlangıç'
                        : money($state)),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->state(fn ($record) => $record?->stock_label)
                    ->color(fn ($record) => match ($record?->stock_state) {
                        'out_of_stock' => 'danger',
                        'low' => 'warning',
                        'made_to_order' => 'info',
                        default => 'success',
                    }),

                IconColumn::make('is_active')
                    ->label('Satışta')
                    ->boolean(),

                IconColumn::make('is_featured')
                    ->label('Öne çıkan')
                    ->boolean(),

                TextColumn::make('badge')
                    ->label('Rozet')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Güncellendi')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Satışta')
                    ->placeholder('Hepsi')
                    ->trueLabel('Satışta olanlar')
                    ->falseLabel('Yayından kaldırılanlar'),

                TernaryFilter::make('is_featured')
                    ->label('Öne çıkan')
                    ->placeholder('Hepsi')
                    ->trueLabel('Öne çıkanlar')
                    ->falseLabel('Öne çıkmayanlar'),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ])
            ->emptyStateHeading('Henüz ürün yok')
            ->emptyStateDescription('İlk ürününüzü ekleyerek başlayın.');
    }
}
