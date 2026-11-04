<?php

namespace Tests\Feature\JournalEntries;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $contador;

    private Client $cliente;

    private FiscalPeriod $periodo;

    private Account $caja;

    private Account $ventas;

    private Account $grupo;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->contador = User::factory()->for($firm)->create();
        $this->cliente = Client::factory()->for($firm)->create();
        $this->cliente->users()->attach($this->contador);

        $this->periodo = FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->grupo = Account::factory()->for($this->cliente)->group()->create(['code' => '11', 'name' => 'Activo corriente']);
        $this->caja = Account::factory()->childOf($this->grupo)->create(['code' => '1101', 'name' => 'Caja y bancos']);
        $this->ventas = Account::factory()->for($this->cliente)->create([
            'code' => '4101',
            'name' => 'Ventas',
            'type' => AccountType::Ingreso,
            'nature' => AccountNature::Acreedora,
        ]);
    }

    /**
     * Act as the accountant working on their assigned client.
     */
    private function trabajando(?User $user = null): static
    {
        return $this->actingAs($user ?? $this->contador)
            ->withSession(['active_client_id' => $this->cliente->id]);
    }

    /**
     * A balanced entry: cash in, sales out.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function asientoValido(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-03-15',
            'description' => 'Venta de contado',
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '1500.00', 'credit' => '', 'description' => 'Cobro'],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '1500.00', 'description' => null],
            ],
        ], $overrides);
    }

    public function test_sin_cliente_activo_los_asientos_redirigen_a_la_seleccion_de_empresa(): void
    {
        $this->actingAs($this->contador)->get('/entries')->assertRedirect('/workspace');
    }

    public function test_se_registra_un_asiento_cuadrado(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido());

        $asiento = JournalEntry::firstOrFail();
        $response->assertRedirect("/entries/{$asiento->id}");

        $this->assertSame($this->cliente->id, $asiento->client_id);
        $this->assertSame($this->periodo->id, $asiento->fiscal_period_id);
        $this->assertSame($this->contador->id, $asiento->user_id);
        $this->assertSame(1, $asiento->number);
        $this->assertSame('2026-03-15', $asiento->date->toDateString());
        $this->assertSame('Venta de contado', $asiento->description);

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $asiento->id,
            'account_id' => $this->caja->id,
            'debit' => '1500.00',
            'credit' => '0.00',
            'description' => 'Cobro',
        ]);
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $asiento->id,
            'account_id' => $this->ventas->id,
            'debit' => '0.00',
            'credit' => '1500.00',
        ]);
    }

    public function test_el_asiento_queda_a_nombre_del_usuario_autenticado_y_del_cliente_activo(): void
    {
        $otroUsuario = User::factory()->create();
        $otroCliente = Client::factory()->create();

        $this->trabajando()->post('/entries', $this->asientoValido([
            'user_id' => $otroUsuario->id,
            'client_id' => $otroCliente->id,
            'number' => 99,
        ]));

        $asiento = JournalEntry::firstOrFail();
        $this->assertSame($this->contador->id, $asiento->user_id);
        $this->assertSame($this->cliente->id, $asiento->client_id);
        $this->assertSame(1, $asiento->number);
    }

    public function test_la_numeracion_es_correlativa_por_cliente(): void
    {
        $otroCliente = Client::factory()->create();
        JournalEntry::factory()->for($otroCliente)->create(['number' => 7]);

        $this->trabajando()->post('/entries', $this->asientoValido());
        $this->trabajando()->post('/entries', $this->asientoValido(['description' => 'Segunda venta']));

        $this->assertSame(
            [1, 2],
            JournalEntry::where('client_id', $this->cliente->id)->orderBy('number')->pluck('number')->all(),
        );
    }

    public function test_se_requieren_fecha_descripcion_y_lineas(): void
    {
        $response = $this->trabajando()->post('/entries', []);

        $response->assertSessionHasErrors(['date', 'description', 'lines']);
    }

    public function test_un_asiento_necesita_al_menos_dos_lineas(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '100.00', 'credit' => ''],
            ],
        ]));

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_una_linea_no_puede_tener_debito_y_credito_a_la_vez(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '100.00', 'credit' => '100.00'],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '100.00'],
                ['account_id' => $this->caja->id, 'debit' => '100.00', 'credit' => ''],
            ],
        ]));

        $response->assertSessionHasErrors('lines.0');
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_una_linea_con_cuenta_debe_tener_debito_o_credito(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '', 'credit' => ''],
                ['account_id' => $this->caja->id, 'debit' => '50.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '50.00'],
            ],
        ]));

        $response->assertSessionHasErrors('lines.0');
    }

    public function test_los_montos_deben_ser_mayores_que_cero(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '-100.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '0'],
            ],
        ]));

        $response->assertSessionHasErrors(['lines.0.debit', 'lines.1']);
    }

    public function test_los_montos_admiten_como_maximo_dos_decimales(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '10.555', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '10.555'],
            ],
        ]));

        $response->assertSessionHasErrors(['lines.0.debit', 'lines.1.credit']);
    }

    public function test_se_rechaza_un_asiento_descuadrado(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '1500.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '1499.99'],
            ],
        ]));

        $response->assertSessionHasErrors('lines');
        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_entry_lines', 0);
    }

    public function test_el_cuadre_es_exacto_con_decimales(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '0.10', 'credit' => ''],
                ['account_id' => $this->caja->id, 'debit' => '0.20', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '0.30'],
            ],
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('journal_entry_lines', 3);
    }

    public function test_no_se_puede_usar_una_cuenta_de_agrupacion(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->grupo->id, 'debit' => '100.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '100.00'],
            ],
        ]));

        $response->assertSessionHasErrors('lines.0.account_id');
    }

    public function test_no_se_puede_usar_una_cuenta_de_otro_cliente(): void
    {
        $cuentaAjena = Account::factory()->for(Client::factory())->create();

        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $cuentaAjena->id, 'debit' => '100.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '100.00'],
            ],
        ]));

        $response->assertSessionHasErrors('lines.0.account_id');
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_la_fecha_debe_estar_dentro_de_un_periodo(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido(['date' => '2027-01-05']));

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_la_fecha_no_puede_estar_en_un_periodo_cerrado(): void
    {
        FiscalPeriod::factory()->for($this->cliente)->closed()->create([
            'name' => 'Ejercicio 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        $response = $this->trabajando()->post('/entries', $this->asientoValido(['date' => '2025-06-10']));

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_el_periodo_de_otro_cliente_no_sirve(): void
    {
        $this->periodo->delete();
        FiscalPeriod::factory()->for(Client::factory())->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response = $this->trabajando()->post('/entries', $this->asientoValido());

        $response->assertSessionHasErrors('date');
    }

    public function test_las_filas_vacias_del_formulario_se_ignoran(): void
    {
        $response = $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '200.00', 'credit' => ''],
                ['account_id' => '', 'debit' => '', 'credit' => '', 'description' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '200.00'],
            ],
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('journal_entry_lines', 2);
    }

    public function test_el_admin_de_firma_tambien_registra_asientos(): void
    {
        $admin = User::factory()->for($this->cliente->firm)->adminFirma()->create();

        $this->trabajando($admin)->post('/entries', $this->asientoValido());

        $this->assertDatabaseHas('journal_entries', [
            'client_id' => $this->cliente->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_el_formulario_solo_ofrece_cuentas_imputables_del_cliente_activo(): void
    {
        Account::factory()->for(Client::factory())->create(['name' => 'Cuenta Ajena']);

        $response = $this->trabajando()->get('/entries/create');

        $response->assertStatus(200);
        $response->assertSee('1101');
        $response->assertSee('Caja y bancos');
        $response->assertDontSee('Activo corriente');
        $response->assertDontSee('Cuenta Ajena');
    }

    public function test_el_listado_muestra_los_asientos_del_cliente_activo(): void
    {
        JournalEntry::factory()->for($this->cliente)->create([
            'fiscal_period_id' => $this->periodo->id,
            'description' => 'Pago de alquiler',
        ]);
        JournalEntry::factory()->for(Client::factory())->create(['description' => 'Asiento Ajeno']);

        $response = $this->trabajando()->get('/entries');

        $response->assertStatus(200);
        $response->assertSee('Pago de alquiler');
        $response->assertDontSee('Asiento Ajeno');
    }

    public function test_el_detalle_muestra_las_lineas_y_los_totales(): void
    {
        $this->trabajando()->post('/entries', $this->asientoValido([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '1234.50', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '1234.50'],
            ],
        ]));
        $asiento = JournalEntry::firstOrFail();

        $response = $this->trabajando()->get("/entries/{$asiento->id}");

        $response->assertStatus(200);
        $response->assertSee('Venta de contado');
        $response->assertSeeInOrder(['Caja y bancos', 'Ventas']);
        $response->assertSee('1,234.50');
        $response->assertSee($this->contador->name);
    }

    public function test_no_se_puede_ver_un_asiento_de_otro_cliente(): void
    {
        $ajeno = JournalEntry::factory()->for(Client::factory())->create();

        $this->trabajando()->get("/entries/{$ajeno->id}")->assertStatus(404);
    }

    public function test_no_se_puede_eliminar_una_cuenta_con_movimientos(): void
    {
        $asiento = JournalEntry::factory()->for($this->cliente)->create(['fiscal_period_id' => $this->periodo->id]);
        JournalEntryLine::factory()->for($asiento, 'entry')->create(['account_id' => $this->caja->id]);

        $response = $this->trabajando()->delete("/accounts/{$this->caja->id}");

        $response->assertRedirect('/accounts');
        $response->assertSessionHasErrors('account');
        $this->assertDatabaseHas('accounts', ['id' => $this->caja->id]);
    }

    public function test_se_puede_eliminar_una_cuenta_sin_movimientos(): void
    {
        $this->trabajando()->delete("/accounts/{$this->ventas->id}")->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('accounts', ['id' => $this->ventas->id]);
    }

    public function test_el_inicio_enlaza_a_los_asientos_cuando_hay_cliente_activo(): void
    {
        $this->trabajando()->get('/')->assertSee(url('/entries'));
    }
}
