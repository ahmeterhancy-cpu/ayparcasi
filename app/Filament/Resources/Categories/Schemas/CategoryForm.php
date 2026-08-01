<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Kategori')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Kategori adı')
                            ->required()
                            ->maxLength(190)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $context) {
                                if ($context === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Bağlantı adresi')
                            ->maxLength(190)
                            ->unique(ignoreRecord: true)
                            ->helperText('Boş bırakırsanız addan üretilir.'),

                        Select::make('parent_id')
                            ->label('Üst kategori')
                            ->options(fn ($record) => Category::query()
                                ->whereNull('parent_id')
                                ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                                ->orderBy('position')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Ana kategori'),

                        TextInput::make('position')
                            ->label('Sıra')
                            ->numeric()
                            ->default(0),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->helperText('Ana sayfadaki panelde ve kategori sayfasının başında görünür.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Görünüm')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Görünsün')
                            ->default(true),

                        Toggle::make('is_featured')
                            ->label('Ana sayfada panel olarak göster')
                            ->helperText('"Çiçeği sebebiyle seçin" bölümünde çıkar; en fazla 6 tanesi gösterilir.'),

                        FileUpload::make('image')
                            ->label('Kategori görseli')
                            ->image()
                            ->imageEditor()
                            ->directory('categories')
                            ->disk('public')
                            ->maxSize(6144),
                    ]),

                Section::make('Arama motoru')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('meta_title')->label('Meta başlık')->maxLength(190),
                        Textarea::make('meta_description')->label('Meta açıklama')->rows(2)->maxLength(300),
                    ]),
            ]);
    }
}
