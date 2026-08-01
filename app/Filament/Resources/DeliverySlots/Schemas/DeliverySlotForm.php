<?php

namespace App\Filament\Resources\DeliverySlots\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DeliverySlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('label')
                    ->label('Görünen metin')
                    ->placeholder('09:00 – 12:00')
                    ->required()
                    ->maxLength(60)
                    ->columnSpanFull()
                    ->helperText('Kasada müşteriye bu metin gösterilir.'),

                TimePicker::make('starts_at')->label('Başlangıç')->seconds(false),
                TimePicker::make('ends_at')->label('Bitiş')->seconds(false),

                TextInput::make('position')->label('Sıra')->numeric()->default(0),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
