<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label('Kupon kodu')
                    ->required()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->helperText('Müşteri sepette bu kodu yazar. Büyük/küçük harf farkı yok.')
                    ->extraInputAttributes(['style' => 'text-transform:uppercase']),

                Select::make('type')
                    ->label('İndirim tipi')
                    ->options(['percent' => 'Yüzde (%)', 'fixed' => 'Sabit tutar (TL)'])
                    ->default('percent')
                    ->required()
                    ->live()
                    ->native(false),

                TextInput::make('value')
                    ->label('İndirim miktarı')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->suffix(fn (Get $get) => $get('type') === 'percent' ? '%' : 'TL'),

                TextInput::make('min_total')
                    ->label('Alt sepet tutarı')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('TL')
                    ->helperText('Boş bırakırsanız sınır yok.'),

                Toggle::make('free_delivery')
                    ->label('Teslimat ücretsiz olsun'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),

                DateTimePicker::make('starts_at')
                    ->label('Başlangıç')
                    ->native(false)
                    ->displayFormat('d.m.Y H:i'),

                DateTimePicker::make('ends_at')
                    ->label('Bitiş')
                    ->native(false)
                    ->displayFormat('d.m.Y H:i')
                    ->after('starts_at'),

                TextInput::make('usage_limit')
                    ->label('Kullanım sınırı')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Boş bırakırsanız sınırsız.'),

                TextInput::make('used_count')
                    ->label('Kullanıldı')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
}
