<?php

namespace App\Filament\Resources\Banners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('title')->label('Duyuru')->weight('bold')->wrap()->searchable(),

                TextColumn::make('placement')
                    ->label('Yer')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'strip' => 'Üst şerit',
                        'promo' => 'Kampanya bloğu',
                        'hero' => 'Hero',
                        default => $state,
                    }),

                TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d M Y H:i') ?? 'Süresiz')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),

                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
