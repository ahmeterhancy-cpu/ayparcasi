<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Yazı')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Başlık')
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
                            ->unique(ignoreRecord: true),

                        Textarea::make('excerpt')
                            ->label('Özet')
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('Liste ve paylaşımlarda görünür.'),

                        Textarea::make('body')
                            ->label('İçerik')
                            ->rows(18)
                            ->helperText('Paragrafları boş satırla ayırın.'),
                    ]),

                Section::make('Yayın')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')->label('Yayında')->default(true),

                        DateTimePicker::make('published_at')
                            ->label('Yayın tarihi')
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->default(now())
                            ->helperText('İleri tarih verirseniz o tarihe kadar görünmez.'),

                        FileUpload::make('cover')
                            ->label('Kapak görseli')
                            ->image()
                            ->imageEditor()
                            ->directory('posts')
                            ->disk('public')
                            ->maxSize(6144),

                        TextInput::make('meta_title')->label('Meta başlık')->maxLength(190),
                        Textarea::make('meta_description')->label('Meta açıklama')->rows(2)->maxLength(300),
                    ]),
            ]);
    }
}
