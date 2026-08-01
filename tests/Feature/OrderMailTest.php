<?php

namespace Tests\Feature;

use App\Mail\NewOrderAlert;
use App\Mail\OrderPaid;
use App\Mail\OrderPlaced;
use App\Mail\OrderStatusChanged;
use App\Mail\TestMail;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\OrderMailer;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        // defer() ile ertelenen gönderimler testte anında çalışsın
        $this->withoutDefer();

        Mail::fake();
    }

    private function simpleProduct(): Product
    {
        return Product::active()
            ->whereDoesntHave('variants')
            ->where('track_stock', true)
            ->where('stock', '>', 5)
            ->firstOrFail();
    }

    private function placeOrder(array $overrides = []): Order
    {
        $product = $this->simpleProduct();
        $zone = DeliveryZone::where('same_day', true)->firstOrFail();

        $this->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $this->post('/kasa', array_merge([
            'customer_name' => 'Ahmet Erhan',
            'customer_phone' => '0533 111 22 33',
            'customer_email' => 'musteri@ornek.com',
            'recipient_name' => 'Selin',
            'delivery_zone_id' => $zone->id,
            'delivery_address' => 'Girne, Örnek Sok. 5',
            'delivery_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
            'kvkk' => '1',
        ], $overrides));

        return Order::latest('id')->firstOrFail();
    }

    // --- Sipariş oluşturma ------------------------------------------------

    public function test_siparis_verilince_musteriye_ozet_ekibe_bildirim_gider(): void
    {
        $order = $this->placeOrder();

        Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo('musteri@ornek.com')
            && $mail->order->id === $order->id);

        Mail::assertSent(NewOrderAlert::class, fn ($mail) => $mail->hasTo('merhaba@ayparcasicicekci.com'));
    }

    public function test_eposta_birakmayan_musteriye_gonderilmez_ekip_yine_haberdar_olur(): void
    {
        $this->placeOrder(['customer_email' => null]);

        Mail::assertNotSent(OrderPlaced::class);
        Mail::assertSent(NewOrderAlert::class);
    }

    public function test_bildirimler_kapaliyken_hicbir_eposta_gitmez(): void
    {
        Setting::put('order_emails_enabled', false);

        $this->placeOrder();

        Mail::assertNothingSent();
    }

    // --- Durum değişiklikleri ---------------------------------------------

    public function test_durum_degisince_musteriye_bilgi_gider(): void
    {
        $order = $this->placeOrder();
        Mail::fake(); // sipariş anındaki postaları say dışı bırak

        $order->update(['status' => 'on_the_way']);

        Mail::assertSent(OrderStatusChanged::class, function ($mail) {
            return $mail->hasTo('musteri@ornek.com')
                && $mail->order->status === 'on_the_way';
        });
    }

    public function test_her_durum_icin_eposta_yok(): void
    {
        $order = $this->placeOrder();
        Mail::fake();

        // "pending" için bilgilendirme yapılmaz
        $order->update(['status' => 'pending']);
        Mail::assertNotSent(OrderStatusChanged::class);

        $this->assertFalse(OrderStatusChanged::supports('pending'));
        $this->assertTrue(OrderStatusChanged::supports('delivered'));
    }

    public function test_odeme_alininca_bilgi_gider(): void
    {
        $order = $this->placeOrder();
        Mail::fake();

        $order->update(['payment_status' => 'paid']);

        Mail::assertSent(OrderPaid::class, fn ($mail) => $mail->hasTo('musteri@ornek.com'));
    }

    public function test_iptal_edilince_hem_stok_geri_gelir_hem_bilgi_gider(): void
    {
        $product = $this->simpleProduct();
        $before = $product->stock;

        $order = $this->placeOrder();
        Mail::fake();

        $order->update(['status' => 'cancelled']);

        $this->assertSame($before, $product->fresh()->stock);
        Mail::assertSent(OrderStatusChanged::class);
    }

    // --- Şablonlar --------------------------------------------------------

    public function test_tum_eposta_sablonlari_hatasiz_derlenir(): void
    {
        $order = $this->placeOrder();
        $order->load('items');
        $url = 'https://ornek.test/siparis';

        // render() Blade'i gerçekten çalıştırır — şablon hatasını yakalar
        $this->assertStringContainsString($order->number, (new OrderPlaced($order, $url))->render());
        $this->assertStringContainsString($order->number, (new NewOrderAlert($order))->render());
        $this->assertStringContainsString($order->number, (new OrderPaid($order, $url))->render());

        foreach (array_keys(OrderStatusChanged::MESSAGES) as $status) {
            $order->forceFill(['status' => $status]);
            $html = (new OrderStatusChanged($order, $url))->render();

            $this->assertStringContainsString(OrderStatusChanged::MESSAGES[$status][0], $html);
        }

        $this->assertStringContainsString('deneme gönderimidir', (new TestMail)->render());
    }

    public function test_havale_secilirse_epostada_hesap_bilgisi_olur(): void
    {
        Setting::put('bank_details', 'Banka: Örnek Bank / IBAN: TR00 0000');

        $order = $this->placeOrder(['payment_method' => 'transfer']);

        $html = (new OrderPlaced($order->load('items'), 'https://ornek.test'))->render();

        $this->assertStringContainsString('TR00 0000', $html);
        $this->assertStringContainsString($order->number, $html);
    }

    // --- İmzalı bağlantı --------------------------------------------------

    public function test_epostadaki_baglanti_misafire_siparisi_acar(): void
    {
        $order = $this->placeOrder();

        // Yeni ziyaretçi: oturumda sipariş kaydı yok
        $this->flushSession();
        $this->get('/siparis/'.$order->number)->assertNotFound();

        $url = app(OrderMailer::class)->orderUrl($order);

        $this->get($url)->assertRedirect(route('order.show', $order->number));
        $this->get('/siparis/'.$order->number)->assertOk();
    }

    public function test_imzasiz_baglanti_calismaz(): void
    {
        $order = $this->placeOrder();
        $this->flushSession();

        $this->get('/siparis/'.$order->number.'/goruntule')->assertForbidden();
    }
}
