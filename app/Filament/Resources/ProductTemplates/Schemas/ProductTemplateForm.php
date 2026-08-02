<?php

namespace App\Filament\Resources\ProductTemplates\Schemas;

use App\Models\Addon;
use App\Models\Category;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([

                Group::make()->columnSpan(2)->schema([

                    Section::make('Şablon')
                        ->description('Bu şablondan açılan her ürün aşağıdaki alanlarla hazır gelir. Sonradan şablonu değiştirmek daha önce açılmış ürünlere dokunmaz.')
                        ->schema([
                            TextInput::make('name')
                                ->label('Şablon adı')
                                ->placeholder('Buket')
                                ->required()
                                ->maxLength(80),

                            Textarea::make('short_description')
                                ->label('Kısa açıklama')
                                ->rows(2)
                                ->maxLength(300),

                            Textarea::make('description')
                                ->label('Açıklama')
                                ->rows(6),

                            Textarea::make('contents')
                                ->label('İçindekiler')
                                ->helperText('Her satıra bir madde yazın.')
                                ->rows(4),

                            Textarea::make('care_notes')
                                ->label('Bakım önerisi')
                                ->helperText('Her satıra bir madde yazın.')
                                ->rows(4),
                        ]),

                    Section::make('Boy seçenekleri')
                        ->description('Bu şablondan açılan ürüne aynı boylar otomatik eklenir. Boş bırakırsanız ürün tek fiyatla satılır.')
                        ->schema([
                            Repeater::make('variants')
                                ->hiddenLabel()
                                ->addActionLabel('Boy ekle')
                                ->reorderable()
                                ->columns(4)
                                ->defaultItems(0)
                                ->itemLabel(fn (array $state) => $state['name'] ?? null)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Boy adı')
                                        ->placeholder('Orta')
                                        ->required()
                                        ->maxLength(60),

                                    TextInput::make('description')
                                        ->label('Açıklama')
                                        ->placeholder('15 dal')
                                        ->maxLength(120),

                                    TextInput::make('price')
                                        ->label('Fiyat')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required()
                                        ->suffix('TL'),

                                    TextInput::make('stock')
                                        ->label('Stok')
                                        ->numeric()
                                        ->default(0),

                                    Toggle::make('is_default')
                                        ->label('Varsayılan boy')
                                        ->helperText('Ürün sayfasında baştan seçili gelir.')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),

                Group::make()->columnSpan(1)->schema([

                    Section::make('Varsayılanlar')
                        ->schema([
                            TextInput::make('price')
                                ->label('Fiyat')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->suffix('TL')
                                ->helperText('Boy eklerseniz fiyat oradan gelir.'),

                            TextInput::make('badge')
                                ->label('Rozet')
                                ->placeholder('Yeni')
                                ->maxLength(40),

                            Toggle::make('same_day')
                                ->label('Aynı gün teslime uygun')
                                ->default(true),

                            Toggle::make('track_stock')
                                ->label('Stok takibi yapılsın')
                                ->default(true)
                                ->live(),

                            TextInput::make('stock')
                                ->label('Başlangıç stoğu')
                                ->numeric()
                                ->default(0)
                                ->visible(fn (Get $get) => (bool) $get('track_stock')),

                            TextInput::make('position')
                                ->label('Sıra')
                                ->numeric()
                                ->default(0)
                                ->helperText('Küçük sayı önce görünür.'),

                            Toggle::make('is_active')
                                ->label('Kullanımda')
                                ->default(true)
                                ->helperText('Kapatırsanız şablon listelerde çıkmaz.'),
                        ]),

                    Section::make('Kategoriler')
                        ->schema([
                            Select::make('category_ids')
                                ->hiddenLabel()
                                ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                                ->multiple()
                                ->preload()
                                ->searchable(),
                        ]),

                    Section::make('Ek ürünler')
                        ->schema([
                            CheckboxList::make('addon_ids')
                                ->hiddenLabel()
                                ->options(fn () => Addon::orderBy('position')->pluck('name', 'id'))
                                ->helperText('Bu şablondan açılan üründe hazır seçili gelir.'),
                        ]),
                ]),
            ]);
    }
}
