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
        // Masuk dengan email atau username (kolom name).
        foreach ([
            ['name' => 'superadmin', 'email' => 'superadmin@omahiot.example'],
            ['name' => 'admin', 'email' => 'admin@omahiot.example'],
        ] as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'password' => 'password', 'email_verified_at' => now()],
            );
        }
    }
}
