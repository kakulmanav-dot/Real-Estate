<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SettingsHomepageTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_public_settings_endpoint_returns_defaults(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertOk()->assertJsonPath('data.company_name', 'Real Estate');
    }

    public function test_homepage_endpoint_returns_settings_featured_properties_and_testimonials(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'featured' => true]);
        Testimonial::factory()->create(['is_approved' => true]);

        $response = $this->getJson('/api/v1/home');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['settings', 'featured_properties', 'testimonials']])
            ->assertJsonCount(1, 'data.featured_properties')
            ->assertJsonCount(1, 'data.testimonials');
    }

    public function test_homepage_excludes_unapproved_testimonials(): void
    {
        Testimonial::factory()->create(['is_approved' => false]);

        $response = $this->getJson('/api/v1/home');

        $response->assertJsonCount(0, 'data.testimonials');
    }

    public function test_admin_can_retrieve_settings(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/admin/settings')->assertOk();
    }

    public function test_admin_can_update_settings(): void
    {
        $this->actingAsAdmin();

        $response = $this->putJson('/api/v1/admin/settings', ['company_name' => 'Updated Realty Co']);

        $response->assertOk()->assertJsonPath('data.company_name', 'Updated Realty Co');
        $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'Updated Realty Co']);
    }

    public function test_unknown_setting_key_is_ignored_not_persisted(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/v1/admin/settings', ['not_a_real_setting' => 'value'])->assertOk();

        $this->assertDatabaseMissing('settings', ['key' => 'not_a_real_setting']);
    }

    public function test_settings_update_validates_email_format(): void
    {
        $this->actingAsAdmin();

        $response = $this->putJson('/api/v1/admin/settings', ['company_email' => 'not-an-email']);

        $response->assertStatus(422)->assertJsonValidationErrors(['company_email']);
    }

    public function test_settings_cache_is_invalidated_after_update(): void
    {
        // Warm the cache with the default value.
        $this->getJson('/api/v1/settings')->assertJsonPath('data.company_name', 'Real Estate');

        $this->actingAsAdmin();
        $this->putJson('/api/v1/admin/settings', ['company_name' => 'Fresh Name Inc']);

        // The public endpoint must reflect the change immediately, not a stale cached value.
        $this->getJson('/api/v1/settings')->assertJsonPath('data.company_name', 'Fresh Name Inc');
    }

    public function test_settings_endpoint_does_not_expose_unknown_stored_keys(): void
    {
        // Regression test for a fixed bug: allSettings() used to merge in
        // every stored row, not just the whitelisted public-facing keys.
        Setting::create(['key' => 'internal_secret', 'value' => 'should-not-leak']);
        Setting::flushCache();

        $response = $this->getJson('/api/v1/settings');

        $response->assertOk()->assertJsonMissingPath('data.internal_secret');
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $this->actingAsUser();

        $this->putJson('/api/v1/admin/settings', ['company_name' => 'Hacked'])->assertStatus(403);
    }
}
