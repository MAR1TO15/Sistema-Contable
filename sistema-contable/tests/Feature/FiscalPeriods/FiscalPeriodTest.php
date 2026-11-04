<?php

namespace Tests\Feature\FiscalPeriods;

use App\Enums\PeriodStatus;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FiscalPeriodTest extends TestCase
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
    private function trabajando(?User $user = null): static
    {
        return $this->actingAs($user ?? $this->contador)
            ->withSession(['active_client_id' => $this->cliente->id]);
    }

    public function test_sin_cliente_activo_los_periodos_redirigen_a_la_seleccion_de_empresa(): void
    {
        $this->actingAs($this->contador)->get('/periods')->assertRedirect('/workspace');
    }

    public function test_se_listan_los_periodos_del_cliente_activo_del_mas_reciente_al_mas_antiguo(): void
    {
        FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response = $this->trabajando()->get('/periods');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Ejercicio 2026', 'Ejercicio 2025']);
    }

    public function test_no_se_listan_periodos_de_otro_cliente(): void
    {
        FiscalPeriod::factory()->for(Client::factory())->create(['name' => 'Periodo Ajeno']);

        $response = $this->trabajando()->get('/periods');

        $response->assertStatus(200);
        $response->assertDontSee('Periodo Ajeno');
    }

    public function test_se_puede_crear_un_periodo_y_queda_abierto(): void
    {
        $response = $this->trabajando()->post('/periods', [
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertRedirect('/periods');

        $periodo = FiscalPeriod::where('name', 'Ejercicio 2026')->firstOrFail();
        $this->assertSame($this->cliente->id, $periodo->client_id);
        $this->assertSame(PeriodStatus::Abierto, $periodo->status);
        $this->assertSame('2026-01-01', $periodo->start_date->toDateString());
        $this->assertSame('2026-12-31', $periodo->end_date->toDateString());
    }

    public function test_el_periodo_siempre_pertenece_al_cliente_activo_y_nace_abierto(): void
    {
        $otroCliente = Client::factory()->create();

        $this->trabajando()->post('/periods', [
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'client_id' => $otroCliente->id,
            'status' => PeriodStatus::Cerrado->value,
        ]);

        $this->assertDatabaseHas('fiscal_periods', [
            'name' => 'Ejercicio 2026',
            'client_id' => $this->cliente->id,
            'status' => 'abierto',
        ]);
        $this->assertDatabaseMissing('fiscal_periods', ['client_id' => $otroCliente->id]);
    }

    public function test_el_nombre_y_las_fechas_son_requeridos(): void
    {
        $response = $this->trabajando()->post('/periods', []);

        $response->assertSessionHasErrors(['name', 'start_date', 'end_date']);
    }

    public function test_las_fechas_deben_ser_validas(): void
    {
        $response = $this->trabajando()->post('/periods', [
            'name' => 'Ejercicio',
            'start_date' => 'no-es-fecha',
            'end_date' => '2026-02-30',
        ]);

        $response->assertSessionHasErrors(['start_date', 'end_date']);
    }

    public function test_la_fecha_final_debe_ser_posterior_a_la_inicial(): void
    {
        $response = $this->trabajando()->post('/periods', [
            'name' => 'Ejercicio al reves',
            'start_date' => '2026-12-31',
            'end_date' => '2026-01-01',
        ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('fiscal_periods', 0);
    }

    public function test_la_fecha_final_no_puede_ser_igual_a_la_inicial(): void
    {
        $response = $this->trabajando()->post('/periods', [
            'name' => 'Un solo dia',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-01',
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    /**
     * Ranges that overlap the existing period 2026-01-01 .. 2026-06-30.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rangosQueSeTraslapan(): array
    {
        return [
            'mismo rango' => ['2026-01-01', '2026-06-30'],
            'dentro del existente' => ['2026-02-01', '2026-03-31'],
            'contiene al existente' => ['2025-12-01', '2026-07-31'],
            'empieza antes y termina dentro' => ['2025-10-01', '2026-01-15'],
            'empieza dentro y termina despues' => ['2026-06-01', '2026-12-31'],
            'comparte el ultimo dia' => ['2026-06-30', '2026-12-31'],
            'comparte el primer dia' => ['2025-07-01', '2026-01-01'],
        ];
    }

    #[DataProvider('rangosQueSeTraslapan')]
    public function test_se_rechaza_un_periodo_que_se_traslapa_con_otro(string $inicio, string $fin): void
    {
        FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Primer semestre',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $response = $this->trabajando()->post('/periods', [
            'name' => 'Nuevo periodo',
            'start_date' => $inicio,
            'end_date' => $fin,
        ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertDatabaseMissing('fiscal_periods', ['name' => 'Nuevo periodo']);
    }

    public function test_se_acepta_un_periodo_que_empieza_justo_despues_de_otro(): void
    {
        FiscalPeriod::factory()->for($this->cliente)->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $response = $this->trabajando()->post('/periods', [
            'name' => 'Segundo semestre',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fiscal_periods', ['name' => 'Segundo semestre']);
    }

    public function test_el_traslape_solo_se_valida_dentro_del_mismo_cliente(): void
    {
        FiscalPeriod::factory()->for(Client::factory())->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response = $this->trabajando()->post('/periods', [
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('fiscal_periods', ['client_id' => $this->cliente->id]);
    }

    public function test_el_nombre_es_unico_dentro_del_mismo_cliente(): void
    {
        FiscalPeriod::factory()->for($this->cliente)->create([
            'name' => 'Ejercicio 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        $response = $this->trabajando()->post('/periods', [
            'name' => 'Ejercicio 2025',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_el_admin_de_firma_tambien_puede_crear_periodos(): void
    {
        $admin = User::factory()->for($this->cliente->firm)->adminFirma()->create();

        $response = $this->trabajando($admin)->post('/periods', [
            'name' => 'Ejercicio 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $response->assertRedirect('/periods');
        $this->assertDatabaseHas('fiscal_periods', ['client_id' => $this->cliente->id]);
    }

    public function test_el_formulario_de_creacion_se_muestra(): void
    {
        $this->trabajando()->get('/periods/create')->assertStatus(200);
    }

    public function test_el_inicio_enlaza_a_los_periodos_cuando_hay_cliente_activo(): void
    {
        $this->trabajando()->get('/')->assertSee(route('periods.index'));
    }

    public function test_el_inicio_no_enlaza_a_los_periodos_sin_cliente_activo(): void
    {
        $this->actingAs($this->contador)->get('/')->assertDontSee(url('/periods'));
    }
}
