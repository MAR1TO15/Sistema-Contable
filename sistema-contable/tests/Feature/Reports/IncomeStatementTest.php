<?php

namespace Tests\Feature\Reports;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\User;
use App\Services\IncomeStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Reports\Concerns\CreatesJournalEntries;
use Tests\TestCase;

class IncomeStatementTest extends TestCase
{
    use CreatesJournalEntries, RefreshDatabase;

    private User $contador;

    private Client $cliente;

    private FiscalPeriod $periodo2025;

    private FiscalPeriod $periodo2026;

    private Account $caja;

    private Account $ventas;

    private Account $servicios;

    private Account $devoluciones;

    private Account $sueldos;

    private Account $alquiler;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->contador = User::factory()->for($firm)->create();
        $this->cliente = Client::factory()->for($firm)->create();
        $this->cliente->users()->attach($this->contador);

        $this->periodo2025 = FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $this->periodo2026 = FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->caja = Account::factory()->for($this->cliente)->create(['code' => '1101', 'name' => 'Caja y bancos']);

        $ingresos = Account::factory()->for($this->cliente)->group()->create([
            'code' => '4',
            'name' => 'Ingresos',
            'type' => AccountType::Ingreso,
            'nature' => AccountNature::Acreedora,
        ]);
        $this->ventas = Account::factory()->childOf($ingresos)->create(['code' => '4101', 'name' => 'Ventas']);
        $this->servicios = Account::factory()->childOf($ingresos)->create(['code' => '4102', 'name' => 'Servicios prestados']);
        $this->devoluciones = Account::factory()->childOf($ingresos)->create([
            'code' => '4199',
            'name' => 'Devoluciones sobre ventas',
            'nature' => AccountNature::Deudora,
        ]);

