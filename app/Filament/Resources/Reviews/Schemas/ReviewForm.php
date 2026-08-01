<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Models\Review;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Müşterinin yazdıkları değiştirilemez — yalnız okunur.
                TextInput::make('name')->label('Yazan')->disabled(),

                TextInput::make('rating')
                    ->label('Puan')
                    ->disabled()
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state).' ('.$state.'/5)'),

                TextInput::make('title')->label('Başlık')->disabled()->columnSpanFull(),

                Textarea::make('body')
                    ->label('Yorum')
                    ->disabled()
                    ->rows(5)
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Durum')
                    ->options(Review::STATUSES)
                    ->required()
                    ->native(false)
                    ->helperText('Yalnızca "Yayında" olanlar ürün sayfasında ve puan ortalamasında görünür.'),

                TextInput::make('order.number')
                    ->label('Yorum hakkını veren sipariş')
                    ->disabled()
                    ->formatStateUsing(fn ($record) => $record?->order?->number ?? 'Sipariş silinmiş'),

                Textarea::make('reply')
                    ->label('Dükkân cevabı')
                    ->rows(3)
                    ->maxLength(600)
                    ->columnSpanFull()
                    ->helperText('Doldurursanız yorumun altında Ay Parçası imzasıyla gösterilir.'),
            ]);
    }
}
