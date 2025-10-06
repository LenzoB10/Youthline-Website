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
        User::factory()->create([
            'name' => 'Promis Mphoola',
            'email' => 'promisemphoola2@gmail.com',
            'password' => Hash::make('1234567890'),
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Registrar',
            'email' => 'registrar@must.ac.mw',
            'password' => Hash::make('1234567890'),
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Chizinga',
            'email' => 'chizinga@must.ac.mw',
            'password' => Hash::make('1234567890'),
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Secretary',
            'email' => 'secretary@must.ac.mw',
            'password' => Hash::make('1234567890'),
            'status' => 'active',
        ]);

        User::factory()->create([
            'name' => 'Director of Finance',
            'email' => 'dof@must.ac.mw',
            'password' => Hash::make('1234567890'),
            'status' => 'active',
        ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            DepartmentSeeder::class,
            VehicleSeeder::class,
            DriverSeeder::class,
        ]);
    }
}
