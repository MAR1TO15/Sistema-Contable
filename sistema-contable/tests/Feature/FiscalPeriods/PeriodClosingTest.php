<?php

namespace Tests\Feature\FiscalPeriods;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\PeriodStatus;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountBalances;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Reports\Concerns\CreatesJournalEntries;
use Tests\TestCase;

class PeriodClosingTest extends TestCase
{
    use CreatesJournalEntries, RefreshDatabase;

    private User $admin;

    private User $contador;

    private Client $cliente;

    private FiscalPeriod $periodo;

    private Account $caja;

    private Account $ventas;

    private Account $sueldos;

    private Account $utilidades;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->admin = User::factory()->for($firm)->adminFirma()->create(['name' => 'Ana Admin']);
        $this->contador = User::factory()->for($firm)->create();
        $this->cliente = Client::factory()->for($firm)->create();
        $this->cliente->users()->attach($this->contador);

        $this->periodo = FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->caja = Account::factory()->for($this->cliente)->create(['code' => '1101', 'name' => 'Caja y bancos']);
        $this->utilidades = Account::factory()->for($this->cliente)->create([
            'code' => '3102',
            'name' => 'Utilidades retenidas',
            'type' => AccountType::Patrimonio,
            'nature' => AccountNature::Acreedora,
        ]);
        $this->ventas = Account::factory()->for($this->cliente)->create([
            'code' => '4101',
            'name' => 'Ventas',
            'type' => AccountType::Ingreso,
            'nature' => AccountNature::Acreedora,
        ]);
        $this->sueldos = Account::factory()->for($this->cliente)->create([
            'code' => '5101',
            'name' => 'Sueldos y salarios',
            'type' => AccountType::Gasto,
            'nature' => AccountNature::Deudora,
        ]);
    }

    private function trabajando(?User $user = null): static
    {
        return $this->actingAs($user ?? $this->admin)
            ->withSession(['active_client_id' => $this->cliente->id]);
    }

    /**
     * Sales of 1,000.00 and salaries of 300.00: a profit of 700.00.
     */
    private function conUtilidad(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']]);
        $this->asiento($this->periodo, '2026-03-01', [[$this->sueldos, '300.00', '0'], [$this->caja, '0', '300.00']]);
    }

    private function cerrar(?User $user = null)
    {
        return $this->trabajando($user)->post("/periods/{$this->periodo->id}/close", [
            'equity_account_id' => $this->utilidades->id,
        ]);
    }

    // Confirmación

    public function test_el_admin_ve_la_pantalla_de_confirmacion_con_el_resultado(): void
    {
        $this->conUtilidad();

        $response = $this->trabajando()->get("/periods/{$this->periodo->id}/close");

        $response->assertStatus(200);
        $response->assertSee('Ejercicio 2026');
        $response->assertSeeInOrder(['Utilidad del periodo', '700.00']);
        $response->assertSee('Utilidades retenidas');
    }

    public function test_el_contador_no_puede_ver_la_confirmacion_ni_cerrar(): void
    {
        $this->trabajando($this->contador)->get("/periods/{$this->periodo->id}/close")->assertStatus(403);
        $this->cerrar($this->contador)->assertStatus(403);

        $this->assertSame(PeriodStatus::Abierto, $this->periodo->fresh()->status);
    }

    public function test_el_listado_solo_ofrece_cerrar_al_admin_y_en_periodos_abiertos(): void
    {
        $cerrado = FiscalPeriod::factory()->for($this->cliente)->closed()->create([
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        $this->trabajando()->get('/periods')
            ->assertSee(url("/periods/{$this->periodo->id}/close"))
            ->assertDontSee(url("/periods/{$cerrado->id}/close"));

        $this->trabajando($this->contador)->get('/periods')
            ->assertDontSee(url("/periods/{$this->periodo->id}/close"));
    }

    // Cierre

    public function test_el_admin_cierra_el_periodo_y_queda_registrado_quien_y_cuando(): void
    {
        Carbon::setTestNow('2027-01-10 09:30:00');

        $response = $this->cerrar();

        $response->assertRedirect('/periods');
        $periodo = $this->periodo->fresh();
        $this->assertSame(PeriodStatus::Cerrado, $periodo->status);
        $this->assertSame($this->admin->id, $periodo->closed_by);
        $this->assertSame('2027-01-10 09:30:00', $periodo->closed_at->toDateTimeString());
    }

    public function test_el_listado_muestra_quien_cerro_el_periodo(): void
    {
        $this->cerrar();

        $this->trabajando()->get('/periods')->assertSeeInOrder(['Ejercicio 2026', 'Cerrado', 'Ana Admin']);
    }

    public function test_un_periodo_cerrado_no_se_puede_volver_a_cerrar(): void
    {
        $this->cerrar();
        $cerradoPor = $this->periodo->fresh()->closed_at;

        $this->trabajando()->get("/periods/{$this->periodo->id}/close")->assertStatus(403);
        $this->cerrar()->assertStatus(403);

        $this->assertEquals($cerradoPor, $this->periodo->fresh()->closed_at);
    }

    public function test_no_se_puede_cerrar_un_periodo_de_otro_cliente(): void
    {
        $ajeno = FiscalPeriod::factory()->for(Client::factory())->create();

        $this->trabajando()->post("/periods/{$ajeno->id}/close")->assertStatus(404);
        $this->assertSame(PeriodStatus::Abierto, $ajeno->fresh()->status);
    }

    public function test_la_cuenta_de_patrimonio_debe_ser_imputable_de_tipo_patrimonio_y_del_cliente(): void
    {
        $this->conUtilidad();
        $patrimonioAjeno = Account::factory()->for(Client::factory())->create(['type' => AccountType::Patrimonio]);

        foreach ([null, $this->caja->id, $patrimonioAjeno->id] as $cuenta) {
            $this->trabajando()
                ->post("/periods/{$this->periodo->id}/close", ['equity_account_id' => $cuenta])
                ->assertSessionHasErrors('equity_account_id');
        }

        $this->assertSame(PeriodStatus::Abierto, $this->periodo->fresh()->status);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    // Asiento de cierre

    public function test_el_cierre_genera_un_asiento_que_traslada_la_utilidad_al_patrimonio(): void
    {
        $this->conUtilidad();

        $this->cerrar();

        $cierre = JournalEntry::where('is_closing', true)->firstOrFail();
        $this->assertSame($this->periodo->id, $cierre->fiscal_period_id);
        $this->assertSame('2026-12-31', $cierre->date->toDateString());
        $this->assertSame(3, $cierre->number);
        $this->assertSame($this->admin->id, $cierre->user_id);

        $saldos = app(AccountBalances::class)->for($this->cliente, $this->periodo);
        $this->assertSame(0, $saldos->get($this->ventas->id)->balance);
        $this->assertSame(0, $saldos->get($this->sueldos->id)->balance);
        $this->assertSame(70000, $saldos->get($this->utilidades->id)->balance);
        $this->assertSame(70000, $saldos->get($this->caja->id)->balance);
    }

    public function test_una_perdida_se_traslada_como_debito_al_patrimonio(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [[$this->caja, '200.00', '0'], [$this->ventas, '0', '200.00']]);
        $this->asiento($this->periodo, '2026-03-01', [[$this->sueldos, '500.00', '0'], [$this->caja, '0', '500.00']]);

        $this->cerrar();

        $saldos = app(AccountBalances::class)->for($this->cliente, $this->periodo);
        $this->assertSame(-30000, $saldos->get($this->utilidades->id)->balance);
        $this->assertSame(0, $saldos->get($this->ventas->id)->balance);
        $this->assertSame(0, $saldos->get($this->sueldos->id)->balance);
    }

    public function test_el_asiento_de_cierre_cuadra(): void
    {
        $this->conUtilidad();

        $this->cerrar();

        $cierre = JournalEntry::where('is_closing', true)->with('lines')->firstOrFail();
        $this->assertSame(
            $cierre->lines->sum(fn ($line) => (float) $line->debit),
            $cierre->lines->sum(fn ($line) => (float) $line->credit),
        );
    }

    public function test_sin_movimientos_de_resultados_no_se_genera_asiento_de_cierre(): void
    {
        $this->asiento($this->periodo, '2026-02-01', [[$this->caja, '100.00', '0'], [$this->utilidades, '0', '100.00']]);

        $this->cerrar()->assertSessionHasNoErrors();

        $this->assertSame(PeriodStatus::Cerrado, $this->periodo->fresh()->status);
        $this->assertFalse(JournalEntry::where('is_closing', true)->exists());
    }

    public function test_el_estado_de_resultados_de_un_periodo_cerrado_sigue_mostrando_la_utilidad(): void
    {
        $this->conUtilidad();
        $this->cerrar();

        $response = $this->trabajando()->get("/reports/income-statement?period={$this->periodo->id}");

        $response->assertSeeInOrder(['Total ingresos', '1,000.00', 'Total gastos', '300.00', 'Utilidad del periodo', '700.00']);
    }

    public function test_no_se_puede_registrar_un_asiento_en_el_periodo_cerrado(): void
    {
        $this->cerrar();

        $response = $this->trabajando()->post('/entries', [
            'date' => '2026-06-01',
            'description' => 'Tarde',
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '10.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '10.00'],
            ],
        ]);

        $response->assertSessionHasErrors('date');
    }
}
