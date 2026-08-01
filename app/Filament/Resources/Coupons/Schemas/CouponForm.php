<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
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
                    ->label('Toplam kullanım sınırı')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Boş bırakırsanız sınırsız.'),

                TextInput::make('per_user_limit')
                    ->label('Kişi başı kullanım sınırı')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Aynı hesap ya da aynı e-posta kaç kez kullanabilir.'),

                TextInput::make('used_count')
                    ->label('Kullanıldı')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),

                Section::make('Nerede geçerli')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('applies_to')
                            ->label('Kapsam')
                            ->options([
                                'all' => 'Tüm ürünler',
                                'products' => 'Yalnızca seçili ürünler',
                                'categories' => 'Yalnızca seçili kategoriler',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->native(false),

                        Toggle::make('exclude_sale_items')
                            ->label('İndirimli ürünlerde geçmesin')
                            ->helperText('Üstü çizili fiyatı olan ürünler indirim hesabına katılmaz.'),

                        Select::make('products')
                            ->label('Ürünler')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('applies_to') === 'products')
                            ->required(fn (Get $get) => $get('applies_to') === 'products'),

                        Select::make('categories')
                            ->label('Kategoriler')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('applies_to') === 'categories')
                            ->required(fn (Get $get) => $get('applies_to') === 'categories'),

                        Textarea::make('allowed_emails')
                            ->label('Yalnızca bu e-postalar')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('ayse@ornek.com, mehmet@ornek.com')
                            ->helperText('Boş bırakırsanız herkes kullanabilir. Virgülle ayırın.'),
                    ]),
            ]);
    }
}
