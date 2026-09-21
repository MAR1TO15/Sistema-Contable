<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_users_are_redirected_away_from_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/');
    }

    public function test_users_can_authenticate_using_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-secreta'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-secreta',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-secreta'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-incorrecta',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_nonexistent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'no-existe@example.com',
            'password' => 'cualquier-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_email_is_required(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => '',
            'password' => 'password-secreta',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_password_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }
}