        $gastos = Account::factory()->for($this->cliente)->group()->create([
            'code' => '5',
            'name' => 'Gastos',
            'type' => AccountType::Gasto,
            'nature' => AccountNature::Deudora,
        ]);
        $this->sueldos = Account::factory()->childOf($gastos)->create(['code' => '5101', 'name' => 'Sueldos y salarios']);
        $this->alquiler = Account::factory()->childOf($gastos)->create(['code' => '5102', 'name' => 'Alquileres']);
    }

    private function trabajando(): static
    {
        return $this->actingAs($this->contador)
            ->withSession(['active_client_id' => $this->cliente->id]);
    }

    private function estado(?FiscalPeriod $periodo = null)
    {
        return app(IncomeStatementService::class)->for($this->cliente, $periodo ?? $this->periodo2026);
    }

    /**
     * Sales of 1,500.00 and expenses of 800.00 in 2026.
     */
    private function periodoConUtilidad(): void
    {
        $this->asiento($this->periodo2026, '2026-02-01', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']]);
        $this->asiento($this->periodo2026, '2026-02-15', [[$this->caja, '500.00', '0'], [$this->servicios, '0', '500.00']]);
        $this->asiento($this->periodo2026, '2026-03-01', [[$this->sueldos, '600.00', '0'], [$this->caja, '0', '600.00']]);
        $this->asiento($this->periodo2026, '2026-03-05', [[$this->alquiler, '200.00', '0'], [$this->caja, '0', '200.00']]);
    }

    // Cálculo

    public function test_calcula_ingresos_gastos_y_utilidad(): void
    {
        $this->periodoConUtilidad();

        $estado = $this->estado();

        $this->assertSame(150000, $estado->totalIncome);
        $this->assertSame(80000, $estado->totalExpenses);
        $this->assertSame(70000, $estado->result);
        $this->assertFalse($estado->isLoss());
    }

    public function test_calcula_una_perdida_cuando_los_gastos_superan_a_los_ingresos(): void
    {
        $this->asiento($this->periodo2026, '2026-02-01', [[$this->caja, '500.00', '0'], [$this->ventas, '0', '500.00']]);
        $this->asiento($this->periodo2026, '2026-03-01', [[$this->sueldos, '800.00', '0'], [$this->caja, '0', '800.00']]);

        $estado = $this->estado();

        $this->assertSame(-30000, $estado->result);
        $this->assertTrue($estado->isLoss());
    }

    public function test_un_periodo_sin_movimientos_da_todo_en_cero(): void
    {
        $estado = $this->estado();

        $this->assertSame(0, $estado->totalIncome);
        $this->assertSame(0, $estado->totalExpenses);
        $this->assertSame(0, $estado->result);
        $this->assertFalse($estado->isLoss());
    }

    public function test_el_detalle_incluye_cada_cuenta_imputable_de_ingreso_y_de_gasto_con_movimientos(): void
    {
        $this->periodoConUtilidad();

        $estado = $this->estado();

        $this->assertSame(['4101' => 100000, '4102' => 50000], $estado->income->mapWithKeys(fn ($linea) => [$linea->account->code => $linea->amount])->all());
        $this->assertSame(['5101' => 60000, '5102' => 20000], $estado->expenses->mapWithKeys(fn ($linea) => [$linea->account->code => $linea->amount])->all());
    }

    public function test_las_cuentas_de_balance_no_entran_en_el_estado_de_resultados(): void
    {
        $this->periodoConUtilidad();

        $codigos = $this->estado()->income->merge($this->estado()->expenses)->map(fn ($linea) => $linea->account->code);

        $this->assertNotContains('1101', $codigos);
    }

    public function test_una_cuenta_contraria_de_ingreso_reduce_los_ingresos(): void
    {
        $this->periodoConUtilidad();
        $this->asiento($this->periodo2026, '2026-04-01', [[$this->devoluciones, '100.00', '0'], [$this->caja, '0', '100.00']]);

        $estado = $this->estado();

        $this->assertSame(140000, $estado->totalIncome);
        $this->assertSame(-10000, $estado->income->firstWhere('account.code', '4199')->amount);
        $this->assertSame(60000, $estado->result);
    }

    public function test_solo_se_consideran_los_asientos_del_periodo(): void
    {
        $this->asiento($this->periodo2025, '2025-06-01', [[$this->caja, '900.00', '0'], [$this->ventas, '0', '900.00']]);
        $this->periodoConUtilidad();

        $this->assertSame(150000, $this->estado($this->periodo2026)->totalIncome);
        $this->assertSame(90000, $this->estado($this->periodo2025)->totalIncome);
    }

    public function test_solo_se_consideran_los_asientos_de_la_empresa(): void
    {
        $periodoAjeno = FiscalPeriod::factory()->for(Client::factory())->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $ventasAjenas = Account::factory()->for($periodoAjeno->client)->create([
            'type' => AccountType::Ingreso,
            'nature' => AccountNature::Acreedora,
        ]);
        $cajaAjena = Account::factory()->for($periodoAjeno->client)->create();
        $this->asiento($periodoAjeno, '2026-02-01', [[$cajaAjena, '999.00', '0'], [$ventasAjenas, '0', '999.00']]);

        $this->assertSame(0, $this->estado()->totalIncome);
    }

    // Vista

    public function test_sin_cliente_activo_redirige_a_la_seleccion_de_empresa(): void
    {
        $this->actingAs($this->contador)->get('/reports/income-statement')->assertRedirect('/workspace');
    }

    public function test_la_vista_muestra_el_detalle_y_la_utilidad(): void
    {
        $this->periodoConUtilidad();

        $response = $this->trabajando()->get("/reports/income-statement?period={$this->periodo2026->id}");

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Ingresos', 'Ventas', '1,000.00', 'Servicios prestados', '500.00', 'Total ingresos', '1,500.00',
            'Gastos', 'Sueldos y salarios', '600.00', 'Alquileres', '200.00', 'Total gastos', '800.00',
            'Utilidad del periodo', '700.00',
        ]);
    }

    public function test_la_vista_indica_claramente_una_perdida(): void
    {
        $this->asiento($this->periodo2026, '2026-02-01', [[$this->caja, '500.00', '0'], [$this->ventas, '0', '500.00']]);
        $this->asiento($this->periodo2026, '2026-03-01', [[$this->sueldos, '800.00', '0'], [$this->caja, '0', '800.00']]);

        $response = $this->trabajando()->get("/reports/income-statement?period={$this->periodo2026->id}");

        $response->assertSeeInOrder(['Pérdida del periodo', '300.00']);
        $response->assertDontSee('Utilidad del periodo');
    }

    public function test_por_defecto_se_muestra_el_periodo_mas_reciente(): void
    {
        $this->asiento($this->periodo2025, '2025-06-01', [[$this->caja, '900.00', '0'], [$this->ventas, '0', '900.00']]);
        $this->periodoConUtilidad();

        $response = $this->trabajando()->get('/reports/income-statement');

        $response->assertStatus(200);
        $response->assertSee('1,500.00');
        $response->assertDontSee('900.00');
    }

    public function test_se_puede_elegir_otro_periodo(): void
    {
        $this->asiento($this->periodo2025, '2025-06-01', [[$this->caja, '900.00', '0'], [$this->ventas, '0', '900.00']]);
        $this->periodoConUtilidad();

        $response = $this->trabajando()->get("/reports/income-statement?period={$this->periodo2025->id}");

        $response->assertSeeInOrder(['Total ingresos', '900.00']);
        $response->assertDontSee('1,500.00');
    }

    public function test_sin_periodos_la_vista_invita_a_crear_uno(): void
    {
        $this->periodo2025->delete();
        $this->periodo2026->delete();

        $response = $this->trabajando()->get('/reports/income-statement');

        $response->assertStatus(200);
        $response->assertSee(route('periods.create'));
    }

    public function test_no_se_puede_elegir_un_periodo_de_otro_cliente(): void
    {
        $periodoAjeno = FiscalPeriod::factory()->for(Client::factory())->create();

        $this->trabajando()->get("/reports/income-statement?period={$periodoAjeno->id}")->assertStatus(404);
    }

    public function test_el_inicio_enlaza_al_estado_de_resultados(): void
    {
        $this->trabajando()->get('/')->assertSee(url('/reports/income-statement'));
    }
}
