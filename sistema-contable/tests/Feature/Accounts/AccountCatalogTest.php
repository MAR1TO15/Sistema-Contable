<?php

namespace Tests\Feature\Accounts;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $contador;

    private Client $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->contador = User::factory()->for($firm)->create();
        $this->cliente = Client::factory()->for($firm)->create();
        $this->cliente->users()->attach($this->contador);
    }

    /**
     * Act as the accountant working on their assigned client.
     */
    private function trabajando(?User $user = null, ?Client $cliente = null): static
    {
        return $this->actingAs($user ?? $this->contador)
            ->withSession(['active_client_id' => ($cliente ?? $this->cliente)->id]);
    }

    public function test_sin_cliente_activo_el_catalogo_redirige_a_la_seleccion_de_empresa(): void
    {
        $this->actingAs($this->contador)->get('/accounts')->assertRedirect('/workspace');
    }

    public function test_el_catalogo_muestra_el_arbol_de_cuentas_en_orden(): void
    {
        $activo = Account::factory()->for($this->cliente)->group()->create(['code' => '1', 'name' => 'Activo']);
        $corriente = Account::factory()->childOf($activo)->group()->create(['code' => '11', 'name' => 'Activo corriente']);
        Account::factory()->childOf($corriente)->create(['code' => '1101', 'name' => 'Caja y bancos']);
        Account::factory()->for($this->cliente)->group()->create([
            'code' => '2',
            'name' => 'Pasivo',
            'type' => AccountType::Pasivo,
            'nature' => AccountNature::Acreedora,
        ]);

        $response = $this->trabajando()->get('/accounts');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Activo', 'Activo corriente', 'Caja y bancos', 'Pasivo']);
    }

    public function test_el_catalogo_no_muestra_cuentas_de_otro_cliente(): void
    {
        $otroCliente = Client::factory()->create();
        Account::factory()->for($otroCliente)->create(['name' => 'Cuenta Ajena']);

        $response = $this->trabajando()->get('/accounts');

        $response->assertStatus(200);
        $response->assertDontSee('Cuenta Ajena');
    }

    public function test_se_puede_crear_una_cuenta_raiz(): void
    {
        $response = $this->trabajando()->post('/accounts', [
            'code' => '1',
            'name' => 'Activo',
            'type' => AccountType::Activo->value,
            'nature' => AccountNature::Deudora->value,
        ]);

        $response->assertRedirect('/accounts');
        $this->assertDatabaseHas('accounts', [
            'client_id' => $this->cliente->id,
            'parent_id' => null,
            'code' => '1',
            'name' => 'Activo',
            'type' => 'activo',
            'nature' => 'deudora',
            'is_postable' => false,
        ]);
    }

    public function test_si_no_se_indica_la_naturaleza_se_usa_la_del_tipo(): void
    {
        $this->trabajando()->post('/accounts', [
            'code' => '4',
            'name' => 'Ingresos',
            'type' => AccountType::Ingreso->value,
        ]);

        $this->assertDatabaseHas('accounts', ['code' => '4', 'nature' => 'acreedora']);
    }

    public function test_la_cuenta_siempre_pertenece_al_cliente_activo(): void
    {
        $otroCliente = Client::factory()->create();

        $this->trabajando()->post('/accounts', [
            'code' => '1',
            'name' => 'Activo',
            'type' => AccountType::Activo->value,
            'client_id' => $otroCliente->id,
        ]);

        $this->assertDatabaseHas('accounts', ['code' => '1', 'client_id' => $this->cliente->id]);
        $this->assertDatabaseMissing('accounts', ['client_id' => $otroCliente->id]);
    }

    public function test_una_cuenta_hija_hereda_el_tipo_de_su_padre(): void
    {
        $activo = Account::factory()->for($this->cliente)->group()->create(['code' => '1']);

        $response = $this->trabajando()->post('/accounts', [
            'parent_id' => $activo->id,
            'code' => '1101',
            'name' => 'Caja',
            'type' => AccountType::Gasto->value,
            'is_postable' => '1',
        ]);

        $response->assertRedirect('/accounts');
        $this->assertDatabaseHas('accounts', [
            'code' => '1101',
            'parent_id' => $activo->id,
            'type' => 'activo',
            'is_postable' => true,
        ]);
    }

    public function test_una_cuenta_hija_puede_tener_naturaleza_contraria_a_su_tipo(): void
    {
        $activo = Account::factory()->for($this->cliente)->group()->create(['code' => '1']);

        $this->trabajando()->post('/accounts', [
            'parent_id' => $activo->id,
            'code' => '1299',
            'name' => 'Depreciación acumulada',
            'nature' => AccountNature::Acreedora->value,
            'is_postable' => '1',
        ]);

        $this->assertDatabaseHas('accounts', [
            'code' => '1299',
            'type' => 'activo',
            'nature' => 'acreedora',
        ]);
    }

    public function test_el_codigo_es_unico_dentro_del_mismo_cliente(): void
    {
        Account::factory()->for($this->cliente)->create(['code' => '1101']);

        $response = $this->trabajando()->post('/accounts', [
            'code' => '1101',
            'name' => 'Repetida',
            'type' => AccountType::Activo->value,
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, Account::where('code', '1101')->count());
    }

    public function test_el_mismo_codigo_se_puede_usar_en_otro_cliente(): void
    {
        Account::factory()->for(Client::factory())->create(['code' => '1101']);

        $response = $this->trabajando()->post('/accounts', [
            'code' => '1101',
            'name' => 'Caja',
            'type' => AccountType::Activo->value,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('accounts', ['code' => '1101', 'client_id' => $this->cliente->id]);
    }

    public function test_no_se_puede_usar_como_padre_una_cuenta_de_otro_cliente(): void
    {
        $padreAjeno = Account::factory()->for(Client::factory())->group()->create();

        $response = $this->trabajando()->post('/accounts', [
            'parent_id' => $padreAjeno->id,
            'code' => '1101',
            'name' => 'Caja',
        ]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('accounts', ['code' => '1101']);
    }

    public function test_no_se_puede_usar_como_padre_una_cuenta_imputable(): void
    {
        $imputable = Account::factory()->for($this->cliente)->create(['is_postable' => true]);

        $response = $this->trabajando()->post('/accounts', [
            'parent_id' => $imputable->id,
            'code' => '110101',
            'name' => 'Subcuenta',
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_una_cuenta_raiz_requiere_tipo(): void
    {
        $response = $this->trabajando()->post('/accounts', [
            'code' => '9',
            'name' => 'Sin tipo',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_se_puede_editar_una_cuenta_del_cliente_activo(): void
    {
        $cuenta = Account::factory()->for($this->cliente)->create(['code' => '1101', 'name' => 'Caja']);

        $response = $this->trabajando()->put("/accounts/{$cuenta->id}", [
            'code' => '1101',
            'name' => 'Caja general',
            'nature' => AccountNature::Deudora->value,
            'is_postable' => '1',
        ]);

        $response->assertRedirect('/accounts');
        $this->assertSame('Caja general', $cuenta->fresh()->name);
    }

    public function test_no_se_puede_editar_una_cuenta_de_otro_cliente(): void
    {
        $ajena = Account::factory()->for(Client::factory())->create(['name' => 'Original']);

        $this->trabajando()->get("/accounts/{$ajena->id}/edit")->assertStatus(404);

        $response = $this->trabajando()->put("/accounts/{$ajena->id}", [
            'code' => $ajena->code,
            'name' => 'Hackeada',
        ]);

        $response->assertStatus(404);
        $this->assertSame('Original', $ajena->fresh()->name);
    }

    public function test_no_se_puede_editar_una_cuenta_de_otro_cliente_asignado_al_mismo_contador(): void
    {
        $otroCliente = Client::factory()->for($this->cliente->firm)->create();
        $otroCliente->users()->attach($this->contador);
        $cuentaDelOtro = Account::factory()->for($otroCliente)->create(['name' => 'Original']);

        $response = $this->trabajando()->put("/accounts/{$cuentaDelOtro->id}", [
            'code' => $cuentaDelOtro->code,
            'name' => 'Cambiada',
        ]);

        $response->assertStatus(404);
        $this->assertSame('Original', $cuentaDelOtro->fresh()->name);
    }

    public function test_no_se_puede_marcar_como_imputable_una_cuenta_con_subcuentas(): void
    {
        $padre = Account::factory()->for($this->cliente)->group()->create(['code' => '1']);
        Account::factory()->childOf($padre)->create();

        $response = $this->trabajando()->put("/accounts/{$padre->id}", [
            'code' => '1',
            'name' => $padre->name,
            'is_postable' => '1',
        ]);

        $response->assertSessionHasErrors('is_postable');
        $this->assertFalse($padre->fresh()->is_postable);
    }

    public function test_se_puede_eliminar_una_cuenta_sin_subcuentas(): void
    {
        $cuenta = Account::factory()->for($this->cliente)->create();

        $response = $this->trabajando()->delete("/accounts/{$cuenta->id}");

        $response->assertRedirect('/accounts');
        $this->assertDatabaseMissing('accounts', ['id' => $cuenta->id]);
    }

    public function test_no_se_puede_eliminar_una_cuenta_con_subcuentas(): void
    {
        $padre = Account::factory()->for($this->cliente)->group()->create();
        Account::factory()->childOf($padre)->create();

        $response = $this->trabajando()->delete("/accounts/{$padre->id}");

        $response->assertRedirect('/accounts');
        $response->assertSessionHasErrors('account');
        $this->assertDatabaseHas('accounts', ['id' => $padre->id]);
    }

    public function test_no_se_puede_eliminar_una_cuenta_de_otro_cliente(): void
    {
        $ajena = Account::factory()->for(Client::factory())->create();

        $this->trabajando()->delete("/accounts/{$ajena->id}")->assertStatus(404);
        $this->assertDatabaseHas('accounts', ['id' => $ajena->id]);
    }

    public function test_el_admin_de_firma_tambien_gestiona_el_catalogo(): void
    {
        $admin = User::factory()->for($this->cliente->firm)->adminFirma()->create();

        $response = $this->trabajando($admin)->post('/accounts', [
            'code' => '1',
            'name' => 'Activo',
            'type' => AccountType::Activo->value,
        ]);

        $response->assertRedirect('/accounts');
        $this->assertDatabaseHas('accounts', ['code' => '1', 'client_id' => $this->cliente->id]);
    }

    public function test_se_puede_cargar_el_catalogo_base_en_un_cliente_sin_cuentas(): void
    {
        $response = $this->trabajando()->post('/accounts/base-catalog');

        $response->assertRedirect('/accounts');

        $raices = Account::where('client_id', $this->cliente->id)->whereNull('parent_id')->orderBy('code')->get();
        $this->assertSame(
            ['activo', 'pasivo', 'patrimonio', 'ingreso', 'gasto'],
            $raices->map(fn (Account $cuenta) => $cuenta->type->value)->all(),
        );
        $this->assertTrue(Account::where('client_id', $this->cliente->id)->where('is_postable', true)->exists());
    }

    public function test_las_subcuentas_del_catalogo_base_heredan_el_tipo_de_su_raiz(): void
    {
        $this->trabajando()->post('/accounts/base-catalog');

        $cuentas = Account::where('client_id', $this->cliente->id)->with('parent')->get();

        foreach ($cuentas->whereNotNull('parent_id') as $cuenta) {
            $this->assertSame($cuenta->parent->type, $cuenta->type, "La cuenta {$cuenta->code} no hereda el tipo de su padre");
            $this->assertFalse($cuenta->parent->is_postable, "El padre de {$cuenta->code} debería ser de agrupación");
        }
    }

    public function test_el_inicio_enlaza_al_catalogo_cuando_hay_cliente_activo(): void
    {
        $this->trabajando()->get('/')->assertSee(route('accounts.index'));
    }

    public function test_el_inicio_no_enlaza_al_catalogo_sin_cliente_activo(): void
    {
        $this->actingAs($this->contador)->get('/')->assertDontSee(route('accounts.index'));
    }

    public function test_no_se_carga_el_catalogo_base_si_el_cliente_ya_tiene_cuentas(): void
    {
        Account::factory()->for($this->cliente)->create();

        $response = $this->trabajando()->post('/accounts/base-catalog');

        $response->assertSessionHasErrors('account');
        $this->assertSame(1, Account::where('client_id', $this->cliente->id)->count());
    }
}
