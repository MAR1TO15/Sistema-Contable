<?php

namespace Tests\Feature\Reports;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Reports\Concerns\CreatesJournalEntries;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use CreatesJournalEntries, RefreshDatabase;

    private User $contador;

    private Client $cliente;

    private FiscalPeriod $periodo2025;

    private FiscalPeriod $periodo2026;

    private Account $activo;

    private Account $caja;

    private Account $ventas;

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

        $this->activo = Account::factory()->for($this->cliente)->group()->create(['code' => '1', 'name' => 'Activo']);
        $this->caja = Account::factory()->childOf($this->activo)->create(['code' => '1101', 'name' => 'Caja y bancos']);
        $this->ventas = Account::factory()->for($this->cliente)->create([
            'code' => '4101',
            'name' => 'Ventas',
            'type' => AccountType::Ingreso,
            'nature' => AccountNature::Acreedora,
        ]);
    }

    private function trabajando(): static
    {
        return $this->actingAs($this->contador)
            ->withSession(['active_client_id' => $this->cliente->id]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function reportes(): array
    {
        return [
            'libro diario' => ['/reports/journal'],
            'libro mayor' => ['/reports/ledger'],
            'balance de comprobacion' => ['/reports/trial-balance'],
        ];
    }

    #[DataProvider('reportes')]
    public function test_sin_cliente_activo_los_reportes_redirigen_a_la_seleccion_de_empresa(string $url): void
    {
        $this->actingAs($this->contador)->get($url)->assertRedirect('/workspace');
    }

    #[DataProvider('reportes')]
    public function test_no_se_puede_filtrar_por_un_periodo_de_otro_cliente(string $url): void
    {
        $periodoAjeno = FiscalPeriod::factory()->for(Client::factory())->create();

        $this->trabajando()->get("{$url}?period={$periodoAjeno->id}")->assertStatus(404);
    }

    // Libro diario

    public function test_el_libro_diario_muestra_los_asientos_en_orden_cronologico_con_sus_lineas(): void
    {
        $this->asiento($this->periodo2026, '2026-03-10', [[$this->caja, '200.00', '0'], [$this->ventas, '0', '200.00']], 'Venta de marzo');
        $this->asiento($this->periodo2026, '2026-01-05', [[$this->caja, '100.00', '0'], [$this->ventas, '0', '100.00']], 'Venta de enero');

        $response = $this->trabajando()->get('/reports/journal');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Venta de enero', 'Caja y bancos', 'Ventas', 'Venta de marzo']);
        $response->assertSee('300.00');
    }

    public function test_el_libro_diario_se_filtra_por_periodo(): void
    {
        $this->asiento($this->periodo2025, '2025-06-01', [[$this->caja, '50.00', '0'], [$this->ventas, '0', '50.00']], 'Venta de 2025');
        $this->asiento($this->periodo2026, '2026-06-01', [[$this->caja, '80.00', '0'], [$this->ventas, '0', '80.00']], 'Venta de 2026');

        $response = $this->trabajando()->get("/reports/journal?period={$this->periodo2026->id}");

        $response->assertSee('Venta de 2026');
        $response->assertDontSee('Venta de 2025');
    }

    public function test_el_libro_diario_no_muestra_asientos_de_otro_cliente(): void
    {
        $periodoAjeno = FiscalPeriod::factory()->for(Client::factory())->create();
        $cuentaAjena = Account::factory()->for($periodoAjeno->client)->create();
        $this->asiento($periodoAjeno, $periodoAjeno->start_date->toDateString(), [[$cuentaAjena, '1.00', '0'], [$cuentaAjena, '0', '1.00']], 'Asiento Ajeno');

        $this->trabajando()->get('/reports/journal')->assertDontSee('Asiento Ajeno');
    }

    // Libro mayor

    public function test_el_libro_mayor_sin_cuenta_seleccionada_muestra_el_selector(): void
    {
        $response = $this->trabajando()->get('/reports/ledger');

        $response->assertStatus(200);
        $response->assertSee('Caja y bancos');
    }

    public function test_el_libro_mayor_muestra_los_movimientos_con_saldo_acumulado(): void
    {
        $this->asiento($this->periodo2026, '2026-01-10', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']], 'Cobro inicial');
        $this->asiento($this->periodo2026, '2026-02-10', [[$this->ventas, '300.00', '0'], [$this->caja, '0', '300.00']], 'Devolucion');
        $this->asiento($this->periodo2026, '2026-03-10', [[$this->caja, '50.25', '0'], [$this->ventas, '0', '50.25']], 'Cobro final');

        $response = $this->trabajando()->get("/reports/ledger?account={$this->caja->id}");

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Cobro inicial', '1,000.00',
            'Devolucion', '300.00', '700.00',
            'Cobro final', '50.25', '750.25',
        ]);
    }

    public function test_el_libro_mayor_de_un_periodo_arrastra_el_saldo_anterior(): void
    {
        $this->asiento($this->periodo2025, '2025-12-01', [[$this->caja, '400.00', '0'], [$this->ventas, '0', '400.00']], 'Venta de 2025');
        $this->asiento($this->periodo2026, '2026-01-15', [[$this->caja, '100.00', '0'], [$this->ventas, '0', '100.00']], 'Venta de 2026');

        $response = $this->trabajando()->get("/reports/ledger?account={$this->caja->id}&period={$this->periodo2026->id}");

        $response->assertDontSee('Venta de 2025');
        $response->assertSeeInOrder(['Saldo anterior', '400.00', 'Venta de 2026', '100.00', '500.00']);
    }

    public function test_el_libro_mayor_de_una_cuenta_acreedora_respeta_su_naturaleza(): void
    {
        $this->asiento($this->periodo2026, '2026-01-10', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']], 'Venta');
        $this->asiento($this->periodo2026, '2026-02-10', [[$this->ventas, '300.00', '0'], [$this->caja, '0', '300.00']], 'Devolucion');

        $response = $this->trabajando()->get("/reports/ledger?account={$this->ventas->id}");

        $response->assertSeeInOrder(['Venta', '1,000.00', 'Devolucion', '300.00', '700.00']);
    }

    public function test_el_libro_mayor_no_muestra_cuentas_de_otro_cliente(): void
    {
        $cuentaAjena = Account::factory()->for(Client::factory())->create();

        $this->trabajando()->get("/reports/ledger?account={$cuentaAjena->id}")->assertStatus(404);
    }

    // Balance de comprobación

    public function test_el_balance_de_comprobacion_muestra_sumas_y_saldos_que_cuadran(): void
    {
        $this->asiento($this->periodo2026, '2026-01-10', [[$this->caja, '1000.00', '0'], [$this->ventas, '0', '1000.00']]);
        $this->asiento($this->periodo2026, '2026-02-10', [[$this->ventas, '300.00', '0'], [$this->caja, '0', '300.00']]);

        $response = $this->trabajando()->get('/reports/trial-balance');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['1', 'Activo', '1101', 'Caja y bancos', '4101', 'Ventas']);
        $response->assertSeeInOrder(['Totales', '1,300.00', '1,300.00']);
        $response->assertSee('El balance cuadra');
    }

    public function test_el_balance_de_comprobacion_se_filtra_por_periodo(): void
    {
        $this->asiento($this->periodo2025, '2025-06-01', [[$this->caja, '777.00', '0'], [$this->ventas, '0', '777.00']]);
        $this->asiento($this->periodo2026, '2026-06-01', [[$this->caja, '111.00', '0'], [$this->ventas, '0', '111.00']]);

        $response = $this->trabajando()->get("/reports/trial-balance?period={$this->periodo2026->id}");

        $response->assertSee('111.00');
        $response->assertDontSee('777.00');
    }

    public function test_el_inicio_enlaza_a_los_reportes_cuando_hay_cliente_activo(): void
    {
        $response = $this->trabajando()->get('/');

        $response->assertSee(url('/reports/journal'));
        $response->assertSee(url('/reports/ledger'));
        $response->assertSee(url('/reports/trial-balance'));
    }
}
