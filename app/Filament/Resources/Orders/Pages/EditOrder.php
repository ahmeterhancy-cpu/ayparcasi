<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderStock;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Sipariş '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('fis')
                    ->label('Sipariş fişi')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Order $record) => route('print.receipt', $record->number))
                    ->openUrlInNewTab(),

                Action::make('teslim_fisi')
                    ->label('Kurye teslim fişi')
                    ->icon('heroicon-o-truck')
                    ->url(fn (Order $record) => route('print.slip', $record->number))
                    ->openUrlInNewTab(),
            ])
                ->label('Yazdır')
                ->icon('heroicon-o-printer')
                ->button(),

            Action::make('iade')
                ->label('İade et')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn (Order $record) => $record->refundable > 0)
                ->schema(fn (Order $record) => [
                    TextInput::make('amount')
                        ->label('İade tutarı')
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue($record->refundable)
                        ->default($record->refundable)
                        ->required()
                        ->suffix('TL')
                        ->helperText('İade edilebilir kalan: '.money($record->refundable)),

                    Textarea::make('reason')
                        ->label('Sebep')
                        ->rows(2)
                        ->maxLength(190)
                        ->placeholder('Müşteri vazgeçti, ürün beğenilmedi…'),

                    Toggle::make('restocked')
                        ->label('Ürünleri stoğa geri ekle')
                        ->default(fn () => $record->stock_reserved)
                        ->disabled(fn () => ! $record->stock_reserved)
                        ->helperText($record->stock_reserved
                            ? 'Kapatırsanız stok değişmez.'
                            : 'Bu siparişin stoğu zaten geri yüklenmiş.'),
                ])
                ->action(function (Order $record, array $data) {
                    $amount = min((float) $data['amount'], $record->refundable);

                    if ($amount <= 0) {
                        Notification::make()->title('İade edilecek tutar kalmadı.')->warning()->send();

                        return;
                    }

                    $record->refunds()->create([
                        'user_id' => Auth::id(),
                        'amount' => $amount,
                        'reason' => $data['reason'] ?? null,
                        'restocked' => (bool) ($data['restocked'] ?? false),
                    ]);

                    $record->forceFill([
                        'refunded_total' => round((float) $record->refunded_total + $amount, 2),
                    ])->save();

                    if ($record->fresh()->is_fully_refunded) {
                        $record->update(['payment_status' => 'refunded']);
                    }

                    if (! empty($data['restocked'])) {
                        app(OrderStock::class)->restore($record->load('items'));
                    }

                    Notification::make()
                        ->title(money($amount).' iade kaydedildi.')
                        ->body('Parayı bankadan/Tiko panelinden ayrıca iade etmeyi unutmayın.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['refunded_total', 'payment_status']);
                }),

            Action::make('whatsapp_musteri')
                ->label('Müşteriye yaz')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn (Order $record) => wa_link(
                    "Merhaba {$record->customer_name}, {$record->number} numaralı siparişiniz hakkında yazıyoruz.",
                    $record->customer_phone
                ))
                ->openUrlInNewTab(),

            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function afterSave(): void
    {
        // Kalemler değişmiş olabilir — tutarları tazele
        $this->record->recalculate();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Sipariş güncellendi.';
    }
}
