<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_panel_giris_gerektirir(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_yetkisiz_kullanici_panele_giremez(): void
    {
        $outsider = User::factory()->create(['role' => 'musteri']);

        $this->actingAs($outsider)->get('/admin')->assertForbidden();
    }

    public function test_tum_liste_sayfalari_acilir(): void
    {
        $paths = [
            '/admin',
            '/admin/orders',
            '/admin/orders/create',
            '/admin/reports',
            '/admin/musteriler',
            '/admin/products',
            '/admin/products/create',
            '/admin/categories',
            '/admin/categories/create',
            '/admin/addons',
            '/admin/coupons',
            '/admin/coupons/create',
            '/admin/delivery-zones',
            '/admin/delivery-slots',
            '/admin/banners',
            '/admin/posts',
            '/admin/posts/create',
            '/admin/faqs',
            '/admin/testimonials',
            '/admin/stock-inquiries',
            '/admin/newsletter-subscribers',
            '/admin/site-settings',
            '/admin/users',
        ];

        foreach ($paths as $path) {
            $this->actingAs($this->admin)->get($path)->assertOk();
        }
    }

    public function test_musteri_detayi_acilir(): void
    {
        $customer = User::create([
            'name' => 'Selin Yılmaz',
            'email' => 'selin.detay@ornek.com',
            'password' => 'parola-12345',
            'role' => 'customer',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/musteriler/'.$customer->id)
            ->assertOk()
            ->assertSee('Selin Yılmaz');
    }

    public function test_ekip_listesinde_musteriler_gorunmez(): void
    {
        User::create([
            'name' => 'Gizli Müşteri',
            'email' => 'gizli@ornek.com',
            'password' => 'parola-12345',
            'role' => 'customer',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertDontSee('Gizli Müşteri');
    }

    public function test_urun_duzenleme_sayfasi_acilir(): void
    {
        $product = Product::firstOrFail();

        $this->actingAs($this->admin)
            ->get('/admin/products/'.$product->id.'/edit')
            ->assertOk();
    }

    public function test_personel_kullanicilari_yonetemez(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/admin')->assertOk();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
    }

    /**
     * Ürün listesi kategorileri KIRPMAZ. Eskiden ilk ikisi görünüp kalanı
     * "+N / kayıt daha göster" arkasına saklanıyordu; kullanıcı hepsini
     * görmek istedi. Taşma `wrap()` ile çözülüyor, kırpma ile değil.
     */
    public function test_urun_listesi_tum_kategorileri_gosterir(): void
    {
        $product = Product::firstOrFail();

        $categories = Category::query()->limit(6)->get();

        $this->assertCount(6, $categories, 'Test için en az 6 kategori gerekiyor.');

        $product->categories()->sync($categories->pluck('id'));

        $response = $this->actingAs($this->admin)->get('/admin/products');

        $response->assertOk();

        foreach ($categories as $category) {
            $response->assertSee($category->name, escape: false);
        }

        // Filament'in kırpma rozetinin izleri: hiçbiri kalmamalı.
        $response->assertDontSee('kayıt daha göster', escape: false);
    }
}
