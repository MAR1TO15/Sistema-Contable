<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Firm;
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
        User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
        ]);

        $firmaUno = Firm::factory()->create([
            'name' => 'Contadores Pro SAS',
            'tax_id' => '900111222',
        ]);

        $adminFirmaUno = User::factory()->for($firmaUno)->create([
            'name' => 'Admin Firma Uno',
            'email' => 'admin@example.com',
            'role' => UserRole::AdminFirma,
        ]);

        $contadorUno = User::factory()->for($firmaUno)->create([
            'name' => 'Carlos Contador',
            'email' => 'contador1@example.com',
            'role' => UserRole::Contador,
        ]);

        $contadorDos = User::factory()->for($firmaUno)->create([
            'name' => 'Carla Contadora',
            'email' => 'contador2@example.com',
            'role' => UserRole::Contador,
        ]);

        $clientesFirmaUno = Client::factory()
            ->for($firmaUno)
            ->count(3)
            ->sequence(
                ['name' => 'Panaderia El Trigal SA', 'is_active' => true],
                ['name' => 'Ferreteria Central Ltda', 'is_active' => true],
                ['name' => 'Transportes Rapido SAS', 'is_active' => false],
            )
            ->create();

        $clientesFirmaUno[0]->users()->attach($contadorUno);
        $clientesFirmaUno[1]->users()->attach([$contadorUno->id, $contadorDos->id]);

        $firmaDos = Firm::factory()->create([
            'name' => 'Asesores Andinos SA',
            'tax_id' => '900333444',
        ]);

        User::factory()->for($firmaDos)->create([
            'name' => 'Admin Firma Dos',
            'email' => 'admin2@example.com',
            'role' => UserRole::AdminFirma,
        ]);

        $contadorFirmaDos = User::factory()->for($firmaDos)->create([
            'name' => 'David Contador',
            'email' => 'contador3@example.com',
            'role' => UserRole::Contador,
        ]);

        $clienteFirmaDos = Client::factory()->for($firmaDos)->create([
            'name' => 'Textiles del Sur SA',
        ]);

        $clienteFirmaDos->users()->attach($contadorFirmaDos);
    }
}
