<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Product $product;

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

        $this->product = Product::active()->firstOrFail();
    }

    /** Verilen durumda, bu ürünü içeren bir sipariş açar. */
    private function orderFor(User $user, string $status, ?Product $product = null): Order
    {
        $product ??= $this->product;

        $order = Order::create([
            'number' => 'AP-TEST-'.uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '0533',
            'recipient_name' => 'Alıcı',
            'status' => $status,
            'total' => (float) $product->price,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'line_total' => $product->price,
        ]);

        return $order;
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'rating' => 5,
            'title' => 'Çok güzeldi',
            'body' => 'Buket tam tarif ettiğim gibi geldi, çiçekler taptazeydi.',
        ], $override);
    }

    private function url(): string
    {
        return '/urun/'.$this->product->slug.'/yorum';
    }

    // --- Tohum verisi -----------------------------------------------------

    public function test_urunler_uydurma_puanla_baslamaz(): void
    {
        $this->assertSame(0, Product::whereNotNull('rating')->count());
        $this->assertSame(0, Product::where('review_count', '>', 0)->count());
    }

    public function test_puani_olmayan_urunun_sayfasinda_yildiz_gosterilmez(): void
    {
        $this->get($this->product->url)
            ->assertOk()
            ->assertSee('Henüz yorum yok')
            ->assertDontSee('stars-rate');
    }

    // --- Yorum hakkı ------------------------------------------------------

    public function test_misafir_yorum_yazamaz(): void
    {
        $this->post($this->url(), $this->payload())->assertRedirect(route('login'));

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_siparis_vermemis_musteri_yorum_yazamaz(): void
    {
        $this->actingAs($this->customer)
            ->post($this->url(), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_siparisi_henuz_teslim_edilmemis_musteri_yorum_yazamaz(): void
    {
        $this->orderFor($this->customer, 'on_the_way');

        $this->actingAs($this->customer)
            ->post($this->url(), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_baskasinin_siparisi_yorum_hakki_vermez(): void
    {
        $other = User::create([
            'name' => 'Başkası',
            'email' => 'baskasi@ornek.com',
            'phone' => '0533 000 00 00',
            'password' => 'gizli-parola',
            'role' => 'customer',
        ]);

        $this->orderFor($other, 'delivered');

        $this->actingAs($this->customer)
            ->post($this->url(), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_teslim_almis_musteri_yorum_yazabilir(): void
    {
        $order = $this->orderFor($this->customer, 'delivered');

        $this->actingAs($this->customer)
            ->post($this->url(), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'rating' => 5,
            'status' => 'pending',
            'name' => 'Selin Yılmaz',
        ]);
    }

    public function test_ayni_urune_ikinci_yorum_yazilamaz(): void
    {
        $this->orderFor($this->customer, 'delivered');

        $this->actingAs($this->customer)->post($this->url(), $this->payload());
        $this->actingAs($this->customer)->post($this->url(), $this->payload(['title' => 'İkinci']));

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_gecersiz_puan_reddedilir(): void
    {
        $this->orderFor($this->customer, 'delivered');

        $this->actingAs($this->customer)
            ->post($this->url(), $this->payload(['rating' => 9]))
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_cok_kisa_yorum_reddedilir(): void
    {
        $this->orderFor($this->customer, 'delivered');

        $this->actingAs($this->customer)
            ->post($this->url(), $this->payload(['body' => 'iyi']))
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('reviews', 0);
    }

    // --- Onay akışı ve ortalama ------------------------------------------

    public function test_onaylanmamis_yorum_sayfada_gorunmez(): void
    {
        $this->orderFor($this->customer, 'delivered');
        $this->actingAs($this->customer)->post($this->url(), $this->payload());

        $this->get($this->product->url)->assertDontSee('Çok güzeldi');

        $this->assertNull($this->product->fresh()->rating);
        $this->assertSame(0, (int) $this->product->fresh()->review_count);
    }

    public function test_onaylanan_yorum_yayinlanir_ve_ortalamaya_girer(): void
    {
        $this->orderFor($this->customer, 'delivered');
        $this->actingAs($this->customer)->post($this->url(), $this->payload(['rating' => 4]));

        Review::firstOrFail()->update(['status' => 'approved']);

        $fresh = $this->product->fresh();
        $this->assertSame('4.00', (string) $fresh->rating);
        $this->assertSame(1, (int) $fresh->review_count);

        $this->get($this->product->url)
            ->assertSee('Çok güzeldi')
            ->assertSee('Doğrulanmış alışveriş');
    }

    public function test_yayindan_kaldirilan_yorum_ortalamadan_dusulur(): void
    {
        $this->orderFor($this->customer, 'delivered');
        $this->actingAs($this->customer)->post($this->url(), $this->payload());

        $review = Review::firstOrFail();
        $review->update(['status' => 'approved']);
        $this->assertSame(1, (int) $this->product->fresh()->review_count);

        $review->update(['status' => 'rejected']);

        $this->assertNull($this->product->fresh()->rating);
        $this->assertSame(0, (int) $this->product->fresh()->review_count);
    }

    public function test_silinen_yorum_ortalamadan_dusulur(): void
    {
        $this->orderFor($this->customer, 'delivered');
        $this->actingAs($this->customer)->post($this->url(), $this->payload());

        $review = Review::firstOrFail();
        $review->update(['status' => 'approved']);

        $review->delete();

        $this->assertNull($this->product->fresh()->rating);
        $this->assertSame(0, (int) $this->product->fresh()->review_count);
    }

    public function test_ortalama_birden_fazla_yorumdan_hesaplanir(): void
    {
        $ratings = [5, 4, 3];

        foreach ($ratings as $i => $rating) {
            $user = User::create([
                'name' => 'Müşteri '.$i,
                'email' => "musteri{$i}@ornek.com",
                'phone' => '0533 000 00 0'.$i,
                'password' => 'gizli-parola',
                'role' => 'customer',
            ]);

            $order = $this->orderFor($user, 'delivered');

            Review::create([
                'product_id' => $this->product->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'name' => $user->name,
                'rating' => $rating,
                'body' => 'Yorum metni burada duruyor.',
                'status' => 'approved',
            ]);
        }

        $fresh = $this->product->fresh();
        $this->assertSame('4.00', (string) $fresh->rating);
        $this->assertSame(3, (int) $fresh->review_count);
    }

    // --- Gösterim ---------------------------------------------------------

    public function test_yazar_adi_kisaltilarak_gosterilir(): void
    {
        $order = $this->orderFor($this->customer, 'delivered');

        Review::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'name' => 'Selin Yılmaz',
            'rating' => 5,
            'body' => 'Yorum metni burada duruyor.',
            'status' => 'approved',
        ]);

        $this->get($this->product->url)
            ->assertSee('Selin Y.')
            ->assertDontSee('Selin Yılmaz');
    }

    public function test_dukkan_cevabi_yorumun_altinda_gosterilir(): void
    {
        $order = $this->orderFor($this->customer, 'delivered');

        Review::create([
            'product_id' => $this->product->id,
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
            'name' => 'Selin Yılmaz',
            'rating' => 5,
            'body' => 'Yorum metni burada duruyor.',
            'status' => 'approved',
            'reply' => 'Teşekkür ederiz, yine bekleriz.',
        ]);

        $this->get($this->product->url)->assertSee('Teşekkür ederiz, yine bekleriz.');
    }
}
