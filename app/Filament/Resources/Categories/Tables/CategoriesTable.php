<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->imageSize(44),

                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record?->parent?->name
                        ? '↳ '.$record->parent->name.' altında'
                        : 'Ana kategori'),

                TextColumn::make('products_count')
                    ->label('Ürün')
                    ->counts('products')
                    ->badge(),

                IconColumn::make('is_featured')
                    ->label('Ana sayfada')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Görünür')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ]);
    }
}
