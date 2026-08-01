<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Ürün bilgileri')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Ürün adı')
                            ->required()
                            ->maxLength(190)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $context) {
                                if ($context === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Bağlantı adresi')
                            ->helperText('Boş bırakırsanız ürün adından üretilir.')
                            ->maxLength(190)
                            ->unique(ignoreRecord: true),

                        TextInput::make('sku')
                            ->label('Stok kodu')
                            ->maxLength(60),

                        Textarea::make('short_description')
                            ->label('Kısa açıklama')
                            ->helperText('Ürün başlığının hemen altında görünür. 1–2 cümle.')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(6)
                            ->columnSpanFull(),

                        Textarea::make('contents')
                            ->label('İçindekiler')
                            ->helperText('Her satıra bir madde yazın.')
                            ->rows(4),

                        Textarea::make('care_notes')
                            ->label('Bakım önerisi')
                            ->helperText('Her satıra bir madde yazın.')
                            ->rows(4),
                    ]),

                Section::make('Yayın')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Satışta')
                            ->default(true),

                        Toggle::make('is_featured')
                            ->label('Ana sayfada öne çıkar')
                            ->helperText('"Gözden kaçmayanlar" bölümünde görünür.'),

                        Toggle::make('same_day')
                            ->label('Aynı gün teslime uygun')
                            ->default(true),

                        TextInput::make('badge')
                            ->label('Rozet')
                            ->placeholder('Çok satan')
                            ->maxLength(40),

                        TextInput::make('position')
                            ->label('Sıra')
                            ->numeric()
                            ->default(0)
                            ->helperText('Küçük sayı önce görünür.'),

                        Select::make('categories')
                            ->label('Kategoriler')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required(),
                    ]),

                Section::make('Fiyat')
                    ->columnSpan(2)
                    ->columns(2)
                    ->description('Boy seçenekleri eklerseniz fiyat oradan alınır; buradaki fiyat yalnızca boy seçeneği olmayan ürünlerde kullanılır.')
                    ->schema([
                        TextInput::make('price')
                            ->label('Fiyat')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default(0)
                            ->suffix('TL'),

                        TextInput::make('compare_at_price')
                            ->label('Üstü çizili fiyat')
                            ->helperText('İndirim göstermek için. Fiyattan büyük olmalı.')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('TL'),
                    ]),

                Section::make('Stok')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('track_stock')
                            ->label('Stok takibi yapılsın')
                            ->helperText('Kapatırsanız durumu elle seçersiniz.')
                            ->default(true)
                            ->live(),

                        TextInput::make('stock')
                            ->label('Stok adedi')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Get $get) => (bool) $get('track_stock'))
                            ->helperText('Boy seçeneği olan ürünlerde adet boy başına tutulur.'),

                        Select::make('stock_status')
                            ->label('Stok durumu')
                            ->options([
                                'in_stock' => 'Stokta',
                                'low' => 'Son birkaç adet',
                                'made_to_order' => 'Siparişe özel hazırlanır',
                                'out_of_stock' => 'Tükendi',
                            ])
                            ->default('in_stock')
                            ->visible(fn (Get $get) => ! $get('track_stock')),
                    ]),

                Section::make('Görseller')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Kapak görseli')
                            ->image()
                            ->imageEditor()
                            ->directory('products')
                            ->disk('public')
                            ->maxSize(6144),

                        FileUpload::make('gallery')
                            ->label('Galeri')
                            ->helperText('İlk galeri görseli, listede fareyle üzerine gelince gösterilir.')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->directory('products')
                            ->disk('public')
                            ->maxSize(6144)
                            ->maxFiles(8),
                    ]),

                Section::make('Arama motoru')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta başlık')
                            ->maxLength(190),

                        Textarea::make('meta_description')
                            ->label('Meta açıklama')
                            ->rows(2)
                            ->maxLength(300),
                    ]),
            ]);
    }
}
