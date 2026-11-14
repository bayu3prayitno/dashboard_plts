<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk')->assertSee('Email / username');
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create(['email' => 'admin@omahiot.example', 'password' => 'password']);

        $this->post('/login', ['login' => 'admin@omahiot.example', 'password' => 'password'])
            ->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username(): void
    {
        $user = User::factory()->create(['name' => 'admin', 'password' => 'password']);

        $this->post('/login', ['login' => 'admin', 'password' => 'password'])
            ->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'admin@omahiot.example', 'password' => 'password']);

        $this->from('/login')
            ->post('/login', ['login' => 'admin@omahiot.example', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'x@omahiot.example', 'password' => 'salah']);
        }

        $this->post('/login', ['login' => 'x@omahiot.example', 'password' => 'salah'])
            ->assertSessionHasErrors('login');
        $this->assertStringStartsWith('Terlalu banyak percobaan.', session('errors')->first('login'));
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_dashboard_pages(): void
    {
        foreach (['/dashboard', '/devices', '/gateway/1', '/historical', '/settings/system'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_logged_in_user_can_open_dashboard_and_returns_to_intended_page(): void
    {
        User::factory()->create(['email' => 'admin@omahiot.example', 'password' => 'password']);

        $this->get('/gateway/1')->assertRedirect(route('login'));
        $this->post('/login', ['login' => 'admin@omahiot.example', 'password' => 'password'])
            ->assertRedirect('/gateway/1');
        $this->get('/dashboard')->assertOk()->assertSee('Monitoring');
    }

    public function test_logged_in_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect();
    }
}
