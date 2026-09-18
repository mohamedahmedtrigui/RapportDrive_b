<?php

namespace Database\Seeders;

use App\Models\Dispatcher;
use App\Models\User;
use App\Models\Zone;
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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@rapportdrive.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@rapportdrive.test',
            'password' => 'password',
            'role' => 'manager',
        ]);

        Dispatcher::factory()->create([
            'nom' => 'Dispatcher',
            'email' => 'dispatcher@rapportdrive.test',
            'ville_affectee' => 'Tunis',
            'password' => 'password',
        ]);

        $zones = [
            'Ariana',
            'Ben Arous',
            'Bizerte',
            'Gabes',
            'Manouba',
            'Monastir',
            'Nabeul & Hammamet',
            'Sfax',
            'Sousse',
            'Tunis',
            'Mahdia',
            'Djerba',
        ];

        foreach ($zones as $nom) {
            Zone::factory()->create(['nom' => $nom]);
        }
    }
}
