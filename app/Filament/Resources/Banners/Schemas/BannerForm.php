<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('placement')
                    ->label('Nerede görünecek')
                    ->options([
                        'strip' => 'Üst duyuru şeridi (sitenin en üstü)',
                        'promo' => 'Ara kampanya bloğu',
                        'hero' => 'Hero alanı',
                    ])
                    ->default('strip')
                    ->required()
                    ->live()
                    ->native(false)
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->label('Başlık / duyuru metni')
                    ->required()
                    ->maxLength(190)
                    ->columnSpanFull(),

                TextInput::make('eyebrow')
                    ->label('Üst etiket')
                    ->maxLength(60)
                    ->visible(fn (Get $get) => $get('placement') !== 'strip'),

                TextInput::make('cta_label')
                    ->label('Buton metni')
                    ->placeholder('Bölgeleri gör')
                    ->maxLength(60),

                TextInput::make('link')
                    ->label('Bağlantı')
                    ->placeholder('/teslimat')
                    ->maxLength(190)
                    ->columnSpanFull(),

                Textarea::make('subtitle')
                    ->label('Alt metin')
                    ->rows(2)
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('placement') !== 'strip'),

                FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->directory('banners')
                    ->disk('public')
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('placement') !== 'strip'),

                DateTimePicker::make('starts_at')
                    ->label('Yayın başlangıcı')
                    ->native(false)
                    ->displayFormat('d.m.Y H:i')
                    ->helperText('Boş bırakırsanız hemen yayında.'),

                DateTimePicker::make('ends_at')
                    ->label('Yayın bitişi')
                    ->native(false)
                    ->displayFormat('d.m.Y H:i')
                    ->after('starts_at'),

                TextInput::make('position')->label('Sıra')->numeric()->default(0),

                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
