<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    private function closeShop(): void
    {
        Setting::put('maintenance_enabled', '1');
    }

    public function test_ayar_kapaliyken_vitrin_normal_acilir(): void
    {
        $this->get('/')->assertOk();
        $this->get('/magaza')->assertOk();
    }

    public function test_kapali_ayar_bos_dizeyle_de_kapali_sayilir(): void
    {
        Setting::put('maintenance_enabled', '0');

        $this->get('/')->assertOk();
    }

    public function test_acikken_vitrin_perde_sayfasini_gosterir(): void
    {
        Setting::put('maintenance_title', 'Vitrinimizi yeniliyoruz');
        $this->closeShop();

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After')
            ->assertSee('Yapım aşamasında')
            ->assertSee('Vitrinimizi yeniliyoruz');

        $this->get('/magaza')->assertStatus(503);
        $this->get('/iletisim')->assertStatus(503);
    }

    public function test_perde_onbellege_alinmaz(): void
    {
        $this->closeShop();

        // Sunucu ya da tarayıcı perdeyi saklarsa, ekip girişi yapıldıktan
        // sonra bile saklanmış kapalı sayfa dönüyor.
        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private')
            ->assertHeader('X-LiteSpeed-Cache-Control', 'no-cache');
    }

    public function test_ekip_hesabi_kapali_siteyi_gezebilir(): void
    {
        $this->closeShop();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/')
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get('/magaza')
            ->assertOk();
    }

    public function test_musteri_hesabi_kapali_siteyi_goremez(): void
    {
        $this->closeShop();

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get('/')
            ->assertStatus(503);
    }

    public function test_panel_kapaliyken_uyari_seridini_gosterir(): void
    {
        $this->closeShop();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/site-settings')
            ->assertOk()
            ->assertSee('ziyaretçiler vitrini göremiyor', false)
            ->assertSee('Site şu anda ziyaretçilere kapalı');
    }

    public function test_panel_ve_odeme_yollari_perdeden_etkilenmez(): void
    {
        $this->closeShop();

        // Panel girişi açık kalır
        $this->get('/admin/login')->assertOk();

        // Süren ödemenin bildirimi düşmemeli: perde 503 döndürmez,
        // istek gerçek denetleyiciye ulaşır (imzasız veriyle reddedilir).
        $this->post('/odeme/bildirim', [])->assertStatus(400);
    }
}
