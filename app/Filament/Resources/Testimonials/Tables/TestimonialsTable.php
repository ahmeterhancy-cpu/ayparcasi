<?php

namespace App\Filament\Resources\Testimonials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Müşteri')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($r) => $r?->city),

                TextColumn::make('rating')
                    ->label('Puan')
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state))
                    ->color('warning'),

                TextColumn::make('body')->label('Yorum')->limit(70)->wrap(),

                IconColumn::make('is_active')->label('Yayında')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
