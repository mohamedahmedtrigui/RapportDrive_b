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
        User::firstOrCreate(
            ['email' => 'admin@rapportdrive.test'],
            User::factory()->raw([
                'name' => 'Admin',
                'email' => 'admin@rapportdrive.test',
                'password' => 'password',
                'role' => 'admin',
            ]),
        );

        User::firstOrCreate(
            ['email' => 'manager@rapportdrive.test'],
            User::factory()->raw([
                'name' => 'Manager',
                'email' => 'manager@rapportdrive.test',
                'password' => 'password',
                'role' => 'manager',
            ]),
        );

        Dispatcher::firstOrCreate(
            ['email' => 'dispatcher@rapportdrive.test'],
            Dispatcher::factory()->raw([
                'nom' => 'Dispatcher',
                'email' => 'dispatcher@rapportdrive.test',
                'ville_affectee' => 'Tunis',
                'password' => 'password',
            ]),
        );

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
            Zone::firstOrCreate(['nom' => $nom], Zone::factory()->raw(['nom' => $nom]));
        }
    }
}
