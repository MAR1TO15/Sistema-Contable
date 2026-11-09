<?php

namespace Tests\Feature\Workspace;

use App\Models\Client;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActiveClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta de prueba que representa cualquier pantalla del módulo contable.
        Route::middleware(['web', 'auth', 'active.client'])
            ->get('/_prueba-contable', fn () => 'modulo contable')
            ->name('prueba.contable');
    }

    public function test_contador_ve_solo_sus_clientes_asignados(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $asignado = Client::factory()->for($firm)->create();
        $noAsignado = Client::factory()->for($firm)->create();
        $deOtraFirma = Client::factory()->create();
        $asignado->users()->attach($contador);
        $deOtraFirma->users()->attach($contador);

        $response = $this->actingAs($contador)->get('/workspace');

        $response->assertStatus(200);
        $response->assertSee($asignado->name);
        $response->assertDontSee($noAsignado->name);
        $response->assertDontSee($deOtraFirma->name);
    }

    public function test_admin_de_firma_ve_todos_los_clientes_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $clientes = Client::factory()->for($firm)->count(2)->create();
        $deOtraFirma = Client::factory()->create();

        $response = $this->actingAs($admin)->get('/workspace');

        $response->assertStatus(200);
        $response->assertSee($clientes[0]->name);
        $response->assertSee($clientes[1]->name);
        $response->assertDontSee($deOtraFirma->name);
    }

    public function test_los_clientes_inactivos_no_aparecen_en_el_listado(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $inactivo = Client::factory()->for($firm)->create(['is_active' => false]);

        $response = $this->actingAs($admin)->get('/workspace');

        $response->assertStatus(200);
        $response->assertDontSee($inactivo->name);
    }

    public function test_super_admin_no_puede_acceder_a_la_seleccion_de_empresa(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get('/workspace')->assertStatus(403);
    }

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get('/workspace')->assertRedirect('/login');
    }

    public function test_contador_puede_seleccionar_un_cliente_asignado(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $cliente = Client::factory()->for($firm)->create();
        $cliente->users()->attach($contador);

        $response = $this->actingAs($contador)->post("/workspace/{$cliente->id}");

        $response->assertRedirect('/');
        $response->assertSessionHas('active_client_id', $cliente->id);
    }

    public function test_admin_de_firma_puede_seleccionar_cualquier_cliente_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $cliente = Client::factory()->for($firm)->create();

        $response = $this->actingAs($admin)->post("/workspace/{$cliente->id}");

        $response->assertSessionHas('active_client_id', $cliente->id);
    }

    public function test_contador_no_puede_seleccionar_un_cliente_no_asignado(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $noAsignado = Client::factory()->for($firm)->create();

        $response = $this->actingAs($contador)->post("/workspace/{$noAsignado->id}");

        $response->assertStatus(403);
        $response->assertSessionMissing('active_client_id');
    }

    public function test_admin_de_firma_no_puede_seleccionar_un_cliente_de_otra_firma(): void
    {
        $admin = User::factory()->adminFirma()->create();
        $deOtraFirma = Client::factory()->create();

        $response = $this->actingAs($admin)->post("/workspace/{$deOtraFirma->id}");

        $response->assertStatus(403);
        $response->assertSessionMissing('active_client_id');
    }

    public function test_no_se_puede_seleccionar_un_cliente_inactivo(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $inactivo = Client::factory()->for($firm)->create(['is_active' => false]);

        $response = $this->actingAs($admin)->post("/workspace/{$inactivo->id}");

        $response->assertStatus(403);
        $response->assertSessionMissing('active_client_id');
    }

    public function test_las_rutas_contables_redirigen_a_la_seleccion_si_no_hay_cliente_activo(): void
    {
        $contador = User::factory()->create();

        $this->actingAs($contador)->get('/_prueba-contable')->assertRedirect('/workspace');
    }

    public function test_las_rutas_contables_responden_con_un_cliente_activo(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $cliente = Client::factory()->for($firm)->create();
        $cliente->users()->attach($contador);

        $response = $this->actingAs($contador)
            ->withSession(['active_client_id' => $cliente->id])
            ->get('/_prueba-contable');

        $response->assertStatus(200);
        $response->assertSee('modulo contable');
    }

    public function test_tras_seleccionar_un_cliente_se_vuelve_a_la_ruta_contable_solicitada(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $cliente = Client::factory()->for($firm)->create();
        $cliente->users()->attach($contador);

        $this->actingAs($contador)->get('/_prueba-contable')->assertRedirect('/workspace');

        $this->actingAs($contador)
            ->post("/workspace/{$cliente->id}")
            ->assertRedirect('/_prueba-contable');
    }

    public function test_si_el_contador_pierde_la_asignacion_se_limpia_el_cliente_activo(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $cliente = Client::factory()->for($firm)->create();
        $cliente->users()->attach($contador);

        $cliente->users()->detach($contador);

        $response = $this->actingAs($contador)
            ->withSession(['active_client_id' => $cliente->id])
            ->get('/_prueba-contable');

        $response->assertRedirect('/workspace');
        $response->assertSessionMissing('active_client_id');
    }

    public function test_si_el_cliente_activo_se_desactiva_se_limpia_de_la_sesion(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $cliente = Client::factory()->for($firm)->create(['is_active' => false]);

        $response = $this->actingAs($admin)
            ->withSession(['active_client_id' => $cliente->id])
            ->get('/_prueba-contable');

        $response->assertRedirect('/workspace');
        $response->assertSessionMissing('active_client_id');
    }

    public function test_el_inicio_muestra_el_cliente_activo_y_la_opcion_de_cambiarlo(): void
    {
        $firm = Firm::factory()->create();
        $contador = User::factory()->for($firm)->create();
        $cliente = Client::factory()->for($firm)->create(['name' => 'Panaderia El Trigal SA']);
        $cliente->users()->attach($contador);

        $response = $this->actingAs($contador)
            ->withSession(['active_client_id' => $cliente->id])
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('Panaderia El Trigal SA');
        $response->assertSee('Cambiar');
        $response->assertSee(route('workspace.index'));
    }

    public function test_el_inicio_ofrece_seleccionar_empresa_al_contador_sin_cliente_activo(): void
    {
        $contador = User::factory()->create();

        $response = $this->actingAs($contador)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Mis clientes');
        $response->assertSee(route('workspace.index'));
    }
}
