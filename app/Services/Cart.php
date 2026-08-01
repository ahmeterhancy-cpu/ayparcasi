<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Oturum tabanlı sepet. Fiyatlar her okumada veritabanından tazelenir —
 * oturumda yalnızca kimlikler ve adet tutulur, böylece fiyat oynanamaz.
 */
class Cart
{
    private const KEY = 'cart';

    private const COUPON_KEY = 'cart_coupon';

    public function __construct(private readonly Session $session) {}

    /** @return array<string, array{product_id:int, variant_id:?int, quantity:int, addons:array<int>}> */
    public function raw(): array
    {
        return $this->session->get(self::KEY, []);
    }

    public function lineKey(int $productId, ?int $variantId, array $addonIds): string
    {
        sort($addonIds);

        return md5($productId.'|'.($variantId ?? 0).'|'.implode(',', $addonIds));
    }

    public function add(Product $product, ?ProductVariant $variant, int $quantity = 1, array $addonIds = []): string
    {
        $quantity = max(1, min(99, $quantity));
        $addonIds = array_values(array_unique(array_map('intval', $addonIds)));
        $key = $this->lineKey($product->id, $variant?->id, $addonIds);

        $cart = $this->raw();

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = min(99, $cart[$key]['quantity'] + $quantity);
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'addons' => $addonIds,
            ];
        }

        $this->session->put(self::KEY, $cart);

        return $key;
    }

    public function update(string $key, int $quantity): void
    {
        $cart = $this->raw();

        if (! isset($cart[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key]['quantity'] = min(99, $quantity);
        }

        $this->session->put(self::KEY, $cart);
    }

    public function remove(string $key): void
    {
        $cart = $this->raw();
        unset($cart[$key]);
        $this->session->put(self::KEY, $cart);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
        $this->session->forget(self::COUPON_KEY);
    }

    /**
     * Sepet satırlarını modelleriyle birlikte döndürür.
     * Silinmiş/pasifleşmiş ürünler sessizce düşer.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function lines(): Collection
    {
        $raw = $this->raw();

        if (empty($raw)) {
            return collect();
        }

        $products = Product::query()
            ->with('variants')
            ->whereIn('id', collect($raw)->pluck('product_id')->unique())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $addonIds = collect($raw)->pluck('addons')->flatten()->unique()->all();
        $addons = $addonIds ? Addon::whereIn('id', $addonIds)->get()->keyBy('id') : collect();

        $lines = collect();
        $dirty = false;

        foreach ($raw as $key => $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                $dirty = true;

                continue;
            }

            $variant = $item['variant_id']
                ? $product->variants->firstWhere('id', $item['variant_id'])
                : null;

            if ($item['variant_id'] && ! $variant) {
                $dirty = true;

                continue;
            }

            // İndirim penceresi dışındaysa normal fiyattan tahsil edilir
            $unit = $variant ? $variant->effectivePriceFor($product) : $product->effective_price;

            $lineAddons = collect($item['addons'] ?? [])
                ->map(fn ($id) => $addons->get($id))
                ->filter()
                ->map(fn (Addon $a) => ['id' => $a->id, 'name' => $a->name, 'price' => (float) $a->price])
                ->values();

            $unitWithAddons = $unit + $lineAddons->sum('price');

            $lines->push([
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'addons' => $lineAddons,
                'quantity' => $item['quantity'],
                'unit_price' => $unitWithAddons,
                'line_total' => round($unitWithAddons * $item['quantity'], 2),
            ]);
        }

        if ($dirty) {
            $this->session->put(self::KEY, $lines->mapWithKeys(fn ($l) => [$l['key'] => [
                'product_id' => $l['product']->id,
                'variant_id' => $l['variant']?->id,
                'quantity' => $l['quantity'],
                'addons' => $l['addons']->pluck('id')->all(),
            ]])->all());
        }

        return $lines;
    }

    public function count(): int
    {
        return (int) collect($this->raw())->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return empty($this->raw());
    }

    public function subtotal(): float
    {
        return round($this->lines()->sum('line_total'), 2);
    }

    // --- Kupon ------------------------------------------------------------

    public function applyCoupon(string $code): ?string
    {
        $coupon = Coupon::with('products', 'categories')
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if (! $coupon) {
            return 'Böyle bir kupon bulamadık.';
        }

        if ($error = $this->couponError($coupon)) {
            return $error;
        }

        $this->session->put(self::COUPON_KEY, $coupon->id);

        return null;
    }

    /** Kuponu güncel sepete ve müşteriye göre doğrular. */
    private function couponError(Coupon $coupon): ?string
    {
        return $coupon->validationError(
            $this->subtotal(),
            $coupon->eligibleSubtotal($this->lines()),
            auth()->id(),
            auth()->user()?->email,
        );
    }

    public function removeCoupon(): void
    {
        $this->session->forget(self::COUPON_KEY);
    }

    public function coupon(): ?Coupon
    {
        $id = $this->session->get(self::COUPON_KEY);

        if (! $id) {
            return null;
        }

        $coupon = Coupon::with('products', 'categories')->find($id);

        // Sepet değiştiyse kupon geçersizleşmiş olabilir — sessizce düşür
        if (! $coupon || $this->couponError($coupon)) {
            $this->removeCoupon();

            return null;
        }

        return $coupon;
    }

    public function discount(): float
    {
        $coupon = $this->coupon();

        if (! $coupon) {
            return 0.0;
        }

        return $coupon->discountFor($coupon->eligibleSubtotal($this->lines()));
    }

    public function deliveryFee(?DeliveryZone $zone): float
    {
        if (! $zone) {
            return 0.0;
        }

        if ($this->coupon()?->free_delivery) {
            return 0.0;
        }

        return $zone->feeFor($this->subtotal());
    }

    public function total(?DeliveryZone $zone = null): float
    {
        return round(max(0, $this->subtotal() - $this->discount()) + $this->deliveryFee($zone), 2);
    }

    /** Vitrinde gösterilecek özet. */
    public function summary(?DeliveryZone $zone = null): array
    {
        return [
            'subtotal' => $this->subtotal(),
            'discount' => $this->discount(),
            'delivery_fee' => $this->deliveryFee($zone),
            'total' => $this->total($zone),
            'coupon' => $this->coupon(),
        ];
    }
}
