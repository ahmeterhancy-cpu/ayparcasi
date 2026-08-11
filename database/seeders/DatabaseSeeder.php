<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Yalnız GELİŞTİRME içindir. Canlıda çalıştırmayın: DemoSeeder panelden
 * yapılan düzenlemeleri ezer, buradaki hesaplar da demo hesaplardır.
 *
 * Parolalar kaynak koda YAZILMAZ — depo herkese açık. Kendi parolanızı
 * .env içinde verin, vermezseniz rastgele üretilip ekrana basılır:
 *
 *   SEED_ADMIN_PASSWORD=...
 *   SEED_CUSTOMER_PASSWORD=...
 *
 * Canlıdaki yönetici hesabı bundan değil, `php artisan admin:olustur`
 * komutundan açılır (bkz. DEPLOY.md).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DatabaseSeeder canlıda çalıştırılmaz. Yönetici için: php artisan admin:olustur');

            return;
        }

        $adminPassword = $this->password('SEED_ADMIN_PASSWORD');
        $customerPassword = $this->password('SEED_CUSTOMER_PASSWORD');

        User::updateOrCreate(['email' => 'admin@ayparcasicicekci.com'], [
            'name' => 'Ay Parçası Yönetim',
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Hesabım bölümünü denemek için örnek müşteri
        User::updateOrCreate(['email' => 'musteri@ornek.com'], [
            'name' => 'Selin Yılmaz',
            'phone' => '0533 111 22 33',
            'password' => Hash::make($customerPassword),
            'role' => 'customer',
        ]);

        $this->command?->info('Panel : admin@ayparcasicicekci.com / '.$adminPassword);
        $this->command?->info('Müşteri: musteri@ornek.com / '.$customerPassword);

        $this->call(DemoSeeder::class);
    }

    private function password(string $key): string
    {
        return (string) (env($key) ?: Str::password(16, symbols: false));
    }
}
