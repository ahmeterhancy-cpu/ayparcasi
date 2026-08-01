<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
            Action::make('whatsapp_musteri')
                ->label('Müşteriye yaz')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn (Order $record) => wa_link(
                    "Merhaba {$record->customer_name}, {$record->number} numaralı siparişiniz hakkında yazıyoruz.",
                    $record->customer_phone
                ))
                ->openUrlInNewTab(),

            Action::make('whatsapp_alici')
                ->label('Alıcıya yaz')
                ->icon('heroicon-o-phone')
                ->color('gray')
                ->visible(fn (Order $record) => filled($record->recipient_phone))
                ->url(fn (Order $record) => wa_link(
                    "Merhaba {$record->recipient_name}, size bir çiçek teslimatımız var.",
                    $record->recipient_phone
                ))
                ->openUrlInNewTab(),

            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Sipariş güncellendi.';
    }
}
