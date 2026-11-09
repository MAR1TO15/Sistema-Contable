<?php

namespace Tests\Feature\Reports;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\User;
use App\Services\AccountBalances;
use App\Services\BalanceSheetService;
use App\Services\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Reports\Concerns\CreatesJournalEntries;
use Tests\TestCase;

class BalanceSheetTest extends TestCase
{
    use CreatesJournalEntries, RefreshDatabase;

    private User $contador;

    private Client $cliente;

    private FiscalPeriod $periodo;

    private Account $activo;

    private Account $corriente;

    private Account $caja;

    private Account $bancos;

    private Account $equipo;

    private Account $depreciacion;

    private Account $pasivo;

    private Account $proveedores;

    private Account $patrimonio;

    private Account $capital;

    private Account $utilidades;

    private Account $ventas;

    private Account $gastos;

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

        // 1 Activo > 11 Activo corriente > 1101 Caja, 1102 Bancos; 1 Activo > 1201 Equipo, 1299 Depreciación (contraria)
        $this->activo = Account::factory()->for($this->cliente)->group()->create(['code' => '1', 'name' => 'Activo']);
        $this->corriente = Account::factory()->childOf($this->activo)->group()->create(['code' => '11', 'name' => 'Activo corriente']);
        $this->caja = Account::factory()->childOf($this->corriente)->create(['code' => '1101', 'name' => 'Caja']);
        $this->bancos = Account::factory()->childOf($this->corriente)->create(['code' => '1102', 'name' => 'Bancos']);
        $this->equipo = Account::factory()->childOf($this->activo)->create(['code' => '1201', 'name' => 'Equipo']);
        $this->depreciacion = Account::factory()->childOf($this->activo)->create([
            'code' => '1299',
            'name' => 'Depreciación acumulada',
            'nature' => AccountNature::Acreedora,
        ]);

        $this->pasivo = $this->raiz('2', 'Pasivo', AccountType::Pasivo);
        $this->proveedores = Account::factory()->childOf($this->pasivo)->create(['code' => '2101', 'name' => 'Proveedores']);

        $this->patrimonio = $this->raiz('3', 'Patrimonio', AccountType::Patrimonio);
        $this->capital = Account::factory()->childOf($this->patrimonio)->create(['code' => '3101', 'name' => 'Capital social']);
        $this->utilidades = Account::factory()->childOf($this->patrimonio)->create(['code' => '3102', 'name' => 'Utilidades retenidas']);

