<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        $this->customer = User::create([
            'name' => 'Selin Yılmaz',
            'email' => 'selin@ornek.com',
            'phone' => '0533 111 22 33',
            'password' => 'gizli-parola',
            'role' => 'customer',
        ]);
    }

    // --- Kayıt / giriş ----------------------------------------------------

    public function test_hesap_olusturulabilir_ve_rol_daima_musteri(): void
    {
        $this->post('/kayit', [
            'name' => 'Mert Kaya',
            'email' => 'Mert@Ornek.COM',
            'phone' => '0533 999 88 77',
            'password' => 'cok-gizli-parola',
            'password_confirmation' => 'cok-gizli-parola',
            'kvkk' => '1',
            // Kötü niyetli alan — yok sayılmalı
            'role' => 'admin',
        ])->assertRedirect(route('account.index'));

        $user = User::where('email', 'mert@ornek.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_zayif_parola_reddedilir(): void
    {
        $this->post('/kayit', [
            'name' => 'Test',
            'email' => 'yeni@ornek.com',
            'phone' => '0533 000 00 00',
            'password' => '1234',
            'password_confirmation' => '1234',
            'kvkk' => '1',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_giris_ve_cikis(): void
    {
        $this->post('/giris', ['email' => 'selin@ornek.com', 'password' => 'gizli-parola'])
            ->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($this->customer);

        $this->post('/cikis')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_hatali_parola_giris_yapamaz(): void
    {
        $this->post('/giris', ['email' => 'selin@ornek.com', 'password' => 'yanlis-parola'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_hesap_sayfalari_giris_ister(): void
    {
        foreach (['/hesabim', '/hesabim/siparislerim', '/hesabim/adreslerim', '/hesabim/favorilerim', '/hesabim/bilgilerim'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_hesap_sayfalari_acilir(): void
    {
        foreach (['/hesabim', '/hesabim/siparislerim', '/hesabim/adreslerim', '/hesabim/favorilerim', '/hesabim/bilgilerim'] as $path) {
            $this->actingAs($this->customer)->get($path)->assertOk();
        }
    }

    public function test_musteri_yonetim_paneline_giremez(): void
    {
        $this->actingAs($this->customer)->get('/admin')->assertForbidden();
    }

    // --- Bilgiler ---------------------------------------------------------

    public function test_bilgiler_guncellenir(): void
    {
        $this->actingAs($this->customer)
            ->put('/hesabim/bilgilerim', [
                'name' => 'Selin Yılmaz Kaya',
                'email' => 'selin@ornek.com',
                'phone' => '0533 444 55 66',
            ])->assertRedirect();

        $this->assertSame('Selin Yılmaz Kaya', $this->customer->fresh()->name);
    }

    public function test_parola_mevcut_parola_dogruysa_degisir(): void
    {
        $this->actingAs($this->customer)
            ->put('/hesabim/parola', [
                'current_password' => 'yanlis',
                'password' => 'yeni-gizli-parola',
                'password_confirmation' => 'yeni-gizli-parola',
            ])->assertSessionHasErrors('current_password');

        $this->actingAs($this->customer)
            ->put('/hesabim/parola', [
                'current_password' => 'gizli-parola',
                'password' => 'yeni-gizli-parola',
                'password_confirmation' => 'yeni-gizli-parola',
            ])->assertRedirect();

        $this->assertTrue(Hash::check('yeni-gizli-parola', $this->customer->fresh()->password));
    }

    // --- Adresler ---------------------------------------------------------

    public function test_adres_eklenir_ve_ilki_varsayilan_olur(): void
    {
        $zone = DeliveryZone::first();

        $this->actingAs($this->customer)->post('/hesabim/adreslerim', [
            'title' => 'Ev',
            'recipient_name' => 'Selin',
            'delivery_zone_id' => $zone->id,
            'address' => 'Karaoğlanoğlu Cad. No 12',
        ])->assertRedirect();

        $address = $this->customer->addresses()->firstOrFail();

        $this->assertTrue($address->is_default);
    }

    public function test_baskasinin_adresi_duzenlenemez(): void
    {
        $other = User::create([
            'name' => 'Başkası', 'email' => 'baska@ornek.com',
            'password' => 'parola-12345', 'role' => 'customer',
        ]);

        $address = Address::create([
            'user_id' => $other->id,
            'title' => 'Ev',
            'recipient_name' => 'Başkası',
            'address' => 'Gizli adres',
        ]);

        $this->actingAs($this->customer)
            ->put('/hesabim/adreslerim/'.$address->id, [
                'title' => 'Ele geçirildi',
                'recipient_name' => 'Ben',
                'address' => 'Yeni adres',
            ])->assertNotFound();

        $this->assertSame('Ev', $address->fresh()->title);
    }

    // --- Favoriler --------------------------------------------------------

    public function test_favori_eklenip_cikarilir(): void
    {
        $product = Product::first();

        $this->actingAs($this->customer)
            ->post('/hesabim/favorilerim/'.$product->id)
            ->assertOk()
            ->assertJson(['favorited' => true]);

        $this->assertSame(1, $this->customer->favorites()->count());

        $this->actingAs($this->customer)
            ->post('/hesabim/favorilerim/'.$product->id)
            ->assertJson(['favorited' => false]);

        $this->assertSame(0, $this->customer->favorites()->count());
    }

    public function test_tarayicidaki_favoriler_hesaba_tasinir(): void
    {
        $ids = Product::active()->limit(3)->pluck('id')->all();

        $this->actingAs($this->customer)
            ->postJson('/hesabim/favori-birlestir', ['ids' => $ids])
            ->assertOk();

        // Tekrar gönderilse de çoğalmamalı
        $this->actingAs($this->customer)
            ->postJson('/hesabim/favori-birlestir', ['ids' => $ids])
            ->assertOk();

        $this->assertSame(3, $this->customer->favorites()->count());
    }

    // --- Siparişler -------------------------------------------------------

    public function test_giris_yapmis_musterinin_siparisi_hesabina_baglanir(): void
    {
        $product = Product::active()->whereDoesntHave('variants')->where('stock', '>', 3)->firstOrFail();
        $zone = DeliveryZone::where('same_day', true)->firstOrFail();

        $this->actingAs($this->customer)->post('/sepet', ['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($this->customer)->post('/kasa', [
            'customer_name' => 'Selin Yılmaz',
            'customer_phone' => '0533 111 22 33',
            'recipient_name' => 'Annem',
            'delivery_zone_id' => $zone->id,
            'delivery_address' => 'Bir adres',
            'delivery_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
            'kvkk' => '1',
            'save_address' => '1',
            'address_title' => 'Annem',
        ])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame($this->customer->id, $order->user_id);
        // "Bu adresi kaydet" işaretliydi
        $this->assertDatabaseHas('addresses', ['user_id' => $this->customer->id, 'title' => 'Annem']);

        $this->actingAs($this->customer)->get('/hesabim/siparislerim/'.$order->number)->assertOk();
    }

    public function test_baskasinin_siparisi_hesaptan_gorunmez(): void
    {
        $other = User::create([
            'name' => 'Başkası', 'email' => 'baska2@ornek.com',
            'password' => 'parola-12345', 'role' => 'customer',
        ]);

        $order = Order::create([
            'number' => 'AP-TEST-1',
            'user_id' => $other->id,
            'customer_name' => 'Başkası',
            'customer_phone' => '0000',
            'recipient_name' => 'Alıcı',
            'total' => 100,
        ]);

        $this->actingAs($this->customer)->get('/hesabim/siparislerim/'.$order->number)->assertNotFound();
    }

    public function test_siparis_tekrarlanabilir(): void
    {
        $product = Product::active()->whereDoesntHave('variants')->where('stock', '>', 3)->firstOrFail();

        $order = Order::create([
            'number' => 'AP-TEST-2',
            'user_id' => $this->customer->id,
            'customer_name' => 'Selin',
            'customer_phone' => '0533',
            'recipient_name' => 'Alıcı',
            'total' => (float) $product->price,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 2,
            'line_total' => $product->price * 2,
        ]);

        $this->actingAs($this->customer)
            ->post('/hesabim/siparislerim/'.$order->number.'/tekrarla')
            ->assertRedirect(route('cart.index'));

        $this->assertSame(2, app(Cart::class)->count());
    }
}
