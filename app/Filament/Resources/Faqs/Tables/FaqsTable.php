<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('question')
                    ->label('Soru')
                    ->searchable()
                    ->weight('bold')
                    ->wrap()
                    ->description(fn ($r) => Str::limit((string) $r?->answer, 90)),

                IconColumn::make('is_active')->label('Yayında')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
