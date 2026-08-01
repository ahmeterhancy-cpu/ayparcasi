<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function simpleProduct(): Product
    {
        return Product::active()
            ->whereDoesntHave('variants')
            ->where('track_stock', true)
            ->where('stock', '>', 5)
            ->firstOrFail();
    }

    private function fill(array $overrides = []): array
    {
        $zone = DeliveryZone::where('same_day', true)->firstOrFail();

        return array_merge([
            'customer_name' => 'Ahmet Erhan',
            'customer_phone' => '0533 111 22 33',
            'recipient_name' => 'Selin Yılmaz',
            'delivery_zone_id' => $zone->id,
            'delivery_address' => 'Karaoğlanoğlu Cad. No 12, Girne',
            'delivery_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
            'kvkk' => '1',
        ], $overrides);
    }

    public function test_sepete_ekleme_ve_toplam(): void
    {
        $product = $this->simpleProduct();

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->get('/sepet')
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_varyantli_urunde_secilen_boyun_fiyati_kullanilir(): void
    {
        $product = Product::active()->whereHas('variants')->with('variants')->firstOrFail();
        $variant = $product->variants->sortByDesc('price')->first();

        $this->post('/sepet', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect();

        $cart = app(Cart::class);

        $this->assertSame((float) $variant->price, $cart->subtotal());
    }

    public function test_kupon_alt_sinirin_altinda_uygulanmaz(): void
    {
        $coupon = Coupon::create([
            'code' => 'TEST50',
            'type' => 'fixed',
            'value' => 50,
            'min_total' => 99999,
            'is_active' => true,
        ]);

        $product = $this->simpleProduct();
        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/sepet/kupon', ['code' => $coupon->code])
            ->assertSessionHas('error');

        $this->assertSame(0.0, app(Cart::class)->discount());
    }

    public function test_siparis_olusur_ve_stok_duser(): void
    {
        $product = $this->simpleProduct();
        $before = $product->stock;

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 2]);
        $response = $this->post('/kasa', $this->fill());

        $order = Order::latest('id')->firstOrFail();

        $response->assertRedirect(route('order.show', $order->number));

        $this->assertSame(1, $order->items()->count());
        $this->assertSame($before - 2, $product->fresh()->stock);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertTrue((float) $order->total > 0);
    }

    public function test_gecmis_tarihe_siparis_verilemez(): void
    {
        $product = $this->simpleProduct();
        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/kasa', $this->fill(['delivery_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('delivery_date');

        $this->assertSame(0, Order::count());
    }

    public function test_ayni_gun_teslim_edilmeyen_bolgeye_bugun_secilemez(): void
    {
        $zone = DeliveryZone::where('same_day', false)->firstOrFail();
        $product = $this->simpleProduct();

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/kasa', $this->fill([
            'delivery_zone_id' => $zone->id,
            'delivery_date' => now()->toDateString(),
        ]))->assertSessionHasErrors('delivery_date');
    }

    public function test_stok_yetersizse_siparis_olusmaz(): void
    {
        $product = $this->simpleProduct();
        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 3]);

        $product->update(['stock' => 1]);

        $this->post('/kasa', $this->fill())->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_baskasinin_siparisi_goruntulenemez(): void
    {
        $product = $this->simpleProduct();
        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/kasa', $this->fill());

        $order = Order::latest('id')->firstOrFail();

        // Aynı oturumda görünür
        $this->get('/siparis/'.$order->number)->assertOk();

        // Oturum sıfırlanınca görünmez
        $this->flushSession();
        $this->get('/siparis/'.$order->number)->assertNotFound();
    }

    public function test_kart_ile_odeme_kapaliyken_secilemez(): void
    {
        config(['tiko.enabled' => false]);

        $product = $this->simpleProduct();
        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/kasa', $this->fill(['payment_method' => 'tiko']))
            ->assertSessionHasErrors('payment_method');
    }
}
