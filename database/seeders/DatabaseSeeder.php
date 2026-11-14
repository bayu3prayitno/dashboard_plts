<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun demo untuk halaman login (kata sandi: "password"). Jangan dipakai di produksi.
        // Untuk sementara hanya ada peran Super Admin. Masuk dengan email atau username.
        User::updateOrCreate(
            ['email' => 'superadmin@omahiot.example'],
            [
                'name' => 'Rina Wulandari',
                'username' => 'superadmin',
                'password' => 'password',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );
    }
}
