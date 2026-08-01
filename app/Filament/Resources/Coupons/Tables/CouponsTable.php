<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('value')
                    ->label('İndirim')
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'percent'
                        ? '%'.rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',')
                        : money($state)),

                TextColumn::make('min_total')
                    ->label('Alt sınır')
                    ->formatStateUsing(fn ($state) => $state ? money($state) : '—'),

                TextColumn::make('used_count')
                    ->label('Kullanım')
                    ->formatStateUsing(fn ($state, $record) => $record->usage_limit
                        ? $state.' / '.$record->usage_limit
                        : (string) $state),

                TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d M Y') ?? 'Süresiz')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),

                IconColumn::make('free_delivery')
                    ->label('Ücretsiz teslimat')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
