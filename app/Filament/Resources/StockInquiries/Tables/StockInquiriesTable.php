<?php

namespace App\Filament\Resources\StockInquiries\Tables;

use App\Models\StockInquiry;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class StockInquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Ne zaman')
                    ->since()
                    ->sortable()
                    ->description(fn ($r) => $r?->created_at?->translatedFormat('d M Y, H:i')),

                TextColumn::make('product_name')
                    ->label('Ürün')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($r) => $r?->variant_name),

                TextColumn::make('source')
                    ->label('Nereden')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'listing' ? 'Liste' : 'Ürün sayfası'),

                IconColumn::make('handled')
                    ->label('İlgilenildi')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('handled')
                    ->label('İlgilenildi mi')
                    ->placeholder('Hepsi')
                    ->trueLabel('İlgilenilenler')
                    ->falseLabel('Bekleyenler'),
            ])
            ->recordActions([
                Action::make('isaretle')
                    ->label(fn (StockInquiry $r) => $r->handled ? 'Geri al' : 'İlgilenildi')
                    ->icon('heroicon-o-check')
                    ->action(fn (StockInquiry $record) => $record->update(['handled' => ! $record->handled])),

                Action::make('urun')
                    ->label('Ürüne git')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (StockInquiry $r) => $r->product !== null)
                    ->url(fn (StockInquiry $r) => $r->product?->url)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('hepsi_ilgilenildi')
                        ->label('İlgilenildi olarak işaretle')
                        ->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['handled' => true]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ])
            ->emptyStateHeading('Henüz stok sorusu yok')
            ->emptyStateDescription('Müşteriler ürün sayfasındaki WhatsApp düğmesine bastığında burada listelenir.');
    }
}
