<?php

namespace Tests\Feature\Reports;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\FiscalPeriod;
use App\Services\AccountBalances;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Reports\Concerns\CreatesJournalEntries;
use Tests\TestCase;

class AccountBalancesTest extends TestCase
{
    use CreatesJournalEntries, RefreshDatabase;

    private Client $cliente;

    private FiscalPeriod $periodo;

    private Account $activo;

    private Account $corriente;

    private Account $caja;

    private Account $bancos;

    private Account $equipo;

    private Account $depreciacion;

    private Account $ventas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = Client::factory()->create();
        $this->periodo = FiscalPeriod::factory()->for($this->cliente)->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        // 1 Activo > 11 Corriente > 1101 Caja, 1102 Bancos; 1 Activo > 1201 Equipo, 1299 Depreciación (contraria)
        $this->activo = Account::factory()->for($this->cliente)->group()->create(['code' => '1']);
        $this->corriente = Account::factory()->childOf($this->activo)->group()->create(['code' => '11']);
        $this->caja = Account::factory()->childOf($this->corriente)->create(['code' => '1101']);
        $this->bancos = Account::factory()->childOf($this->corriente)->create(['code' => '1102']);
        $this->equipo = Account::factory()->childOf($this->activo)->create(['code' => '1201']);
        $this->depreciacion = Account::factory()->childOf($this->activo)->create([
            'code' => '1299',
            'nature' => AccountNature::Acreedora,
        ]);
        $this->ventas = Account::factory()->for($this->cliente)->create([
            'code' => '4101',
            'type' => AccountType::Ingreso,
            'nature' => AccountNature::Acreedora,
        ]);
    }

    private function saldos(?FiscalPeriod $periodo = null)
    {
        return app(AccountBalances::class)->for($this->cliente, $periodo);
    }

    public function test_el_saldo_de_una_cuenta_deudora_es_debitos_menos_creditos(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']]);
        $this->asiento($this->periodo, '2026-02-02', [[$this->ventas, '250.00', '0'], [$this->caja, '0', '250.00']]);

        $caja = $this->saldos()->get($this->caja->id);

        $this->assertSame(100000, $caja->debit);
        $this->assertSame(25000, $caja->credit);
        $this->assertSame(75000, $caja->balance);
    }

    public function test_el_saldo_de_una_cuenta_acreedora_es_creditos_menos_debitos(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']]);
        $this->asiento($this->periodo, '2026-02-02', [[$this->ventas, '250.00', '0'], [$this->caja, '0', '250.00']]);

        $this->assertSame(75000, $this->saldos()->get($this->ventas->id)->balance);
    }

    public function test_una_cuenta_sin_movimientos_tiene_saldo_cero(): void
    {
        $bancos = $this->saldos()->get($this->bancos->id);

        $this->assertSame(0, $bancos->debit);
        $this->assertSame(0, $bancos->credit);
        $this->assertSame(0, $bancos->balance);
    }

    public function test_una_cuenta_de_agrupacion_suma_los_saldos_de_sus_subcuentas(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [
            [$this->caja, '300.00', '0'],
            [$this->bancos, '700.50', '0'],
            [$this->equipo, '2000.00', '0'],
            [$this->ventas, '0', '3000.50'],
        ]);

        $saldos = $this->saldos();

        $this->assertSame(100050, $saldos->get($this->corriente->id)->balance);
        $this->assertSame(300050, $saldos->get($this->activo->id)->balance);
    }

    public function test_una_cuenta_contraria_reduce_el_saldo_de_su_cuenta_padre(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [[$this->equipo, '2000.00', '0'], [$this->ventas, '0', '2000.00']]);
        $this->asiento($this->periodo, '2026-12-31', [[$this->ventas, '400.00', '0'], [$this->depreciacion, '0', '400.00']]);

        $saldos = $this->saldos();

        $this->assertSame(40000, $saldos->get($this->depreciacion->id)->balance);
        $this->assertSame(160000, $saldos->get($this->activo->id)->balance);
    }

    public function test_los_saldos_se_pueden_filtrar_por_periodo(): void
    {
        $anterior = FiscalPeriod::factory()->for($this->cliente)->create([
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $this->asiento($anterior, '2025-05-01', [[$this->caja, '500.00', '0'], [$this->ventas, '0', '500.00']]);
        $this->asiento($this->periodo, '2026-05-01', [[$this->caja, '120.00', '0'], [$this->ventas, '0', '120.00']]);

        $this->assertSame(12000, $this->saldos($this->periodo)->get($this->caja->id)->balance);
        $this->assertSame(50000, $this->saldos($anterior)->get($this->caja->id)->balance);
        $this->assertSame(62000, $this->saldos()->get($this->caja->id)->balance);
    }

    public function test_solo_se_consideran_los_asientos_del_cliente(): void
    {
        $otroPeriodo = FiscalPeriod::factory()->for(Client::factory())->create();
        $cuentaAjena = Account::factory()->for($otroPeriodo->client)->create();
        $this->asiento($otroPeriodo, $otroPeriodo->start_date->toDateString(), [[$cuentaAjena, '999.00', '0'], [$cuentaAjena, '0', '999.00']]);

        $saldos = $this->saldos();

        $this->assertFalse($saldos->has($cuentaAjena->id));
        $this->assertSame(0, $saldos->sum('debit'));
    }

    public function test_los_saldos_vienen_ordenados_por_codigo(): void
    {
        $this->assertSame(
            ['1', '11', '1101', '1102', '1201', '1299', '4101'],
            $this->saldos()->map(fn ($saldo) => $saldo->account->code)->values()->all(),
        );
    }

    public function test_las_sumas_con_centavos_son_exactas(): void
    {
        $this->asiento($this->periodo, '2026-03-01', [
            [$this->caja, '0.10', '0'],
            [$this->caja, '0.20', '0'],
            [$this->ventas, '0', '0.30'],
        ]);

        $this->assertSame(30, $this->saldos()->get($this->caja->id)->balance);
    }
}
