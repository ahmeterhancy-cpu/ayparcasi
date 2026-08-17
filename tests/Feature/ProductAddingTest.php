<?php

namespace Tests\Feature;

use App\Filament\Pages\Tezgah;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTemplate;
use App\Models\User;
use App\Services\BulkPhotoDrafts;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAddingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function template(): ProductTemplate
    {
        return ProductTemplate::create([
            'name' => 'Buket',
            'short_description' => 'Elde bağlanır.',
            'description' => 'Sipariş üzerine hazırlanır.',
            'care_notes' => "Suyunu iki günde bir değiştirin.\nSap uçlarını eğik kesin.",
            'contents' => 'Mevsim çiçekleri',
            'badge' => 'Yeni',
            'price' => 900,
            'same_day' => true,
            'track_stock' => true,
            'stock' => 5,
            'category_ids' => Category::limit(2)->pluck('id')->all(),
            'addon_ids' => Addon::limit(2)->pluck('id')->all(),
            'variants' => [
                ['name' => 'Küçük', 'description' => '9 dal', 'price' => 900, 'stock' => 3],
                ['name' => 'Orta', 'description' => '15 dal', 'price' => 1400, 'stock' => 2, 'is_default' => true],
            ],
        ]);
    }

    public function test_sablon_urune_metin_kategori_ek_urun_ve_boylari_kopyalar(): void
    {
        $template = $this->template();

        $product = Product::create([
            'name' => 'Deneme buketi',
            'slug' => 'deneme-buketi',
            'is_active' => false,
        ]);

        $template->applyTo($product);

        $product->refresh();

        $this->assertSame('Elde bağlanır.', $product->short_description);
        $this->assertSame('Yeni', $product->badge);
        $this->assertCount(2, $product->categories);
        $this->assertCount(2, $product->addons);
        $this->assertCount(2, $product->variants);
        $this->assertSame('Orta', $product->variants()->where('is_default', true)->value('name'));
    }

    public function test_sablonda_varsayilan_boy_yoksa_ilki_varsayilan_olur(): void
    {
        $template = $this->template();
        $template->update(['variants' => [
            ['name' => 'Küçük', 'price' => 900],
            ['name' => 'Orta', 'price' => 1400],
        ]]);

        $product = Product::create(['name' => 'Tek', 'slug' => 'tek']);
        $template->applyTo($product);

        $this->assertSame('Küçük', $product->variants()->where('is_default', true)->value('name'));
    }

    public function test_sablon_mevcut_boylari_ezmez(): void
    {
        $template = $this->template();

        $product = Product::create(['name' => 'Boylu', 'slug' => 'boylu']);
        $product->variants()->create(['name' => 'Elle eklenen', 'price' => 100, 'is_default' => true]);

        $template->applyTo($product);

        $this->assertCount(1, $product->refresh()->variants);
    }

    public function test_toplu_fotograf_taslak_urun_acar(): void
    {
        $result = app(BulkPhotoDrafts::class)->create([
            UploadedFile::fake()->image('kirmizi-gul-buketi.jpg'),
            UploadedFile::fake()->image('IMG_4821.jpg'),
        ]);

        $this->assertSame(2, $result['created']);

        $named = Product::where('name', 'Kirmizi Gul Buketi')->firstOrFail();
        $this->assertFalse($named->is_active, 'Taslak yayına girmemeli');
        $this->assertNotNull($named->hero_image);
        Storage::disk('public')->assertExists($named->hero_image);

        // Telefonun verdiği anlamsız ad kullanılmaz
        $this->assertDatabaseHas('products', ['name' => 'Yeni ürün 2']);
    }

    public function test_toplu_fotograf_sablonu_uygular(): void
    {
        $template = $this->template();

        app(BulkPhotoDrafts::class)->create(
            [UploadedFile::fake()->image('lale-aranjmani.jpg')],
            $template,
        );

        $product = Product::where('name', 'Lale Aranjmani')->firstOrFail();

        $this->assertSame('Yeni', $product->badge);
        $this->assertCount(2, $product->variants);
        $this->assertFalse($product->is_active);
    }

    public function test_urunden_sablon_cikarilir(): void
    {
        $product = Product::has('variants')->with('categories', 'addons', 'variants')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(EditProduct::class, ['record' => $product->id])
            ->callAction('sablon_cikar', ['name' => 'Buket şablonu']);

        $template = ProductTemplate::where('name', 'Buket şablonu')->firstOrFail();

        $this->assertSame($product->care_notes, $template->care_notes);
        $this->assertSame($product->categories->pluck('id')->all(), $template->category_ids);
        $this->assertSame($product->addons->pluck('id')->all(), $template->addon_ids);
        $this->assertCount($product->variants->count(), $template->variants);
        // Stok şablona taşınmaz — her ürünün stoğu kendine ait
        $this->assertSame(0, $template->stock);
    }

    public function test_sablon_yokken_ekranlar_yol_gosterir(): void
    {
        $this->assertSame(0, ProductTemplate::count());

        $this->actingAs($this->admin)
            ->get('/admin/tezgah')
            ->assertOk()
            ->assertSee('Henüz şablon yok');

        $this->actingAs($this->admin)
            ->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Henüz şablon yok');
    }

    public function test_yeni_panel_sayfalari_acilir(): void
    {
        $this->template();

        foreach ([
            '/admin/tezgah',
            '/admin/product-templates',
            '/admin/product-templates/create',
        ] as $path) {
            $this->actingAs($this->admin)->get($path)->assertOk();
        }

        $this->actingAs($this->admin)
            ->get('/admin/product-templates/'.ProductTemplate::first()->id.'/edit')
            ->assertOk();
    }

    public function test_tezgah_urun_ekler_ve_formu_sifirlar(): void
    {
        $template = $this->template();

        $image = UploadedFile::fake()->image('tezgah.jpg');
        $path = Storage::disk('public')->putFile('products', $image);

        Livewire::actingAs($this->admin)
            ->test(Tezgah::class)
            ->fillForm([
                'name' => 'Tezgâhtan buket',
                'price' => 1250,
                'stock' => 4,
                'hero_image' => [$path],
                'template_id' => $template->id,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            // Ad alanı sıradaki ürün için temizlenir
            ->assertFormSet(['name' => null]);

        $product = Product::where('name', 'Tezgâhtan buket')->firstOrFail();

        $this->assertTrue($product->is_active);
        // Elle girilen fiyat ve stok şablonunkini ezer
        $this->assertSame('1250.00', $product->price);
        $this->assertSame(4, $product->stock);
        // Şablonun metni ve boyları yine de gelir
        $this->assertSame('Yeni', $product->badge);
        $this->assertCount(2, $product->variants);
    }

    public function test_urun_listesinde_ad_fiyat_ve_yayin_satir_ici_duzenlenir(): void
    {
        $draft = Product::where('is_active', true)->firstOrFail();
        $draft->update(['is_active' => false, 'name' => 'Yeni ürün 1']);

        $list = Livewire::actingAs($this->admin)
            ->test(ListProducts::class)
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('price')
            ->assertCanRenderTableColumn('is_active');

        // Taslağın adı düzeltilince bağlantı adresi de düzelir
        $list->call('updateTableColumnState', 'name', (string) $draft->id, 'Papatya demeti 42');
        $this->assertSame('papatya-demeti-42', $draft->refresh()->slug);

        $list->call('updateTableColumnState', 'price', (string) $draft->id, '1750');
        $this->assertSame('1750.00', $draft->refresh()->price);

        // Yayına alınmış ürünün adresi artık sabit — bağlantılar kırılmasın
        $draft->update(['is_active' => true]);
        $list->call('updateTableColumnState', 'name', (string) $draft->id, 'Bambaşka bir ad');
        $this->assertSame('papatya-demeti-42', $draft->refresh()->slug);
        $this->assertSame('Bambaşka bir ad', $draft->name);
    }

    public function test_urun_listesinde_one_cikan_ve_rozet_satir_ici_duzenlenir(): void
    {
        // Vitrinde en sık oynanan iki alan; ürün formunu açmadan değişsin.
        $product = Product::firstOrFail();
        $product->update(['is_featured' => false, 'badge' => null]);

        $list = Livewire::actingAs($this->admin)
            ->test(ListProducts::class)
            ->assertCanRenderTableColumn('is_featured')
            ->assertCanRenderTableColumn('badge');

        $list->call('updateTableColumnState', 'is_featured', (string) $product->id, true);
        $this->assertTrue($product->refresh()->is_featured);

        $list->call('updateTableColumnState', 'badge', (string) $product->id, 'Çok satan');
        $this->assertSame('Çok satan', $product->refresh()->badge);

        // Boş bırakmak rozeti kaldırır
        $list->call('updateTableColumnState', 'badge', (string) $product->id, '');
        $this->assertEmpty($product->refresh()->badge);

        $list->call('updateTableColumnState', 'is_featured', (string) $product->id, false);
        $this->assertFalse($product->refresh()->is_featured);
    }

    public function test_urun_listesinde_kategori_sutunu_kisaltilmaz(): void
    {
        // Bir ürün 10'dan fazla kategoriye bağlı olabiliyor. Önceden ilk ikisi
        // gösterilip kalanı "+N" arkasına saklanıyordu; kullanıcı hepsinin
        // görünmesini istedi. Taşma artık kırpma ile değil, rozetleri alt
        // satıra saran `wrap()` ile önleniyor.
        $product = Product::active()->firstOrFail();
        $product->categories()->sync(Category::limit(5)->pluck('id'));

        // Basılan HTML'e bakmak kırılgan (kategori adları filtre menüsünde de
        // geçiyor). Sütunun kendi ayarını doğruluyoruz.
        $column = Livewire::actingAs($this->admin)
            ->test(ListProducts::class)
            ->instance()
            ->getTable()
            ->getColumn('categories.name');

        $this->assertNull($column->getListLimit(), 'Kategori listesi kırpılmamalı.');
        $this->assertTrue($column->canWrap(), 'Kırpma yoksa taşmayı yalnız wrap() önler.');
    }

    public function test_urun_formunda_sablon_secilince_boylar_kopyalanir(): void
    {
        $template = $this->template();

        Livewire::actingAs($this->admin)
            ->test(CreateProduct::class)
            ->fillForm([
                'sablon' => $template->id,
                'name' => 'Şablonlu ürün',
                'price' => 1000,
                'categories' => Category::limit(1)->pluck('id')->all(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Şablonlu ürün')->firstOrFail();

        $this->assertCount(2, $product->variants);
        $this->assertSame('Orta', $product->variants()->where('is_default', true)->value('name'));
    }
}
