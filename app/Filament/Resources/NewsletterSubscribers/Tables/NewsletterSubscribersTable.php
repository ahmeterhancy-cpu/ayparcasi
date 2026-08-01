<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-posta')->searchable()->copyable()->weight('bold'),
                TextColumn::make('name')->label('Ad')->searchable()->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Kayıt')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d M Y')),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ])
            ->emptyStateHeading('Henüz abone yok');
    }
}
