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

        $this->call(DemoSeeder::class);
    }
}
