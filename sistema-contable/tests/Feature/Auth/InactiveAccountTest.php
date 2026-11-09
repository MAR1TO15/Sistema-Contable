<?php

namespace Tests\Feature\Auth;

use App\Models\Firm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InactiveAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $user = User::factory()->inactive()->create([
            'password' => Hash::make('password-secreta'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-secreta',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_un_usuario_de_una_firma_desactivada_no_puede_iniciar_sesion(): void
    {
        $firm = Firm::factory()->create(['is_active' => false]);
        $user = User::factory()->for($firm)->create([
            'password' => Hash::make('password-secreta'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-secreta',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_el_super_admin_sin_firma_puede_iniciar_sesion(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'password' => Hash::make('password-secreta'),
        ]);

        $this->post('/login', [
            'email' => $superAdmin->email,
            'password' => 'password-secreta',
        ]);

        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_un_usuario_desactivado_con_sesion_abierta_es_expulsado(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertStatus(200);

        $user->update(['is_active' => false]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_un_usuario_cuya_firma_se_desactiva_con_sesion_abierta_es_expulsado(): void
    {
        $user = User::factory()->create();

        $user->firm->update(['is_active' => false]);

        $response = $this->actingAs($user->fresh())->get('/');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
