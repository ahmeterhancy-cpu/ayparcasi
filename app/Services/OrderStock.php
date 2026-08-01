<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Sipariş kalemlerinin stok hareketleri.
 *
 * `orders.stock_reserved` bayrağı sayesinde aynı sipariş için stok
 * iki kez düşmez ve iki kez geri yüklenmez.
 */
class OrderStock
{
    public function __construct(private readonly StockAlerts $alerts) {}

    /**
     * Sipariş kalemleri kadar stoğu düş.
     *
     * @return array<string> Yetersiz stok nedeniyle düşülemeyen ürün adları
     */
    public function reserve(Order $order): array
    {
        if ($order->stock_reserved) {
            return [];
        }

        $short = [];

        DB::transaction(function () use ($order, &$short) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                /** @var Product|null $product */
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product || ! $product->track_stock) {
                    continue;
                }

                $variant = $item->variant_name
                    ? $product->variants()->where('name', $item->variant_name)->lockForUpdate()->first()
                    : null;

                $holder = $variant ?: $product;

                if ($holder->stock < $item->quantity) {
                    $short[] = $item->name;

                    continue;
                }

                $holder->decrement('stock', $item->quantity);
            }

            $order->update(['stock_reserved' => true]);
        });

        $this->alerts->checkOrder($order->fresh('items'));

        return $short;
    }

    /**
     * Düşülen stoğu geri ekle (iptal, iade).
     * Rezerve edilmemiş siparişte hiçbir şey yapmaz.
     */
    public function restore(Order $order): void
    {
        if (! $order->stock_reserved) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product || ! $product->track_stock) {
                    continue;
                }

                $variant = $item->variant_name
                    ? $product->variants()->where('name', $item->variant_name)->lockForUpdate()->first()
                    : null;

                ($variant ?: $product)->increment('stock', $item->quantity);
            }

            $order->update(['stock_reserved' => false]);
        });
    }
}
