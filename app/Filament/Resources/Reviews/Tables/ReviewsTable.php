<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Ürün')
                    ->searchable()
                    ->weight('bold')
                    ->limit(32),

                TextColumn::make('name')
                    ->label('Yazan')
                    ->searchable()
                    ->description(fn (Review $r) => $r->created_at?->translatedFormat('d M Y, H:i')),

                TextColumn::make('rating')
                    ->label('Puan')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_repeat('★', (int) $state))
                    ->color('warning'),

                TextColumn::make('body')
                    ->label('Yorum')
                    ->limit(60)
                    ->tooltip(fn (Review $r) => $r->body)
                    ->description(fn (Review $r) => $r->title),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Review::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('reply')
                    ->label('Cevap')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Var' : '—')
                    ->color(fn ($state) => filled($state) ? 'info' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Review::STATUSES)
                    ->default('pending'),

                SelectFilter::make('rating')
                    ->label('Puan')
                    ->options([5 => '5 yıldız', 4 => '4 yıldız', 3 => '3 yıldız', 2 => '2 yıldız', 1 => '1 yıldız'])
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('onayla')
                    ->label('Yayınla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Review $r) => $r->status !== 'approved')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'approved']);

                        Notification::make()->title('Yorum yayınlandı.')->success()->send();
                    }),

                Action::make('reddet')
                    ->label('Yayından kaldır')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $r) => $r->status !== 'rejected')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'rejected']);

                        Notification::make()->title('Yorum yayından kaldırıldı.')->success()->send();
                    }),

                EditAction::make()->label('Aç'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('toplu_onay')
                        ->label('Seçilenleri yayınla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            // Tek tek kaydedilir; ürün puan ortalaması model
                            // olayında güncellensin diye toplu update yapılmaz.
                            $records->each(fn (Review $r) => $r->update(['status' => 'approved']));

                            Notification::make()->title($records->count().' yorum yayınlandı.')->success()->send();
                        }),

                    DeleteBulkAction::make()->label('Seçilenleri sil'),
                ]),
            ])
            ->emptyStateHeading('Henüz yorum yok')
            ->emptyStateDescription('Yorumlar yalnızca ürünü teslim almış müşterilerden gelir.');
    }
}
