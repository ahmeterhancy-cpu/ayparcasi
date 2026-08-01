<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Services\OrderMailer;
use App\Services\OrderStock;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * Telefonla / WhatsApp'tan gelen siparişleri panelden açmak için.
 */
class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Elle sipariş aç';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['number'] = Order::nextNumber();

        // Bölge adını sipariş anındaki hâliyle sakla
        if (! empty($data['delivery_zone_id'])) {
            $data['delivery_zone_name'] = DeliveryZone::find($data['delivery_zone_id'])?->name;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Order $order */
        $order = $this->record;

        $order->recalculate();

        // Müşteri e-posta bıraktıysa sipariş özetini gönder
        defer(fn () => app(OrderMailer::class)->placed($order->fresh('items')));

        $short = app(OrderStock::class)->reserve($order);

        if ($short) {
            Notification::make()
                ->title('Stok yetmedi')
                ->body('Şu ürünlerin stoğu düşülemedi: '.implode(', ', $short).'. Stokları elle düzeltin.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sipariş oluşturuldu.';
    }
}
