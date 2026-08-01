<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover')->label('')->disk('public')->imageSize(44),

                TextColumn::make('title')
                    ->label('Yazı')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($r) => Str::limit((string) $r?->excerpt, 80)),

                TextColumn::make('published_at')
                    ->label('Yayın')
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d M Y') ?? 'Taslak')
                    ->sortable(),

                IconColumn::make('is_active')->label('Yayında')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
