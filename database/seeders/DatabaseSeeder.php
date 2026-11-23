<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Passwords never live in the code: they come from ADMIN_PASSWORD / MANAGER_PASSWORD,
        // or a strong random one is generated and printed once (see YouthlineSeeder::password()).
        User::factory()->create([
            'name' => 'Promis Mphoola',
            'email' => env('ADMIN_EMAIL', 'lenabekelesi@gmail.com'),
            'password' => Hash::make(YouthlineSeeder::password('ADMIN_PASSWORD', 'Admin', $this->command)),
            'status' => 'active',
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            YouthlineSeeder::class,
        ]);
    }
}
