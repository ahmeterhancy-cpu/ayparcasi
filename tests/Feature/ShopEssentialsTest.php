<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart;
use App\Services\OrderStock;
use App\Services\ProductCsv;
use App\Services\SalesReport;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopEssentialsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function simpleProduct(): Product
    {
        return Product::active()
            ->whereDoesntHave('variants')
            ->where('track_stock', true)
            ->where('stock', '>', 5)
            ->firstOrFail();
    }

    private function makeOrder(Product $product, int $qty = 2, array $attrs = []): Order
    {
        $order = Order::create(array_merge([
            'number' => Order::nextNumber(),
            'customer_name' => 'Test Müşteri',
            'customer_phone' => '0533 111 22 33',
            'recipient_name' => 'Alıcı',
            'total' => $product->price * $qty,
            'subtotal' => $product->price * $qty,
        ], $attrs));

        $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => $qty,
            'line_total' => $product->price * $qty,
        ]);

        return $order->load('items');
    }

    // --- Stok hareketleri -------------------------------------------------

    public function test_stok_rezerve_edilir_ve_iki_kez_dusmez(): void
    {
        $product = $this->simpleProduct();
        $before = $product->stock;
        $order = $this->makeOrder($product, 2);

        app(OrderStock::class)->reserve($order);
        $this->assertSame($before - 2, $product->fresh()->stock);

        // Aynı sipariş için tekrar çağrılırsa stok bir daha düşmemeli
        app(OrderStock::class)->reserve($order->fresh('items'));
        $this->assertSame($before - 2, $product->fresh()->stock);
    }

    public function test_siparis_iptal_edilince_stok_geri_gelir(): void
    {
        $product = $this->simpleProduct();
        $before = $product->stock;
        $order = $this->makeOrder($product, 3);

        app(OrderStock::class)->reserve($order);
        $this->assertSame($before - 3, $product->fresh()->stock);

        $order->update(['status' => 'cancelled']);

        $this->assertSame($before, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
    }

    public function test_kasadan_verilen_siparis_iptal_edilince_de_stok_geri_gelir(): void
    {
        $product = $this->simpleProduct();
        $before = $product->stock;
        $zone = DeliveryZone::where('same_day', true)->firstOrFail();

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 2]);
        $this->post('/kasa', [
            'customer_name' => 'Ahmet', 'customer_phone' => '0533',
            'recipient_name' => 'Alıcı', 'delivery_zone_id' => $zone->id,
            'delivery_address' => 'Adres', 'delivery_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash', 'kvkk' => '1',
        ]);

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame($before - 2, $product->fresh()->stock);

        $order->update(['status' => 'cancelled']);
        $this->assertSame($before, $product->fresh()->stock);
    }

    // --- İade -------------------------------------------------------------

    public function test_kismi_ve_tam_iade(): void
    {
        $product = $this->simpleProduct();
        $order = $this->makeOrder($product, 1, ['payment_status' => 'paid']);
        $total = (float) $order->total;

        $order->refunds()->create(['amount' => 10, 'reason' => 'Kısmi']);
        $order->forceFill(['refunded_total' => 10])->save();

        $this->assertFalse($order->fresh()->is_fully_refunded);
        $this->assertEqualsWithDelta($total - 10, $order->fresh()->refundable, 0.01);

        $order->forceFill(['refunded_total' => $total])->save();
        $this->assertTrue($order->fresh()->is_fully_refunded);
        $this->assertSame(0.0, $order->fresh()->refundable);
    }

    // --- Yazdırma ---------------------------------------------------------

    public function test_yazdirma_sayfalari_yalniz_ekibe_acik(): void
    {
        $product = $this->simpleProduct();
        $order = $this->makeOrder($product, 1, ['delivery_date' => today()]);

        // Girişsiz kullanıcı giriş sayfasına yollanır
        $this->get("/yazdir/siparis/{$order->number}/fis")->assertRedirect(route('login'));

        foreach (["/yazdir/siparis/{$order->number}/fis", "/yazdir/siparis/{$order->number}/teslim", '/yazdir/gunun-teslimatlari'] as $path) {
            $this->actingAs($this->admin)->get($path)->assertOk();
        }

        // Müşteri girişi paneli belgelerine erişemez
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get("/yazdir/siparis/{$order->number}/fis")->assertForbidden();
    }

    public function test_teslim_fisinde_kart_notu_ve_adres_var(): void
    {
        $product = $this->simpleProduct();
        $order = $this->makeOrder($product, 1, [
            'card_message' => 'İyi ki doğdun',
            'delivery_address' => 'Karaoğlanoğlu Cad. No 12',
        ]);

        $this->actingAs($this->admin)
            ->get("/yazdir/siparis/{$order->number}/teslim")
            ->assertOk()
            ->assertSee('İyi ki doğdun')
            ->assertSee('Karaoğlanoğlu Cad. No 12');
    }

    // --- Raporlar ---------------------------------------------------------

    public function test_rapor_toplamlari_iptalleri_haric_tutar(): void
    {
        $product = $this->simpleProduct();
        $this->makeOrder($product, 1)->update(['total' => 100]);
        $this->makeOrder($product, 1)->update(['total' => 200, 'status' => 'cancelled']);

        $report = new SalesReport(now()->startOfDay(), now()->endOfDay());
        $totals = $report->totals();

        $this->assertSame(1, $totals['orders']);
        $this->assertEqualsWithDelta(100, $totals['revenue'], 0.01);
    }

    public function test_rapor_sayfasi_ve_csv(): void
    {
        $this->actingAs($this->admin)->get('/admin/reports')->assertOk();
    }

    // --- Ürün CSV ---------------------------------------------------------

    public function test_urun_csv_disa_ve_ice_aktarma(): void
    {
        $csv = app(ProductCsv::class);

        $response = $csv->export();
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('ad;baglanti', $content);
        $this->assertStringContainsString(Product::first()->name, $content);

        // Dışa aktardığımızı geri yükleyince ürün sayısı değişmemeli
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);

        $before = Product::count();
        $result = $csv->import($path);

        $this->assertSame(0, $result['created']);
        $this->assertSame($before, $result['updated']);
        $this->assertSame($before, Product::count());

        unlink($path);
    }

    public function test_csv_ile_yeni_urun_eklenir_ve_fiyat_guncellenir(): void
    {
        $existing = $this->simpleProduct();
        $category = Category::first();

        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, implode("\n", [
            'ad;baglanti;fiyat;kategoriler;stok',
            "Yeni Deneme Buketi;;1.234,50;{$category->name};7",
            "{$existing->name};{$existing->slug};999;;",
        ]));

        $result = app(ProductCsv::class)->import($path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);

        $new = Product::where('name', 'Yeni Deneme Buketi')->firstOrFail();
        $this->assertEqualsWithDelta(1234.50, (float) $new->price, 0.01);
        $this->assertSame(7, $new->stock);
        $this->assertTrue($new->categories->contains('id', $category->id));

        $this->assertEqualsWithDelta(999, (float) $existing->fresh()->price, 0.01);

        unlink($path);
    }

    // --- İndirim tarihi ---------------------------------------------------

    public function test_indirim_penceresi_disinda_eski_fiyattan_satilir(): void
    {
        $product = $this->simpleProduct();
        $product->update([
            'price' => 100,
            'compare_at_price' => 150,
            'sale_starts_at' => now()->addWeek(),
            'sale_ends_at' => now()->addWeeks(2),
        ]);

        $product->refresh();

        $this->assertFalse($product->sale_active);
        $this->assertEqualsWithDelta(150, $product->effective_price, 0.01);
        $this->assertNull($product->display_compare_at);
        $this->assertNull($product->discount_percent);

        // Sepet de indirimsiz fiyatı almalı
        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);
        $this->assertEqualsWithDelta(150, app(Cart::class)->subtotal(), 0.01);
    }

    public function test_indirim_penceresi_icinde_indirimli_fiyat_gecerli(): void
    {
        $product = $this->simpleProduct();
        $product->update([
            'price' => 100,
            'compare_at_price' => 150,
            'sale_starts_at' => now()->subDay(),
            'sale_ends_at' => now()->addDay(),
        ]);

        $product->refresh();

        $this->assertTrue($product->sale_active);
        $this->assertEqualsWithDelta(100, $product->effective_price, 0.01);
        $this->assertSame(33, $product->discount_percent);
    }

    // --- Kupon kısıtları --------------------------------------------------

    public function test_kupon_yalniz_secili_urunlerde_gecer(): void
    {
        $allowed = $this->simpleProduct();
        $other = Product::active()->whereDoesntHave('variants')->whereKeyNot($allowed->id)->firstOrFail();

        $coupon = Coupon::create([
            'code' => 'SADECEBU', 'type' => 'percent', 'value' => 50,
            'applies_to' => 'products', 'is_active' => true,
        ]);
        $coupon->products()->attach($allowed->id);

        $cart = app(Cart::class);

        // Yalnızca kapsam dışı ürün varken kupon tutmaz
        $this->post('/sepet', ['product_id' => $other->id, 'quantity' => 1]);
        $this->assertNotNull($cart->applyCoupon('SADECEBU'));

        // Kapsamdaki ürün eklenince yalnızca onun üzerinden indirim yapar
        $this->post('/sepet', ['product_id' => $allowed->id, 'quantity' => 1]);
        $this->assertNull($cart->applyCoupon('SADECEBU'));
        $this->assertEqualsWithDelta((float) $allowed->price * 0.5, $cart->discount(), 0.01);
    }

    public function test_kupon_indirimli_urunlerde_gecmeyebilir(): void
    {
        $product = $this->simpleProduct();
        $product->update(['price' => 100, 'compare_at_price' => 150, 'sale_starts_at' => null, 'sale_ends_at' => null]);

        Coupon::create([
            'code' => 'INDIRIMSIZ', 'type' => 'percent', 'value' => 10,
            'exclude_sale_items' => true, 'is_active' => true,
        ]);

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $error = app(Cart::class)->applyCoupon('INDIRIMSIZ');

        $this->assertNotNull($error);
        $this->assertStringContainsString('indirimli', $error);
    }

    public function test_kupon_kisi_basi_limiti(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'email' => 'kupon@ornek.com']);
        $product = $this->simpleProduct();

        $coupon = Coupon::create([
            'code' => 'BIRKEZ', 'type' => 'fixed', 'value' => 50,
            'per_user_limit' => 1, 'is_active' => true,
        ]);

        // Bu müşteri kuponu bir kez kullanmış
        Order::create([
            'number' => 'AP-KUPON-1',
            'user_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'customer_name' => 'Kupon', 'customer_phone' => '0533',
            'recipient_name' => 'A', 'total' => 100,
        ]);

        $this->actingAs($customer)->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $error = app(Cart::class)->applyCoupon('BIRKEZ');

        $this->assertSame('Bu kuponu kullanma hakkınız doldu.', $error);
    }

    public function test_kupon_eposta_kisiti(): void
    {
        $product = $this->simpleProduct();

        Coupon::create([
            'code' => 'OZELDAVET', 'type' => 'fixed', 'value' => 50,
            'allowed_emails' => 'vip@ornek.com', 'is_active' => true,
        ]);

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);
        $this->assertNotNull(app(Cart::class)->applyCoupon('OZELDAVET'));

        $vip = User::factory()->create(['role' => 'customer', 'email' => 'vip@ornek.com']);
        $this->actingAs($vip);
        $this->assertNull(app(Cart::class)->applyCoupon('OZELDAVET'));
    }

    // --- Düşük stok uyarısı -----------------------------------------------

    public function test_stok_esigin_altina_dusunce_eposta_gider(): void
    {
        Mail::fake();
        Setting::putMany(['low_stock_threshold' => 3, 'low_stock_email' => 'depo@ornek.com']);

        $product = $this->simpleProduct();
        $product->update(['stock' => 4]);

        $order = $this->makeOrder($product, 2); // 4 - 2 = 2 → eşiğin altında
        app(OrderStock::class)->reserve($order);

        Mail::assertSent(LowStockAlert::class, fn ($mail) => $mail->product->id === $product->id);
    }

    public function test_ayni_urun_icin_gunde_bir_kez_uyarilir(): void
    {
        Mail::fake();
        Setting::putMany(['low_stock_threshold' => 5, 'low_stock_email' => 'depo@ornek.com']);

        $product = $this->simpleProduct();
        $product->update(['stock' => 6]);

        app(OrderStock::class)->reserve($this->makeOrder($product, 1));
        app(OrderStock::class)->reserve($this->makeOrder($product, 1));

        Mail::assertSent(LowStockAlert::class, 1);
    }

    // --- Misafir sipariş sorgulama ----------------------------------------

    public function test_misafir_siparisini_numara_ve_telefonla_bulur(): void
    {
        $product = $this->simpleProduct();
        $order = $this->makeOrder($product, 1, ['customer_phone' => '0533 111 22 33']);

        $this->get('/siparis-sorgula')->assertOk();

        $this->post('/siparis-sorgula', [
            'number' => strtolower($order->number),
            'contact' => '+90 533 111 22 33',
        ])->assertRedirect(route('order.show', $order->number));

        $this->get('/siparis/'.$order->number)->assertOk();
    }

    public function test_yanlis_telefonla_siparis_bulunamaz(): void
    {
        $product = $this->simpleProduct();
        $order = $this->makeOrder($product, 1, ['customer_phone' => '0533 111 22 33']);

        $this->post('/siparis-sorgula', [
            'number' => $order->number,
            'contact' => '0555 999 88 77',
        ])->assertSessionHasErrors('number');

        $this->get('/siparis/'.$order->number)->assertNotFound();
    }

    // --- sitemap ----------------------------------------------------------

    public function test_sitemap_urunleri_icerir(): void
    {
        $product = Product::active()->firstOrFail();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false)
            ->assertSee(route('shop.product', $product->slug), false);
    }

    // --- Mağaza filtreleri ------------------------------------------------

    public function test_fiyat_ve_ozellik_filtreleri(): void
    {
        $this->get('/magaza?min=2000&max=3000')
            ->assertOk()
            ->assertDontSee('Tek Dal Kırmızı Gül');

        $this->get('/magaza?indirimli=1')->assertOk();
        $this->get('/magaza?ayni_gun=1&stokta=1')->assertOk();
    }

    public function test_stok_filtresi_tukenenleri_gizler(): void
    {
        $product = $this->simpleProduct();
        $product->update(['stock' => 0]);

        $this->get('/magaza?stokta=1')
            ->assertOk()
            ->assertDontSee($product->name);
    }

    // --- KVKK -------------------------------------------------------------

    public function test_musteri_verilerini_indirebilir(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get('/hesabim/verilerim');

        $response->assertOk()->assertHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->assertStringContainsString($customer->email, $response->streamedContent());
    }

    public function test_hesap_silinince_siparisler_anonimlesir(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'password' => 'gizli-parola-1']);
        $product = $this->simpleProduct();

        $order = $this->makeOrder($product, 1, [
            'user_id' => $customer->id,
            'customer_name' => 'Gerçek İsim',
            'card_message' => 'Özel not',
        ]);

        $customer->addresses()->create([
            'title' => 'Ev', 'recipient_name' => 'Ben', 'address' => 'Adres',
        ]);

        $this->actingAs($customer)
            ->delete('/hesabim/hesabi-sil', ['password' => 'gizli-parola-1', 'onay' => '1'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('addresses', ['user_id' => $customer->id]);

        $order->refresh();
        $this->assertNull($order->user_id);
        $this->assertSame('Silinmiş müşteri', $order->customer_name);
        $this->assertNull($order->card_message);
        // Muhasebe kaydı korunmalı
        $this->assertTrue((float) $order->total > 0);
    }

    public function test_yanlis_parolayla_hesap_silinmez(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'password' => 'gizli-parola-1']);

        $this->actingAs($customer)
            ->delete('/hesabim/hesabi-sil', ['password' => 'yanlis', 'onay' => '1'])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }
}
