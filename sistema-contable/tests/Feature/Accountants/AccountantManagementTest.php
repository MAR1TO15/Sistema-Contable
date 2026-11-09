<?php

namespace Tests\Feature\Accountants;

use App\Enums\UserRole;
use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_de_firma_ve_el_listado_de_contadores_de_su_firma(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $contador = User::factory()->for($firm)->create();

        $response = $this->actingAs($admin)->get('/accountants');

        $response->assertStatus(200);
        $response->assertSee($contador->email);
    }

    public function test_admin_de_firma_no_ve_contadores_de_otra_firma_en_el_listado(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $contadorDeOtraFirma = User::factory()->create();

        $response = $this->actingAs($admin)->get('/accountants');

        $response->assertStatus(200);
        $response->assertDontSee($contadorDeOtraFirma->email);
    }

    public function test_contador_no_puede_acceder_a_la_gestion_de_contadores(): void
    {
        $contador = User::factory()->create();

        $response = $this->actingAs($contador)->get('/accountants');

        $response->assertStatus(403);
    }

    public function test_super_admin_no_puede_acceder_a_la_gestion_de_contadores(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->get('/accountants');

        $response->assertStatus(403);
    }

    public function test_admin_de_firma_puede_crear_un_contador(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();

        $response = $this->actingAs($admin)->post('/accountants', [
            'name' => 'Carlos Contador',
            'email' => 'carlos@firma.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/accountants');

        $contador = User::where('email', 'carlos@firma.test')->firstOrFail();
        $this->assertSame($firm->id, $contador->firm_id);
        $this->assertSame(UserRole::Contador, $contador->role);
        $this->assertTrue($contador->is_active);
        $this->assertTrue(Hash::check('password123', $contador->password));
    }

    public function test_el_contador_creado_siempre_pertenece_a_la_firma_del_admin_y_tiene_rol_contador(): void
    {
        $firm = Firm::factory()->create();
        $otraFirma = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();

        $this->actingAs($admin)->post('/accountants', [
            'name' => 'Intruso',
            'email' => 'intruso@firma.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'firm_id' => $otraFirma->id,
            'role' => UserRole::AdminFirma->value,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'intruso@firma.test',
            'firm_id' => $firm->id,
            'role' => UserRole::Contador->value,
        ]);
    }

    public function test_contador_no_puede_crear_contadores(): void
    {
        $contador = User::factory()->create();

        $response = $this->actingAs($contador)->post('/accountants', [
            'name' => 'Otro',
            'email' => 'otro@firma.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'otro@firma.test']);
    }

    public function test_el_correo_debe_ser_unico_al_crear_un_contador(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        User::factory()->create(['email' => 'repetido@firma.test']);

        $response = $this->actingAs($admin)->post('/accountants', [
            'name' => 'Repetido',
            'email' => 'repetido@firma.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_la_contrasena_debe_confirmarse_al_crear_un_contador(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();

        $response = $this->actingAs($admin)->post('/accountants', [
            'name' => 'Carlos',
            'email' => 'carlos@firma.test',
            'password' => 'password123',
            'password_confirmation' => 'otra-cosa',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_admin_de_firma_puede_editar_un_contador_sin_cambiar_su_contrasena(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $contador = User::factory()->for($firm)->create(['password' => Hash::make('original123')]);

        $response = $this->actingAs($admin)->put("/accountants/{$contador->id}", [
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@firma.test',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect('/accountants');

        $contador->refresh();
        $this->assertSame('Nombre Nuevo', $contador->name);
        $this->assertSame('nuevo@firma.test', $contador->email);
        $this->assertTrue(Hash::check('original123', $contador->password));
    }

    public function test_admin_de_firma_puede_cambiar_la_contrasena_de_un_contador(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $contador = User::factory()->for($firm)->create();

        $this->actingAs($admin)->put("/accountants/{$contador->id}", [
            'name' => $contador->name,
            'email' => $contador->email,
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ]);

        $this->assertTrue(Hash::check('nueva-clave-123', $contador->fresh()->password));
    }

    public function test_admin_de_firma_no_puede_editar_un_contador_de_otra_firma(): void
    {
        $admin = User::factory()->adminFirma()->create();
        $contadorDeOtraFirma = User::factory()->create(['name' => 'Original']);

        $this->actingAs($admin)->get("/accountants/{$contadorDeOtraFirma->id}/edit")->assertStatus(403);

        $response = $this->actingAs($admin)->put("/accountants/{$contadorDeOtraFirma->id}", [
            'name' => 'Hackeado',
            'email' => $contadorDeOtraFirma->email,
        ]);

        $response->assertStatus(403);
        $this->assertSame('Original', $contadorDeOtraFirma->fresh()->name);
    }

    public function test_admin_de_firma_no_puede_editar_a_otro_administrador_desde_la_gestion_de_contadores(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $otroAdmin = User::factory()->for($firm)->adminFirma()->create();

        $this->actingAs($admin)->get("/accountants/{$otroAdmin->id}/edit")->assertStatus(403);
        $this->actingAs($admin)->patch("/accountants/{$otroAdmin->id}/toggle")->assertStatus(403);

        $this->assertTrue($otroAdmin->fresh()->is_active);
    }

    public function test_admin_de_firma_puede_desactivar_y_reactivar_un_contador(): void
    {
        $firm = Firm::factory()->create();
        $admin = User::factory()->for($firm)->adminFirma()->create();
        $contador = User::factory()->for($firm)->create();

        $this->actingAs($admin)->patch("/accountants/{$contador->id}/toggle");
        $this->assertDatabaseHas('users', ['id' => $contador->id, 'is_active' => false]);

        $this->actingAs($admin)->patch("/accountants/{$contador->id}/toggle");
        $this->assertDatabaseHas('users', ['id' => $contador->id, 'is_active' => true]);
    }

    public function test_admin_de_firma_no_puede_desactivar_un_contador_de_otra_firma(): void
    {
        $admin = User::factory()->adminFirma()->create();
        $contadorDeOtraFirma = User::factory()->create();

        $response = $this->actingAs($admin)->patch("/accountants/{$contadorDeOtraFirma->id}/toggle");

        $response->assertStatus(403);
        $this->assertTrue($contadorDeOtraFirma->fresh()->is_active);
    }
}
