<?php

namespace Tests\Feature\MultiTenancy;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_de_firma_ve_todos_los_clientes_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);

        $clients = Client::factory()->for($firm)->count(3)->create();

        $visibles = Client::query()->visibleTo($admin)->get();

        $this->assertCount(3, $visibles);
        $this->assertEqualsCanonicalizing(
            $clients->pluck('id')->all(),
            $visibles->pluck('id')->all(),
        );
    }

    public function test_admin_de_firma_no_ve_clientes_de_otra_firma(): void
    {
        $firmA = Firm::factory()->create();
        $firmB = Firm::factory()->create();

        $admin = User::factory()->for($firmA)->create(['role' => UserRole::AdminFirma]);

        Client::factory()->for($firmA)->count(2)->create();
        Client::factory()->for($firmB)->count(5)->create();

        $visibles = Client::query()->visibleTo($admin)->get();

        $this->assertCount(2, $visibles);
        $this->assertTrue($visibles->every(fn (Client $client) => $client->firm_id === $firmA->id));
    }

    public function test_contador_solo_ve_los_clientes_asignados(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create(['role' => UserRole::Contador]);

        $asignado = Client::factory()->for($firm)->create();
        $noAsignado = Client::factory()->for($firm)->create();

        $asignado->users()->attach($contador);

        $visibles = Client::query()->visibleTo($contador)->get();

        $this->assertCount(1, $visibles);
        $this->assertTrue($visibles->contains('id', $asignado->id));
        $this->assertFalse($visibles->contains('id', $noAsignado->id));
    }

    public function test_contador_sin_clientes_asignados_no_ve_ninguno(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create(['role' => UserRole::Contador]);

        Client::factory()->for($firm)->count(4)->create();

        $visibles = Client::query()->visibleTo($contador)->get();

        $this->assertCount(0, $visibles);
    }

    public function test_contador_no_ve_clientes_asignados_de_otra_firma(): void
    {
        $firmA = Firm::factory()->create();
        $firmB = Firm::factory()->create();

        $contador = User::factory()->for($firmA)->create(['role' => UserRole::Contador]);

        $clientDeOtraFirma = Client::factory()->for($firmB)->create();
        $clientDeOtraFirma->users()->attach($contador);

        $visibles = Client::query()->visibleTo($contador)->get();

        $this->assertCount(0, $visibles);
    }
}
