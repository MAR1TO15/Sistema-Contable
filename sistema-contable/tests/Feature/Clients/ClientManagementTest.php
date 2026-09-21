<?php

namespace Tests\Feature\Clients;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_de_firma_ve_el_listado_de_clientes_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);
        $client = Client::factory()->for($firm)->create();

        $response = $this->actingAs($admin)->get('/clients');

        $response->assertStatus(200);
        $response->assertSee($client->name);
    }

    public function test_admin_de_firma_no_ve_clientes_de_otra_firma_en_el_listado(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);

        $clientDeOtraFirma = Client::factory()->create();

        $response = $this->actingAs($admin)->get('/clients');

        $response->assertStatus(200);
        $response->assertDontSee($clientDeOtraFirma->name);
    }

    public function test_contador_no_puede_acceder_al_listado_de_gestion_de_clientes(): void
    {
        $contador = User::factory()->create(['role' => UserRole::Contador]);

        $response = $this->actingAs($contador)->get('/clients');

        $response->assertStatus(403);
    }

    public function test_admin_de_firma_puede_crear_un_cliente(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);

        $response = $this->actingAs($admin)->post('/clients', [
            'name' => 'Panaderia El Trigal SA',
            'tax_id' => '900123456',
        ]);

        $response->assertRedirect('/clients');
        $this->assertDatabaseHas('clients', [
            'name' => 'Panaderia El Trigal SA',
            'firm_id' => $firm->id,
        ]);
    }

    public function test_el_cliente_creado_siempre_pertenece_a_la_firma_del_admin_autenticado(): void
    {
        $firm = Firm::factory()->create();
        $otraFirma = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);

        $this->actingAs($admin)->post('/clients', [
            'name' => 'Cliente Nuevo',
            'firm_id' => $otraFirma->id,
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Nuevo',
            'firm_id' => $firm->id,
        ]);
        $this->assertDatabaseMissing('clients', [
            'name' => 'Cliente Nuevo',
            'firm_id' => $otraFirma->id,
        ]);
    }

    public function test_contador_no_puede_crear_clientes(): void
    {
        $contador = User::factory()->create(['role' => UserRole::Contador]);

        $response = $this->actingAs($contador)->post('/clients', [
            'name' => 'Cliente Cualquiera',
        ]);

        $response->assertStatus(403);
    }

    public function test_el_nombre_es_requerido_al_crear_un_cliente(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminFirma]);

        $response = $this->actingAs($admin)->post('/clients', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_de_firma_puede_editar_un_cliente_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);
        $client = Client::factory()->for($firm)->create(['name' => 'Nombre Viejo']);

        $response = $this->actingAs($admin)->put("/clients/{$client->id}", [
            'name' => 'Nombre Nuevo',
        ]);

        $response->assertRedirect('/clients');
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nombre Nuevo',
        ]);
    }

    public function test_admin_de_firma_no_puede_editar_un_cliente_de_otra_firma(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminFirma]);
        $clientDeOtraFirma = Client::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($admin)->put("/clients/{$clientDeOtraFirma->id}", [
            'name' => 'Hackeado',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('clients', [
            'id' => $clientDeOtraFirma->id,
            'name' => 'Original',
        ]);
    }

    public function test_admin_de_firma_puede_desactivar_y_reactivar_un_cliente(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);
        $client = Client::factory()->for($firm)->create(['is_active' => true]);

        $this->actingAs($admin)->patch("/clients/{$client->id}/toggle");
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'is_active' => false]);

        $this->actingAs($admin)->patch("/clients/{$client->id}/toggle");
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'is_active' => true]);
    }

    public function test_admin_de_firma_puede_asignar_contadores_a_un_cliente(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);
        $contador = User::factory()->for($firm)->create(['role' => UserRole::Contador]);
        $client = Client::factory()->for($firm)->create();

        $response = $this->actingAs($admin)->put("/clients/{$client->id}", [
            'name' => $client->name,
            'accountant_ids' => [$contador->id],
        ]);

        $response->assertRedirect('/clients');
        $this->assertTrue($client->fresh()->users()->whereKey($contador->id)->exists());
    }

    public function test_admin_de_firma_no_puede_asignar_un_contador_de_otra_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->create(['role' => UserRole::AdminFirma]);
        $client = Client::factory()->for($firm)->create();

        $contadorDeOtraFirma = User::factory()->create(['role' => UserRole::Contador]);

        $response = $this->actingAs($admin)->put("/clients/{$client->id}", [
            'name' => $client->name,
            'accountant_ids' => [$contadorDeOtraFirma->id],
        ]);

        $response->assertSessionHasErrors('accountant_ids.0');
        $this->assertFalse($client->fresh()->users()->whereKey($contadorDeOtraFirma->id)->exists());
    }
}
