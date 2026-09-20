<?php

namespace Tests\Feature\MultiTenancy;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_de_firma_puede_ver_un_cliente_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);
        $client = Client::factory()->for($firm)->create();

        $this->assertTrue($admin->can('view', $client));
    }

    public function test_admin_de_firma_no_puede_ver_un_cliente_de_otra_firma(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminFirma]);
        $clientDeOtraFirma = Client::factory()->create();

        $this->assertFalse($admin->can('view', $clientDeOtraFirma));
    }

    public function test_contador_puede_ver_un_cliente_asignado(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create(['role' => UserRole::Contador]);
        $client = Client::factory()->for($firm)->create();
        $client->users()->attach($contador);

        $this->assertTrue($contador->can('view', $client));
    }

    public function test_contador_no_puede_ver_un_cliente_no_asignado_de_su_misma_firma(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create(['role' => UserRole::Contador]);
        $client = Client::factory()->for($firm)->create();

        $this->assertFalse($contador->can('view', $client));
    }
}
