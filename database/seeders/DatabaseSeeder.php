<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@omahiot.example'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => 'password',
                'role' => User::ROLE_SUPER_ADMIN,
                'is_active' => true,
            ],
        );

        $this->call(DummyDataSeeder::class);
    }
}
