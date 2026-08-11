<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_bilgi_yoksa_hesap_acmaz_ama_deployu_kirmaz(): void
    {
        $this->artisan('admin:olustur')->assertSuccessful();

        $this->assertSame(0, User::where('role', 'admin')->count());
    }

    public function test_yonetici_acar(): void
    {
        $this->artisan('admin:olustur', [
            '--eposta' => 'patron@ornek.com',
            '--parola' => 'cok-uzun-bir-parola',
            '--ad' => 'Patron',
        ])->assertSuccessful();

        $user = User::where('email', 'patron@ornek.com')->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertTrue(\Hash::check('cok-uzun-bir-parola', $user->password));
    }

    public function test_mevcut_hesabin_parolasini_ezmez(): void
    {
        User::create([
            'name' => 'Patron',
            'email' => 'patron@ornek.com',
            'password' => 'eski-parola-uzun',
            'role' => 'admin',
        ]);

        $this->artisan('admin:olustur', [
            '--eposta' => 'patron@ornek.com',
            '--parola' => 'yeni-parola-uzun',
        ])->assertSuccessful();

        $this->assertTrue(
            \Hash::check('eski-parola-uzun', User::where('email', 'patron@ornek.com')->value('password'))
        );
    }

    public function test_kisa_parolayi_reddeder(): void
    {
        $this->artisan('admin:olustur', [
            '--eposta' => 'patron@ornek.com',
            '--parola' => '1234',
        ])->assertFailed();

        $this->assertSame(0, User::where('email', 'patron@ornek.com')->count());
    }
}
