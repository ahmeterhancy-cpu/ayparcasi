<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_sabit_sayfalar_acilir(): void
    {
        foreach ([
            '/',
            '/magaza',
            '/arama?q=gul',
            '/sepet',
            '/hakkimizda',
            '/iletisim',
            '/teslimat',
            '/sikca-sorulan-sorular',
            '/blog',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_kategori_ve_urun_sayfalari_acilir(): void
    {
        foreach (Category::active()->get() as $category) {
            $this->get('/kategori/'.$category->slug)->assertOk();
        }

        foreach (Product::active()->limit(8)->get() as $product) {
            $this->get('/urun/'.$product->slug)->assertOk();
        }

        foreach (Post::published()->get() as $post) {
            $this->get('/blog/'.$post->slug)->assertOk();
        }
    }

    public function test_hizli_bakis_parcasi_doner(): void
    {
        $product = Product::active()->whereHas('variants')->firstOrFail();

        $this->get('/urun/'.$product->slug.'/hizli-bakis')
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Boy seçin')
            // Düzen olmadan yalnızca parça dönmeli
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_pasif_urun_hizli_bakista_da_404_verir(): void
    {
        $product = Product::first();
        $product->update(['is_active' => false]);

        $this->get('/urun/'.$product->slug.'/hizli-bakis')->assertNotFound();
    }

    public function test_pasif_urun_404_verir(): void
    {
        $product = Product::first();
        $product->update(['is_active' => false]);

        $this->get('/urun/'.$product->slug)->assertNotFound();
    }

    /**
     * Panelden gelen "Önizleme" bağlantısı taslakları da açmalı, yoksa
     * önizlemenin en gerekli olduğu yerde 404 verirdi.
     */
    public function test_pasif_urunu_yalniz_ekip_onizleyebilir(): void
    {
        $product = Product::first();
        $product->update(['is_active' => false]);

        foreach (['admin', 'staff'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/urun/'.$product->slug)
                ->assertOk();
        }

        // Müşteri ve misafir için 404 sürüyor
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get('/urun/'.$product->slug)
            ->assertNotFound();
    }

    public function test_stok_sorusu_kaydedilir_ve_whatsapp_baglantisi_doner(): void
    {
        $product = Product::first();

        $this->postJson('/stok-sor', ['product_id' => $product->id, 'source' => 'product'])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->assertDatabaseHas('stock_inquiries', [
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);
    }

    public function test_bultene_abone_olunur(): void
    {
        $this->post('/bulten', ['email' => 'Test@Ornek.COM'])->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'test@ornek.com']);
    }
}
