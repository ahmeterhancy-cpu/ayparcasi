<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Payments\TikoGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tiko Sanal POS — 3D Secure iFrame akışı.
 *
 * Testlerin ağırlığı "para" tarafında: siparişi ödendi yapan tek şeyin
 * DOĞRULANMIŞ bir yanıt olduğunu, sahte ya da oynanmış bildirimlerin
 * hiçbir şeyi değiştirmediğini kanıtlıyorlar.
 */
class TikoTest extends TestCase
{
    use RefreshDatabase;

    private const MERCHANT = '100001';

    private const SECRET = 'S3CR3T';

    private const PASSWORD = 'P4SS';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tiko.enabled' => true,
            'tiko.test_mode' => true,
            'tiko.merchant_id' => self::MERCHANT,
            'tiko.secret' => self::SECRET,
            'tiko.password' => self::PASSWORD,
        ]);
    }

    private function gateway(): TikoGateway
    {
        return app(TikoGateway::class);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'number' => 'AY-TEST-1',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'tiko',
            'customer_name' => 'Ahmet Erhan',
            'customer_phone' => '0533 111 22 33',
            'customer_email' => 'ahmet@ornek.com',
            'recipient_name' => 'Selin Yılmaz',
            'delivery_zone_name' => 'Girne',
            'delivery_address' => 'Karaoğlanoğlu Cad. No 12',
            'delivery_date' => now()->addDay()->toDateString(),
            'subtotal' => 1000,
            'delivery_fee' => 150,
            'total' => 1150,
            'currency' => 'TRY',
        ], $overrides));
    }

    /** Tiko'nun döndüreceği türden, imzası geçerli bir sonuç nesnesi. */
    private function sonuc(Order $order, string $status = '200', array $overrides = []): array
    {
        $r = array_merge([
            'MerchantId' => self::MERCHANT,
            'OrderId' => $order->number,
            'TransId' => 'TX-9',
            'Amount' => 1150.0,
            'Currency' => 'TRY',
            'Installment' => 0,
            'Status' => $status,
            'ErrorMsg' => '',
        ], $overrides);

        $r['Hash'] = $this->gateway()->hash(
            $r['MerchantId'].$r['OrderId'].number_format((float) $r['Amount'], 2, '.', '')
            .$r['Currency'].number_format((float) $r['Installment'], 2, '.', '')
            .$r['Status'].$r['TransId']
        );

        return $r;
    }

    // -- imza ------------------------------------------------------------

    public function test_hash_belgedeki_algoritmayla_uretilir(): void
    {
        // base64(HMAC-SHA256(anahtar=secret, mesaj=hashStr+password))
        $this->assertSame(
            'yRTqvYm0czJ6NznsrkZz8s3XV/qRrFt9pmvZWqvJ+YI=',
            $this->gateway()->hash('ABC'),
        );
    }

    public function test_kimlik_eksikse_kapali_sayilir(): void
    {
        config(['tiko.password' => null]);

        $this->assertFalse($this->gateway()->isConfigured());
    }

    // -- ödeme linki -----------------------------------------------------

    public function test_odeme_linki_belgedeki_alanlarla_istenir(): void
    {
        Http::fake([
            '*' => Http::response(['Status' => '200', 'Description' => '', 'Result' => ['Link' => 'https://www.tikokart.com/pay/abc']]),
        ]);

        $order = $this->order();
        $link = $this->gateway()->createPaymentLink($order);

        $this->assertSame('https://www.tikokart.com/pay/abc', $link);

        Http::assertSent(function ($request) use ($order) {
            $d = $request->data();
            $urlOk = route('payment.return', $order->number);

            // Kum havuzu adresi — canlı uca gitmediğini kanıtlar
            $this->assertStringContainsString('api-sanalpos-sandbox', $request->url());

            $this->assertSame(self::MERCHANT, $d['MerchantId']);
            $this->assertSame($order->number, $d['OrderId']);
            $this->assertSame('1150.00', $d['Amount']);
            $this->assertSame('TRY', $d['Currency']);
            $this->assertSame('1', $d['IsTest']);
            $this->assertSame($urlOk, $d['UrlOk']);

            // Belgedeki formül: MerchantId+OrderId+UrlOk+UrlFail+Amount+Currency+IsTest
            $this->assertSame(
                $this->gateway()->hash(self::MERCHANT.$order->number.$urlOk.$urlOk.'1150.00TRY1'),
                $d['Hash'],
            );

            return true;
        });
    }

    public function test_kart_numarasi_tikoya_bizden_gitmez(): void
    {
        Http::fake(['*' => Http::response(['Status' => '200', 'Result' => ['Link' => 'https://x/y']])]);

        $this->gateway()->createPaymentLink($this->order());

        // iFrame yöntemi seçildi: kart alanları isteğe HİÇ girmiyor.
        Http::assertSent(fn ($request) => ! array_intersect(
            ['CardNo', 'CardCvv', 'CardExpireMonth', 'CardExpireYear'],
            array_keys($request->data()),
        ));
    }

    public function test_odeme_sayfasi_linki_iframede_acar(): void
    {
        Http::fake(['*' => Http::response(['Status' => '200', 'Result' => ['Link' => 'https://www.tikokart.com/pay/abc']])]);

        $order = $this->order();
        $this->withSession(['my_orders' => [$order->number]]);

        $this->get(route('payment.redirect', $order->number))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('https://www.tikokart.com/pay/abc', false)
            // Kart alanları BİZİM sayfamızda olmamalı
            ->assertDontSee('name="CardNo"', false);
    }

    public function test_odenmis_siparis_tekrar_odemeye_gonderilmez(): void
    {
        // fake() olmadan assertNothingSent() hiçbir şey kanıtlamaz:
        // kayıt tutulmadığı için gerçek istek gitse bile geçerdi.
        Http::fake();

        $order = $this->order(['payment_status' => 'paid']);
        $this->withSession(['my_orders' => [$order->number]]);

        $this->get(route('payment.redirect', $order->number))
            ->assertRedirect(route('order.show', $order->number));

        Http::assertNothingSent();
    }

    public function test_tiko_link_vermezse_siparis_kaybolmaz(): void
    {
        Http::fake(['*' => Http::response(['Status' => '400', 'Description' => 'Hatalı istek'])]);

        $order = $this->order();
        $this->withSession(['my_orders' => [$order->number]]);

        $this->get(route('payment.redirect', $order->number))
            ->assertRedirect(route('order.show', $order->number))
            ->assertSessionHas('error');

        $this->assertSame('pending', $order->refresh()->payment_status);
    }

    public function test_baskasinin_siparisinin_odeme_sayfasi_acilmaz(): void
    {
        $order = $this->order();

        $this->get(route('payment.redirect', $order->number))->assertNotFound();
    }

    // -- dönüş sayfası ---------------------------------------------------

    public function test_donus_sunucudan_sunucuya_sorup_siparisi_oder(): void
    {
        $order = $this->order();

        Http::fake([
            '*payment/status*' => Http::response([
                'Status' => '200',
                'Result' => $this->sonuc($order),
            ]),
        ]);

        // Tiko iFrame içinden POST ediyor: CSRF jetonu yok, oturum yok.
        $this->post(route('payment.return', $order->number), ['Status' => '200', 'TransId' => 'TX-9'])
            ->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
        $this->assertSame('TX-9', $order->payment_reference);
        $this->assertNotNull($order->paid_at);
    }

    public function test_donustaki_status_alanina_guvenilmez(): void
    {
        $order = $this->order();

        // Tarayıcıdan gelen "başarılı" iddiası; sunucu sorgusu ise başarısız.
        Http::fake([
            '*payment/status*' => Http::response([
                'Status' => '200',
                'Result' => $this->sonuc($order, '300'),
            ]),
        ]);

        $this->post(route('payment.return', $order->number), ['Status' => '200', 'TransId' => 'TX-9'])
            ->assertOk();

        $this->assertSame('failed', $order->refresh()->payment_status);
    }

    public function test_donus_sayfasi_ust_pencereyi_siparise_gonderir(): void
    {
        $order = $this->order();
        Http::fake(['*' => Http::response(['Status' => '400'])]);

        $this->post(route('payment.return', $order->number))
            ->assertOk()
            // iFrame'de kalmasın diye üst pencere yönlendiriliyor
            ->assertSee('window.top', false)
            ->assertSee(route('order.show', $order->number), false);
    }

    public function test_donus_musterinin_oturumunu_ezmez(): void
    {
        $order = $this->order();
        Http::fake(['*' => Http::response(['Status' => '400'])]);

        $response = $this->post(route('payment.return', $order->number));

        // Oturum middleware'i kapalı: yanıtta oturum çerezi HİÇ olmamalı,
        // yoksa müşterinin sepeti/siparişi taşıyan çerezi ezilirdi.
        $this->assertArrayNotHasKey(config('session.cookie'), $response->headers->getCookies());
    }

    // -- callback --------------------------------------------------------

    public function test_gecerli_callback_siparisi_oder(): void
    {
        $order = $this->order();

        $this->postJson(route('payment.callback'), $this->sonuc($order))
            ->assertOk();

        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_imzasi_bozuk_callback_reddedilir(): void
    {
        $order = $this->order();
        $payload = $this->sonuc($order);
        $payload['Hash'] = 'sahte';

        $this->postJson(route('payment.callback'), $payload)->assertStatus(400);

        $this->assertSame('pending', $order->refresh()->payment_status);
    }

    public function test_tutari_oynanmis_callback_reddedilir(): void
    {
        $order = $this->order();

        // İmza kendi içinde tutarlı ama tutar siparişin toplamı değil:
        // saldırgan kendi hesabıyla 1 TL ödeyip 1150 TL'lik siparişi
        // kapatamamalı.
        $payload = $this->sonuc($order, '200', ['Amount' => 1.0]);

        $this->postJson(route('payment.callback'), $payload)->assertOk();

        $this->assertSame('pending', $order->refresh()->payment_status);
    }

    public function test_baska_uye_isyerinin_bildirimi_reddedilir(): void
    {
        $order = $this->order();
        $payload = $this->sonuc($order);
        $payload['MerchantId'] = '999999';

        $this->postJson(route('payment.callback'), $payload)->assertStatus(400);

        $this->assertSame('pending', $order->refresh()->payment_status);
    }

    public function test_sonucu_belli_olmayan_bildirim_siparisi_dusurmez(): void
    {
        $order = $this->order();

        $this->postJson(route('payment.callback'), $this->sonuc($order, '100'))->assertOk();

        // 100 = "henüz belli değil". Başarısız SAYILMAMALI.
        $this->assertSame('pending', $order->refresh()->payment_status);
    }

    public function test_odenmis_siparis_tekrar_bildirimle_bozulmaz(): void
    {
        $order = $this->order();

        $this->postJson(route('payment.callback'), $this->sonuc($order))->assertOk();
        $paidAt = $order->refresh()->paid_at;

        // Tiko yanıt alamazsa gün içinde yeniden deniyor; ikinci kez
        // "başarısız" gelse bile ödenmiş sipariş geri alınmamalı.
        $this->postJson(route('payment.callback'), $this->sonuc($order, '300'))->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals($paidAt, $order->paid_at);
    }

    public function test_bilinmeyen_siparis_numarasi_reddedilir(): void
    {
        $order = $this->order();
        $payload = $this->sonuc($order, '200', ['OrderId' => 'AY-YOK']);

        $this->postJson(route('payment.callback'), $payload)->assertStatus(400);
    }
}
