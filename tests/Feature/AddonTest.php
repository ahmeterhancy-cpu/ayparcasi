<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddonTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);

        $this->product = Product::active()->firstOrFail();
    }

    public function test_urun_sayfasi_yalnizca_kendi_ek_urunlerini_gosterir(): void
    {
        $kept = Addon::where('name', 'Cam vazo')->firstOrFail();
        $dropped = Addon::where('name', 'Peluş ayıcık')->firstOrFail();

        $this->product->addons()->sync([$kept->id]);

        $this->get('/urun/'.$this->product->slug)
            ->assertOk()
            ->assertSee($kept->name)
            ->assertDontSee($dropped->name);
    }

    public function test_hic_ek_urun_secilmemisse_bolum_gorunmez(): void
    {
        $this->product->addons()->detach();

        $this->get('/urun/'.$this->product->slug)
            ->assertOk()
            ->assertDontSee('Yanına ekleyin');
    }

    public function test_pasif_ek_urun_vitrinde_cikmaz(): void
    {
        $addon = Addon::where('name', 'Cam vazo')->firstOrFail();
        $this->product->addons()->sync([$addon->id]);
        $addon->update(['is_active' => false]);

        $this->get('/urun/'.$this->product->slug)
            ->assertOk()
            ->assertDontSee($addon->name);
    }

    public function test_panelde_urun_formu_ek_urun_seciciyi_gosterir(): void
    {
        $addon = Addon::where('name', 'Cam vazo')->firstOrFail();
        $this->product->addons()->sync([$addon->id]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/products/'.$this->product->id.'/edit')
            ->assertOk()
            ->assertSee('Yanına ekleyin')
            // Etiket adı ve fiyatı birlikte yazar
            ->assertSee($addon->name.' — '.money($addon->price));
    }

    public function test_urune_secilmemis_ek_urun_sepete_giremez(): void
    {
        $mine = Addon::where('name', 'Cam vazo')->firstOrFail();
        $foreign = Addon::where('name', 'Uçan balon')->firstOrFail();

        $this->product->addons()->sync([$mine->id]);

        $this->post('/sepet', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'addons' => [$mine->id, $foreign->id],
        ])->assertRedirect(route('cart.index'));

        $line = collect(session('cart'))->first();

        $this->assertSame([$mine->id], $line['addons']);
    }

    public function test_pasif_ek_urun_sepete_giremez(): void
    {
        $addon = Addon::where('name', 'Cam vazo')->firstOrFail();
        $this->product->addons()->sync([$addon->id]);
        $addon->update(['is_active' => false]);

        $this->post('/sepet', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'addons' => [$addon->id],
        ])->assertRedirect(route('cart.index'));

        $line = collect(session('cart'))->first();

        $this->assertSame([], $line['addons']);
    }
}
