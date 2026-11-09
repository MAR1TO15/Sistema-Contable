<?php

namespace Tests\Feature\Firms;

use App\Enums\UserRole;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirmManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_ve_el_listado_de_firmas(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $firm = Firm::factory()->create();

        $response = $this->actingAs($superAdmin)->get('/firms');

        $response->assertStatus(200);
        $response->assertSee($firm->name);
    }

    public function test_admin_de_firma_no_puede_acceder_al_listado_de_firmas(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminFirma]);

        $response = $this->actingAs($admin)->get('/firms');

        $response->assertStatus(403);
    }

    public function test_contador_no_puede_acceder_al_listado_de_firmas(): void
    {
        $contador = User::factory()->create(['role' => UserRole::Contador]);

        $response = $this->actingAs($contador)->get('/firms');

        $response->assertStatus(403);
    }

    public function test_super_admin_puede_crear_una_firma_con_su_administrador(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post('/firms', [
            'name' => 'Contadores Asociados SA',
            'tax_id' => '900555444',
            'admin_name' => 'Ana Admin',
            'admin_email' => 'ana@contadores.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/firms');

        $this->assertDatabaseHas('firms', [
            'name' => 'Contadores Asociados SA',
            'tax_id' => '900555444',
        ]);

        $firm = Firm::where('name', 'Contadores Asociados SA')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'ana@contadores.test',
            'firm_id' => $firm->id,
            'role' => UserRole::AdminFirma->value,
        ]);
    }

    public function test_admin_de_firma_no_puede_crear_firmas(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminFirma]);

        $response = $this->actingAs($admin)->post('/firms', [
            'name' => 'Firma Cualquiera',
            'admin_name' => 'X',
            'admin_email' => 'x@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_el_nombre_es_requerido_al_crear_una_firma(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->post('/firms', [
            'name' => '',
            'admin_name' => 'Ana',
            'admin_email' => 'ana@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_super_admin_puede_editar_una_firma(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $firm = Firm::factory()->create(['name' => 'Nombre Viejo']);

        $response = $this->actingAs($superAdmin)->put("/firms/{$firm->id}", [
            'name' => 'Nombre Nuevo',
        ]);

        $response->assertRedirect('/firms');
        $this->assertDatabaseHas('firms', [
            'id' => $firm->id,
            'name' => 'Nombre Nuevo',
        ]);
    }

    public function test_super_admin_puede_desactivar_y_reactivar_una_firma(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $firm = Firm::factory()->create(['is_active' => true]);

        $this->actingAs($superAdmin)->patch("/firms/{$firm->id}/toggle");
        $this->assertDatabaseHas('firms', ['id' => $firm->id, 'is_active' => false]);

        $this->actingAs($superAdmin)->patch("/firms/{$firm->id}/toggle");
        $this->assertDatabaseHas('firms', ['id' => $firm->id, 'is_active' => true]);
    }
}
