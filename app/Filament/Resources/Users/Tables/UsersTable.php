<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ad soyad')->searchable()->weight('bold'),
                TextColumn::make('email')->label('E-posta')->searchable()->copyable(),

                TextColumn::make('role')
                    ->label('Yetki')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'admin' ? 'Yönetici' : 'Personel')
                    ->color(fn ($state) => $state === 'admin' ? 'danger' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Eklendi')
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d M Y'))
                    ->sortable(),
            ])
            ->recordActions([EditAction::make()->label('Düzenle')])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()->label('Seçilenleri sil')]),
            ]);
    }
}
