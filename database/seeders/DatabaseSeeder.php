<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@ayparcasicicekci.com'], [
            'name' => 'Ay Parçası Yönetim',
            'password' => Hash::make('ayparcasi2026'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Hesabım bölümünü denemek için örnek müşteri
        User::updateOrCreate(['email' => 'musteri@ornek.com'], [
            'name' => 'Selin Yılmaz',
            'phone' => '0533 111 22 33',
            'password' => Hash::make('musteri2026'),
            'role' => 'customer',
        ]);

        $this->call(DemoSeeder::class);
    }
}
