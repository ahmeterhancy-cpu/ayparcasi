<?php

namespace App\Filament\Resources\Addons\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AddonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Ek ürün adı')
                    ->placeholder('Belçika çikolatası')
                    ->required()
                    ->maxLength(120),

                TextInput::make('price')
                    ->label('Fiyat')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->suffix('TL'),

                TextInput::make('description')
                    ->label('Açıklama')
                    ->placeholder('Küçük kutu, 12 parça')
                    ->maxLength(190)
                    ->columnSpanFull(),

                FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->directory('addons')
                    ->disk('public'),

                TextInput::make('position')->label('Sıra')->numeric()->default(0),

                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
