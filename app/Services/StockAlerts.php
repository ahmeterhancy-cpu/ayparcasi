<?php

namespace App\Services;

use App\Mail\LowStockAlert;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Stok eşiğin altına düştüğünde ekibe e-posta.
 * Aynı ürün için günde en fazla bir kez yazılır.
 */
class StockAlerts
{
    public function threshold(): int
    {
        return max(0, (int) setting('low_stock_threshold', 3));
    }

    public function recipient(): ?string
    {
        $to = setting('low_stock_email') ?: setting('email');

        return filter_var((string) $to, FILTER_VALIDATE_EMAIL) ? (string) $to : null;
    }

    /** Siparişteki ürünleri eşiğe göre kontrol et. */
    public function checkOrder(Order $order): void
    {
        $ids = $order->items->pluck('product_id')->filter()->unique();

        if ($ids->isEmpty()) {
            return;
        }

        Product::whereIn('id', $ids)->where('track_stock', true)->with('variants')->get()
            ->each(fn (Product $product) => $this->check($product));
    }

    public function check(Product $product): void
    {
        if (! $product->track_stock) {
            return;
        }

        $threshold = $this->threshold();
        $remaining = $product->has_variants
            ? (int) $product->variants()->where('is_active', true)->sum('stock')
            : (int) $product->stock;

        if ($remaining > $threshold) {
            return;
        }

        $to = $this->recipient();

        if (! $to) {
            return;
        }

        // Günde bir kez — sipariş yağmurunda posta kutusu dolmasın
        $key = 'low-stock:'.$product->id.':'.now()->toDateString();

        if (! Cache::add($key, true, now()->endOfDay())) {
            return;
        }

        try {
            Mail::to($to)->send(new LowStockAlert($product, $remaining, $threshold));
        } catch (\Throwable $e) {
            // Posta gönderilemedi diye sipariş akışı bozulmasın
            Log::warning('Düşük stok e-postası gönderilemedi', [
                'product' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
