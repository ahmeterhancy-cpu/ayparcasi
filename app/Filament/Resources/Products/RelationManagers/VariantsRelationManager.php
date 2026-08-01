<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Boy seçenekleri';

    protected static ?string $modelLabel = 'boy';

    protected static ?string $pluralModelLabel = 'boylar';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Boy adı')
                    ->placeholder('Küçük / Orta / Büyük')
                    ->required()
                    ->maxLength(60),

                TextInput::make('description')
                    ->label('Açıklama')
                    ->placeholder('9 dal')
                    ->maxLength(120),

                TextInput::make('price')
                    ->label('Fiyat')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->suffix('TL'),

                TextInput::make('compare_at_price')
                    ->label('Üstü çizili fiyat')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('TL'),

                TextInput::make('stock')
                    ->label('Stok adedi')
                    ->numeric()
                    ->default(0)
                    ->required(),

                TextInput::make('position')
                    ->label('Sıra')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_default')
                    ->label('Varsayılan seçili gelsin')
                    ->helperText('Yalnızca bir boy varsayılan olmalı.'),

                Toggle::make('is_active')
                    ->label('Satışta')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->label('Boy')
                    ->weight('bold')
                    ->description(fn ($record) => $record?->description),

                TextColumn::make('price')
                    ->label('Fiyat')
                    ->formatStateUsing(fn ($state) => money($state)),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),

                IconColumn::make('is_default')
                    ->label('Varsayılan')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Satışta')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Boy ekle'),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle'),
                DeleteAction::make()->label('Sil'),
            ])
            ->emptyStateHeading('Boy seçeneği yok')
            ->emptyStateDescription('Boy eklemezseniz ürün tek fiyatla satılır.');
    }
}
