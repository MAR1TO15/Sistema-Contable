<?php

namespace Tests\Feature\JournalEntries;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Client;
use App\Models\Firm;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Reports\Concerns\CreatesJournalEntries;
use Tests\TestCase;

class JournalEntryEditTest extends TestCase
{
    use CreatesJournalEntries, RefreshDatabase;

    private User $contador;

    private Client $cliente;

    private FiscalPeriod $abierto;

    private FiscalPeriod $cerrado;

    private Account $caja;

    private Account $ventas;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->contador = User::factory()->for($firm)->create();
        $this->cliente = Client::factory()->for($firm)->create();
        $this->cliente->users()->attach($this->contador);

        $this->cerrado = FiscalPeriod::factory()->for($this->cliente)->closed()->create([
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        $this->abierto = FiscalPeriod::factory()->for($this->cliente)->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->caja = Account::factory()->for($this->cliente)->create(['code' => '1101', 'name' => 'Caja y bancos']);
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

    private function asientoAbierto(): JournalEntry
    {
        return $this->asiento($this->abierto, '2026-02-01', [[$this->caja, '100.00', '0'], [$this->ventas, '0', '100.00']], 'Venta original');
    }

    private function asientoCerrado(): JournalEntry
    {
        return $this->asiento($this->cerrado, '2025-02-01', [[$this->caja, '100.00', '0'], [$this->ventas, '0', '100.00']], 'Venta de 2025');
    }

    /**
     * @return array<string, mixed>
     */
    private function cambios(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-02-10',
            'description' => 'Venta corregida',
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '250.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '250.00'],
            ],
        ], $overrides);
    }

    // Editar

    public function test_el_formulario_de_edicion_muestra_los_datos_del_asiento(): void
    {
        $asiento = $this->asientoAbierto();

        $response = $this->trabajando()->get("/entries/{$asiento->id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Venta original');
        $response->assertSee('100.00');
    }

    public function test_se_puede_editar_un_asiento_de_un_periodo_abierto(): void
    {
        $asiento = $this->asientoAbierto();

        $response = $this->trabajando()->put("/entries/{$asiento->id}", $this->cambios());

        $response->assertRedirect("/entries/{$asiento->id}");
        $asiento->refresh();
        $this->assertSame('Venta corregida', $asiento->description);
        $this->assertSame('2026-02-10', $asiento->date->toDateString());
        $this->assertSame(1, $asiento->number);
        $this->assertCount(2, $asiento->lines);
        $this->assertSame('250.00', $asiento->lines->firstWhere('account_id', $this->caja->id)->debit);
    }

    public function test_la_edicion_aplica_las_mismas_validaciones_que_el_registro(): void
    {
        $asiento = $this->asientoAbierto();

        $response = $this->trabajando()->put("/entries/{$asiento->id}", $this->cambios([
            'lines' => [
                ['account_id' => $this->caja->id, 'debit' => '250.00', 'credit' => ''],
                ['account_id' => $this->ventas->id, 'debit' => '', 'credit' => '200.00'],
            ],
        ]));

        $response->assertSessionHasErrors('lines');
        $this->assertSame('Venta original', $asiento->fresh()->description);
        $this->assertSame('100.00', $asiento->lines()->first()->debit);
    }

    public function test_no_se_puede_mover_un_asiento_a_un_periodo_cerrado(): void
    {
        $asiento = $this->asientoAbierto();

        $response = $this->trabajando()->put("/entries/{$asiento->id}", $this->cambios(['date' => '2025-06-01']));

        $response->assertSessionHasErrors('date');
        $this->assertSame('2026-02-01', $asiento->fresh()->date->toDateString());
    }

    public function test_un_asiento_que_cambia_de_periodo_abierto_actualiza_su_periodo(): void
    {
        $otroAbierto = FiscalPeriod::factory()->for($this->cliente)->create([
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);
        $asiento = $this->asientoAbierto();

        $this->trabajando()->put("/entries/{$asiento->id}", $this->cambios(['date' => '2027-03-01']));

        $this->assertSame($otroAbierto->id, $asiento->fresh()->fiscal_period_id);
    }

    public function test_no_se_puede_editar_un_asiento_de_un_periodo_cerrado(): void
    {
        $asiento = $this->asientoCerrado();

        $this->trabajando()->get("/entries/{$asiento->id}/edit")->assertStatus(403);
        $this->trabajando()->put("/entries/{$asiento->id}", $this->cambios())->assertStatus(403);

        $this->assertSame('Venta de 2025', $asiento->fresh()->description);
    }

    public function test_no_se_puede_editar_un_asiento_de_otro_cliente(): void
    {
        $periodoAjeno = FiscalPeriod::factory()->for(Client::factory())->create();
        $cuentaAjena = Account::factory()->for($periodoAjeno->client)->create();
        $ajeno = $this->asiento($periodoAjeno, $periodoAjeno->start_date->toDateString(), [[$cuentaAjena, '1.00', '0'], [$cuentaAjena, '0', '1.00']]);

        $this->trabajando()->get("/entries/{$ajeno->id}/edit")->assertStatus(404);
        $this->trabajando()->put("/entries/{$ajeno->id}", $this->cambios())->assertStatus(404);
        $this->trabajando()->delete("/entries/{$ajeno->id}")->assertStatus(404);
    }

    // Eliminar

    public function test_se_puede_eliminar_un_asiento_de_un_periodo_abierto(): void
    {
        $asiento = $this->asientoAbierto();

        $response = $this->trabajando()->delete("/entries/{$asiento->id}");

        $response->assertRedirect('/entries');
        $this->assertDatabaseMissing('journal_entries', ['id' => $asiento->id]);
        $this->assertDatabaseMissing('journal_entry_lines', ['journal_entry_id' => $asiento->id]);
    }

    public function test_no_se_puede_eliminar_un_asiento_de_un_periodo_cerrado(): void
    {
        $asiento = $this->asientoCerrado();

        $this->trabajando()->delete("/entries/{$asiento->id}")->assertStatus(403);

        $this->assertDatabaseHas('journal_entries', ['id' => $asiento->id]);
    }

    // Acciones visibles

    public function test_el_detalle_ofrece_editar_y_eliminar_solo_en_periodos_abiertos(): void
    {
        $abierto = $this->asientoAbierto();
        $cerrado = $this->asientoCerrado();

        $this->trabajando()->get("/entries/{$abierto->id}")
            ->assertSee(url("/entries/{$abierto->id}/edit"))
            ->assertSee('Eliminar');

        $this->trabajando()->get("/entries/{$cerrado->id}")
            ->assertDontSee(url("/entries/{$cerrado->id}/edit"))
            ->assertDontSee('Eliminar')
            ->assertSee('periodo cerrado');
    }
}
