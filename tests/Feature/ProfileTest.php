<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profil')->assertRedirect(route('login'));
    }

    public function test_profile_page_shows_logged_in_user_only(): void
    {
        $user = User::factory()->create([
            'name' => 'Rina Wulandari',
            'username' => 'superadmin',
            'email' => 'superadmin@omahiot.example',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($user)->get('/profil')
            ->assertOk()
            ->assertSee('Profil Saya')
            ->assertSee('Rina Wulandari')
            ->assertSee('superadmin@omahiot.example')
            ->assertSee('Super Admin')
            ->assertSee('Akun aktif')
            ->assertSee('id="edit-profile"', false)
            ->assertSee('id="edit-password"', false)
            ->assertDontSee('Manajemen User');
    }

    public function test_sidebar_user_card_links_to_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('profile.show').'"', false);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profil', [
            'name' => 'Nama Baru',
            'username' => 'nama_baru',
            'email' => 'baru@omahiot.example',
        ])->assertRedirect(route('profile.show'))->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'username' => 'nama_baru',
            'email' => 'baru@omahiot.example',
        ]);
    }

    public function test_profile_update_rejects_taken_username_and_email(): void
    {
        User::factory()->create(['username' => 'taken', 'email' => 'taken@omahiot.example']);
        $user = User::factory()->create();

        $this->actingAs($user)->from('/profil')->put('/profil', [
            'name' => 'X',
            'username' => 'taken',
            'email' => 'taken@omahiot.example',
        ])->assertRedirect('/profil')->assertSessionHasErrorsIn('profile', ['username', 'email']);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => 'password',
            'password' => 'rahasia-baru-123',
            'password_confirmation' => 'rahasia-baru-123',
        ])->assertRedirect(route('profile.show'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('rahasia-baru-123', $user->fresh()->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->from('/profil')->put('/profil/password', [
            'current_password' => 'salah',
            'password' => 'rahasia-baru-123',
            'password_confirmation' => 'rahasia-baru-123',
        ])->assertRedirect('/profil')->assertSessionHasErrorsIn('password', ['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
