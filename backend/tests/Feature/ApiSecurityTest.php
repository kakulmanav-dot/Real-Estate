<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_health_endpoint_reports_ok_without_leaking_details(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()->assertJsonPath('data.status', 'ok');
        $response->assertJsonMissingPath('data.database');
        $response->assertJsonMissingPath('data.env');
    }

    public function test_success_responses_share_a_consistent_envelope(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertOk()->assertJsonStructure(['success', 'message', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_validation_errors_share_a_consistent_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)->assertJsonStructure(['success', 'message', 'errors']);
        $this->assertFalse($response->json('success'));
    }

    public function test_guest_receives_json_401_not_html(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('success', false);
    }

    public function test_guest_receives_json_401_even_without_an_accept_header(): void
    {
        // Regression test for a fixed bug: a plain request with no explicit
        // Accept header (i.e. not using the getJson() test helper, which sets
        // one automatically) crashed with a 500 RouteNotFoundException,
        // because the default Authenticate middleware tried to redirect to a
        // named "login" route that doesn't exist in this API-only app.
        $response = $this->call('GET', '/api/v1/favorites');

        $response->assertStatus(401);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function test_forbidden_request_receives_json_403(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_missing_route_receives_json_404(): void
    {
        $response = $this->getJson('/api/v1/this-route-does-not-exist');

        $response->assertStatus(404);
    }

    public function test_unknown_property_slug_receives_json_404(): void
    {
        $response = $this->getJson('/api/v1/properties/no-such-property');

        $response->assertStatus(404)->assertJsonPath('success', false);
    }

    public function test_validation_failure_receives_json_422(): void
    {
        $response = $this->postJson('/api/v1/enquiries', []);

        $response->assertStatus(422);
    }

    public function test_rate_limit_receives_json_429(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'wrong']);
        }

        $response = $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'wrong']);

        $response->assertStatus(429);
    }

    public function test_production_style_error_does_not_leak_stack_trace(): void
    {
        config(['app.debug' => false, 'app.env' => 'production']);

        // Force an internal server error path by hitting a valid route with a
        // condition that would normally throw (property policy misconfiguration
        // is avoided here — instead we simulate via a deliberately broken query).
        $response = $this->getJson('/api/v1/properties?min_price=not-a-number');

        // Even in a hard failure this must never look like a raw exception page.
        $this->assertStringNotContainsString('vendor/laravel', $response->getContent());
        $this->assertStringNotContainsString('Stack trace', $response->getContent());
    }

    public function test_mass_assignment_cannot_escalate_role_via_register(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'role' => 'admin',
            'is_active' => false,
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'attacker@example.com', 'role' => 'user', 'is_active' => true]);
    }

    public function test_mass_assignment_cannot_change_property_owner_via_update(): void
    {
        $admin = $this->actingAsAdmin();
        $otherAdmin = $this->admin();
        $property = Property::factory()->create(['created_by' => $admin->id]);

        $this->putJson("/api/v1/admin/properties/{$property->id}", [
            'title' => $property->title,
            'created_by' => $otherAdmin->id,
        ]);

        $this->assertDatabaseHas('properties', ['id' => $property->id, 'created_by' => $admin->id]);
    }

    public function test_html_and_script_input_is_stored_safely_without_execution(): void
    {
        $this->actingAsAdmin();
        $payload = '<script>alert(1)</script>';

        $response = $this->postJson('/api/v1/admin/testimonials', [
            'name' => $payload,
            'text' => $payload,
            'rating' => 5,
        ]);

        $response->assertCreated();
        // Stored as literal text (Eloquent does not execute or strip it); the
        // frontend is responsible for escaping on render, and the API must
        // never pre-render or evaluate this as markup itself.
        $this->assertDatabaseHas('testimonials', ['name' => $payload]);
    }

    public function test_oversized_request_body_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/enquiries', [
            'name' => 'Overflow',
            'email' => 'overflow@example.com',
            'message' => str_repeat('a', 6000), // exceeds the 5000-char max
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['message']);
    }

    public function test_api_never_exposes_password_hashes_or_internal_paths(): void
    {
        $this->actingAsAdmin();
        $this->user();

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString('$2y$', $body); // bcrypt hash prefix
        $this->assertStringNotContainsString('C:\\', $body);
        $this->assertStringNotContainsString('/home/', $body);
    }

    public function test_password_reset_tokens_are_never_exposed_in_responses(): void
    {
        Notification::fake();
        $this->user(['email' => 'noleak@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'noleak@example.com']);

        $response->assertOk();
        $this->assertArrayNotHasKey('token', $response->json('data') ?? []);
    }

    public function test_cors_allows_configured_frontend_origin(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/settings');

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function test_cors_does_not_grant_unapproved_origin_permissive_headers(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $response = $this->withHeaders([
            'Origin' => 'http://evil.example.com',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/settings');

        $this->assertNotEquals('http://evil.example.com', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
    }
}
