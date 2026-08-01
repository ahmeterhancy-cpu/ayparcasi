<?php

namespace App\Filament\Resources\DeliveryZones\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeliveryZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Bölge adı')
                    ->required()
                    ->maxLength(120)
                    ->helperText('Girne, Lefkoşa, Mağusa… Ana sayfadaki rotada da bu ad görünür.'),

                TextInput::make('fee')
                    ->label('Teslimat ücreti')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->suffix('TL'),

                TextInput::make('free_over')
                    ->label('Şu tutarın üstü ücretsiz')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('TL')
                    ->helperText('Boş bırakırsanız her zaman ücretlidir.'),

                TextInput::make('position')
                    ->label('Sıra')
                    ->numeric()
                    ->default(0),

                TextInput::make('note')
                    ->label('Not')
                    ->maxLength(190)
                    ->columnSpanFull()
                    ->helperText('Teslimat sayfasında bölge kartının altında görünür.'),

                Toggle::make('same_day')
                    ->label('Aynı gün teslimat yapılabilir')
                    ->default(true),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
