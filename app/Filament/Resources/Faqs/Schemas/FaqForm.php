<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('question')
                    ->label('Soru')
                    ->required()
                    ->maxLength(190)
                    ->columnSpanFull(),

                Textarea::make('answer')
                    ->label('Cevap')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull()
                    ->helperText('Satır sonları korunur.'),

                TextInput::make('position')->label('Sıra')->numeric()->default(0),
                Toggle::make('is_active')->label('Yayında')->default(true),
            ]);
    }
}
