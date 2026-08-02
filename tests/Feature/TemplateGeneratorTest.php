<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductTemplates\Pages\ListProductTemplates;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Services\TemplateGenerator;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TemplateGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function generator(): TemplateGenerator
    {
        return app(TemplateGenerator::class);
    }

    public function test_her_alt_kategori_icin_sablon_acar(): void
    {
        $result = $this->generator()->generate();

        $this->assertContains('Buketler', $result['created']);
        $this->assertContains('Orkideler', $result['created']);

        $template = ProductTemplate::where('name', 'Buketler')->firstOrFail();

        // Şablon yalnız kendi kategorisini taşır, ürünün özel gün
        // kategorilerini değil
        $this->assertSame(
            [Category::where('name', 'Buketler')->value('id')],
            $template->category_ids,
        );

        // "Çok satan" gibi rozetler yeni ürüne kendiliğinden yapışmasın
        $this->assertNull($template->badge);
        $this->assertSame(0, $template->stock);
        $this->assertNotNull($template->care_notes);
    }

    public function test_atlanan_ust_kategorinin_altindakiler_uretilmez(): void
    {
        $ozelGunler = Category::where('name', 'Özel Günler')->firstOrFail();

        $result = $this->generator()->generate(excludeParentIds: [$ozelGunler->id]);

        $this->assertContains('Buketler', $result['created']);
        $this->assertNotContains('Yıldönümü', $result['created']);
        $this->assertSame(0, ProductTemplate::where('name', 'Yıldönümü')->count());
    }

    public function test_az_urunlu_kategori_atlanir(): void
    {
        $result = $this->generator()->generate(minProducts: 100);

        $this->assertSame([], $result['created']);
        $this->assertNotEmpty($result['skipped']);
    }

    public function test_mevcut_sablon_ezilmez_tazele_ile_guncellenir(): void
    {
        $this->generator()->generate();

        $template = ProductTemplate::where('name', 'Buketler')->firstOrFail();
        $template->update(['short_description' => 'Elle yazdığım metin']);

        $result = $this->generator()->generate();
        $this->assertSame('Elle yazdığım metin', $template->refresh()->short_description);
        $this->assertContains('Buketler (şablon zaten var)', $result['skipped']);

        $result = $this->generator()->generate(refresh: true);
        $this->assertContains('Buketler', $result['updated']);
        $this->assertNotSame('Elle yazdığım metin', $template->refresh()->short_description);
    }

    public function test_fiyat_grubun_ortancasi_olur(): void
    {
        $category = Category::create(['name' => 'Deneme tipi', 'slug' => 'deneme-tipi', 'parent_id' => null]);
        $child = Category::create(['name' => 'Mumlar', 'slug' => 'mumlar', 'parent_id' => $category->id]);

        foreach ([100, 300, 500] as $i => $price) {
            $product = Product::create([
                'name' => 'Mum '.$i,
                'slug' => 'mum-'.$i,
                'price' => $price,
            ]);
            $product->categories()->attach($child->id);
        }

        $this->generator()->generate();

        $this->assertSame('300.00', ProductTemplate::where('name', 'Mumlar')->value('price'));
    }

    public function test_yalnizca_hepsinde_olan_ek_urunler_sablona_girer(): void
    {
        $category = Category::create(['name' => 'Deneme', 'slug' => 'deneme-ust']);
        $child = Category::create(['name' => 'Sepetler', 'slug' => 'sepetler', 'parent_id' => $category->id]);

        $ortak = Addon::where('name', 'Cam vazo')->firstOrFail();
        $tekil = Addon::where('name', 'Uçan balon')->firstOrFail();

        $a = Product::create(['name' => 'Sepet A', 'slug' => 'sepet-a', 'price' => 100]);
        $b = Product::create(['name' => 'Sepet B', 'slug' => 'sepet-b', 'price' => 200]);

        $a->categories()->attach($child->id);
        $b->categories()->attach($child->id);
        $a->addons()->sync([$ortak->id, $tekil->id]);
        $b->addons()->sync([$ortak->id]);

        $this->generator()->generate();

        $this->assertSame(
            [$ortak->id],
            array_values(ProductTemplate::where('name', 'Sepetler')->firstOrFail()->addon_ids),
        );
    }

    public function test_boy_seti_temsilci_urunden_kopyalanir(): void
    {
        $this->generator()->generate();

        $buketler = ProductTemplate::where('name', 'Buketler')->firstOrFail();

        $this->assertNotEmpty($buketler->variants);
        // Stok şablona taşınmaz — her ürünün stoğu kendine ait
        foreach ($buketler->variants as $variant) {
            $this->assertSame(0, $variant['stock']);
            $this->assertNotEmpty($variant['name']);
        }
    }

    public function test_komut_calisir(): void
    {
        $ozelGunler = Category::where('name', 'Özel Günler')->firstOrFail();

        $this->artisan('urun:sablon-olustur', ['--haric' => [$ozelGunler->id]])
            ->assertSuccessful();

        $this->assertGreaterThan(0, ProductTemplate::count());
        $this->assertSame(0, ProductTemplate::where('name', 'Anneler Günü')->count());
    }

    public function test_panelden_katalogdan_olustur_calisir(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ozelGunler = Category::where('name', 'Özel Günler')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ListProductTemplates::class)
            ->callAction('katalogdan_olustur', [
                'exclude_parents' => [$ozelGunler->id],
                'min_products' => 2,
                'refresh' => false,
            ]);

        $this->assertGreaterThan(0, ProductTemplate::count());
        $this->assertSame(0, ProductTemplate::where('name', 'Doğum Günü')->count());
    }
}