        $this->ventas = Account::factory()->childOf($this->raiz('4', 'Ingresos', AccountType::Ingreso))
            ->create(['code' => '4101', 'name' => 'Ventas']);
        $this->gastos = Account::factory()->childOf($this->raiz('5', 'Gastos', AccountType::Gasto))
            ->create(['code' => '5101', 'name' => 'Sueldos']);
    }

    private function raiz(string $code, string $name, AccountType $type): Account
    {
        return Account::factory()->for($this->cliente)->group()->create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'nature' => $type->defaultNature(),
        ]);
    }

    private function trabajando(): static
    {
        return $this->actingAs($this->contador)
            ->withSession(['active_client_id' => $this->cliente->id]);
    }

    private function balance(string $corte = '2026-12-31')
    {
        return app(BalanceSheetService::class)->for($this->cliente, $corte);
    }

    /**
     * Capital 10,000 in cash; equipment 3,000 bought on credit; sales 2,000
     * to the bank; salaries 500 paid in cash. Profit of the year: 1,500.
     */
    private function operacionesDelAnio(): void
    {
        $this->asiento($this->periodo, '2026-01-02', [[$this->caja, '10000.00', '0'], [$this->capital, '0', '10000.00']], 'Aporte de capital');
        $this->asiento($this->periodo, '2026-02-10', [[$this->equipo, '3000.00', '0'], [$this->proveedores, '0', '3000.00']], 'Compra de equipo');
        $this->asiento($this->periodo, '2026-03-15', [[$this->bancos, '2000.00', '0'], [$this->ventas, '0', '2000.00']], 'Ventas');
        $this->asiento($this->periodo, '2026-04-30', [[$this->gastos, '500.00', '0'], [$this->caja, '0', '500.00']], 'Sueldos');
    }

    private function monto($seccion, Account $cuenta): ?int
    {
        return $seccion->firstWhere('account.id', $cuenta->id)?->amount;
    }

    // Saldos a una fecha de corte

    public function test_los_saldos_se_pueden_calcular_hasta_una_fecha_de_corte(): void
    {
        $this->asiento($this->periodo, '2026-03-01', [[$this->caja, '100.00', '0'], [$this->capital, '0', '100.00']]);
        $this->asiento($this->periodo, '2026-03-31', [[$this->caja, '40.00', '0'], [$this->capital, '0', '40.00']]);
        $this->asiento($this->periodo, '2026-04-01', [[$this->caja, '7.00', '0'], [$this->capital, '0', '7.00']]);

        $saldos = app(AccountBalances::class)->for($this->cliente, until: '2026-03-31');

        $this->assertSame(14000, $saldos->get($this->caja->id)->balance);
    }

    public function test_el_balance_excluye_los_asientos_posteriores_a_la_fecha_de_corte(): void
    {
        $this->operacionesDelAnio();

        $balance = $this->balance('2026-02-28');

        $this->assertSame(1300000, $balance->totalAssets);
        $this->assertSame(300000, $balance->totalLiabilities);
        $this->assertSame(0, $balance->periodResult);
    }

    // Ecuación contable

    public function test_el_balance_cumple_la_ecuacion_contable_incluyendo_el_resultado_del_ejercicio(): void
    {
        $this->operacionesDelAnio();

        $balance = $this->balance();

        $this->assertSame(1450000, $balance->totalAssets);
        $this->assertSame(300000, $balance->totalLiabilities);
        $this->assertSame(150000, $balance->periodResult);
        $this->assertSame(1150000, $balance->totalEquity);
        $this->assertSame(0, $balance->difference);
        $this->assertTrue($balance->isBalanced());
    }

    public function test_despues_del_cierre_la_utilidad_no_se_cuenta_dos_veces(): void
    {
        $this->operacionesDelAnio();
        app(PeriodClosing::class)->close($this->periodo, $this->utilidades, $this->contador);

        $balance = $this->balance();

        $this->assertSame(0, $balance->periodResult);
        $this->assertSame(150000, $this->monto($balance->equity, $this->utilidades));
        $this->assertSame(1150000, $balance->totalEquity);
        $this->assertTrue($balance->isBalanced());
    }

    public function test_detecta_cuando_la_ecuacion_no_cuadra(): void
    {
        // Asiento descuadrado cargado directamente, sin pasar por la validación.
        $this->asiento($this->periodo, '2026-01-02', [[$this->caja, '1000.00', '0'], [$this->capital, '0', '900.00']]);

        $balance = $this->balance();

        $this->assertFalse($balance->isBalanced());
        $this->assertSame(10000, $balance->difference);
    }

    // Agrupación y subtotales

    public function test_cada_cuenta_de_agrupacion_muestra_el_subtotal_de_sus_subcuentas(): void
    {
        $this->operacionesDelAnio();

        $activos = $this->balance()->assets;

        $this->assertSame(950000, $this->monto($activos, $this->caja));
        $this->assertSame(200000, $this->monto($activos, $this->bancos));
        $this->assertSame(1150000, $this->monto($activos, $this->corriente));
        $this->assertSame(1450000, $this->monto($activos, $this->activo));
    }

    public function test_una_cuenta_contraria_reduce_su_grupo(): void
    {
        $this->operacionesDelAnio();
        $this->asiento($this->periodo, '2026-12-31', [[$this->gastos, '600.00', '0'], [$this->depreciacion, '0', '600.00']], 'Depreciación');

        $balance = $this->balance();

        $this->assertSame(-60000, $this->monto($balance->assets, $this->depreciacion));
        $this->assertSame(1390000, $balance->totalAssets);
        $this->assertTrue($balance->isBalanced());
    }

    public function test_las_filas_siguen_la_jerarquia_del_catalogo_con_su_nivel(): void
    {
        $this->operacionesDelAnio();

        $filas = $this->balance()->assets
            ->map(fn ($fila) => [$fila->account->code, $fila->depth, $fila->isGroup])
            ->all();

        $this->assertSame([
            ['1', 0, true],
            ['11', 1, true],
            ['1101', 2, false],
            ['1102', 2, false],
            ['1201', 1, false],
        ], $filas);
    }

    public function test_solo_se_consideran_los_datos_de_la_empresa(): void
    {
        $periodoAjeno = FiscalPeriod::factory()->for(Client::factory())->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $cajaAjena = Account::factory()->for($periodoAjeno->client)->create();
        $capitalAjeno = Account::factory()->for($periodoAjeno->client)->create([
            'type' => AccountType::Patrimonio,
            'nature' => AccountNature::Acreedora,
        ]);
        $this->asiento($periodoAjeno, '2026-02-01', [[$cajaAjena, '999.00', '0'], [$capitalAjeno, '0', '999.00']]);

        $balance = $this->balance();

        $this->assertSame(0, $balance->totalAssets);
        $this->assertTrue($balance->assets->isEmpty());
    }

    // Vista

    public function test_sin_cliente_activo_redirige_a_la_seleccion_de_empresa(): void
    {
        $this->actingAs($this->contador)->get('/reports/balance-sheet')->assertRedirect('/workspace');
    }

    public function test_la_vista_muestra_las_secciones_subtotales_y_el_resultado_del_ejercicio(): void
    {
        $this->operacionesDelAnio();

        $response = $this->trabajando()->get('/reports/balance-sheet?date=2026-12-31');

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Activo', 'Activo corriente', 'Caja', '9,500.00', 'Bancos', '2,000.00', 'Equipo', '3,000.00', 'Total activo', '14,500.00',
            'Pasivo', 'Proveedores', '3,000.00', 'Total pasivo', '3,000.00',
            'Patrimonio', 'Capital social', '10,000.00', 'Resultado del ejercicio', '1,500.00', 'Total patrimonio', '11,500.00',
            'Total pasivo y patrimonio', '14,500.00',
        ]);
        $response->assertSee('Activo = Pasivo + Patrimonio');
    }

    public function test_por_defecto_la_fecha_de_corte_es_hoy(): void
    {
        Carbon::setTestNow('2026-02-28 10:00:00');
        $this->operacionesDelAnio();

        $response = $this->trabajando()->get('/reports/balance-sheet');

        $response->assertSee('2026-02-28');
        $response->assertSee('13,000.00');
        $response->assertDontSee('14,500.00');
    }

    public function test_la_vista_alerta_cuando_la_ecuacion_no_cuadra(): void
    {
        $this->asiento($this->periodo, '2026-01-02', [[$this->caja, '1000.00', '0'], [$this->capital, '0', '900.00']]);

        $response = $this->trabajando()->get('/reports/balance-sheet?date=2026-12-31');

        $response->assertSee('La ecuación contable no cuadra');
        $response->assertSee('100.00');
    }

    public function test_una_fecha_invalida_se_rechaza(): void
    {
        $response = $this->trabajando()->get('/reports/balance-sheet?date=31-12-2026');

        $response->assertSessionHasErrors('date');
    }

    public function test_el_inicio_enlaza_al_balance_general(): void
    {
        $this->trabajando()->get('/')->assertSee(url('/reports/balance-sheet'));
    }
}
