<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'user']);
    }

    public function test_registration_requires_valid_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $this->user(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_password_confirmation_match(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Different1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_registration_cannot_assign_admin_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Wannabe Admin',
            'email' => 'wannabe@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'role' => 'admin',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'wannabe@example.com', 'role' => 'user']);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->user(['email' => 'login@example.com', 'password' => Hash::make('Password1')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'Password1',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->user(['email' => 'login2@example.com', 'password' => Hash::make('Password1')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login2@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->user(['email' => 'inactive@example.com', 'password' => Hash::make('Password1'), 'is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'Password1',
        ]);

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $this->user(['email' => 'ratelimit@example.com', 'password' => Hash::make('Password1')]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'ratelimit@example.com', 'password' => 'wrong']);
        }

        $response = $this->postJson('/api/v1/auth/login', ['email' => 'ratelimit@example.com', 'password' => 'wrong']);

        $response->assertStatus(429);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = $this->actingAsUser();

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = $this->user(['password' => Hash::make('Password1')]);
        $login = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password1']);
        $token = $login->json('data.token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $logout = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/auth/logout');
        $logout->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Laravel's RequestGuard caches the resolved user for the lifetime of the
        // guard instance (see Illuminate\Auth\RequestGuard::user()), and that guard
        // instance is reused across every simulated request within a single test
        // method. Forget it so the next call re-resolves the (now revoked) token,
        // matching real production behavior where each request gets a fresh guard.
        auth()->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_user_can_update_profile(): void
    {
        $this->actingAsUser(['name' => 'Old Name']);

        $response = $this->putJson('/api/v1/profile', ['name' => 'New Name', 'phone' => '5551234']);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_profile_update_validates_email_uniqueness(): void
    {
        $this->user(['email' => 'existing@example.com']);
        $this->actingAsUser(['email' => 'me@example.com']);

        $response = $this->putJson('/api/v1/profile', ['email' => 'existing@example.com']);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_cannot_change_role(): void
    {
        $user = $this->actingAsUser();

        $this->putJson('/api/v1/profile', ['name' => $user->name, 'role' => 'admin']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user']);
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->user(['password' => Hash::make('OldPassword1')]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('NewPassword1', $user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = $this->user(['password' => Hash::make('OldPassword1')]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/profile/password', [
            'current_password' => 'WrongPassword',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_forgot_password_returns_generic_response_for_unknown_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com']);

        // Must not leak whether the account exists via the HTTP status/message —
        // regression test for a fixed email-enumeration bug.
        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_forgot_password_returns_identical_response_for_known_email(): void
    {
        Notification::fake();
        $user = $this->user(['email' => 'known@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'known@example.com']);

        $response->assertOk()->assertJsonPath('success', true);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->user(['email' => 'reset@example.com']);
        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'BrandNew1',
            'password_confirmation' => 'BrandNew1',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('BrandNew1', $user->fresh()->password));
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $this->user(['email' => 'reset2@example.com']);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'reset2@example.com',
            'password' => 'BrandNew1',
            'password_confirmation' => 'BrandNew1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_user_resource_never_exposes_password_hash(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertJsonMissingPath('data.password');
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }
}
