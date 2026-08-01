<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Müşteri adı')->required()->maxLength(120),
                TextInput::make('city')->label('Şehir')->maxLength(120),

                Select::make('rating')
                    ->label('Puan')
                    ->options([5 => '5 yıldız', 4 => '4 yıldız', 3 => '3 yıldız', 2 => '2 yıldız', 1 => '1 yıldız'])
                    ->default(5)
                    ->required()
                    ->native(false),

                TextInput::make('position')->label('Sıra')->numeric()->default(0),

                Textarea::make('body')
                    ->label('Yorum')
                    ->required()
                    ->rows(4)
                    ->maxLength(400)
                    ->columnSpanFull()
                    ->helperText('Ana sayfada büyük puntoyla gösterilir; kısa tutun.'),

                Toggle::make('is_active')->label('Yayında')->default(true),
            ]);
    }
}
