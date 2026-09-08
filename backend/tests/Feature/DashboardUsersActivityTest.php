<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Models\ActivityLog;
use App\Models\Enquiry;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class DashboardUsersActivityTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_dashboard_statistics_reflect_real_records(): void
    {
        $this->actingAsAdmin();

        Property::factory()->create(['created_by' => auth()->id(), 'status' => PropertyStatus::Published]);
        Property::factory()->draft()->create(['created_by' => auth()->id()]);
        Property::factory()->create(['created_by' => auth()->id(), 'status' => PropertyStatus::Sold]);
        Property::factory()->create(['created_by' => auth()->id(), 'status' => PropertyStatus::Rented, 'purpose' => 'rent']);
        Enquiry::factory()->create(['status' => 'new']);
        Enquiry::factory()->create(['status' => 'closed']);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertOk();
        $stats = $response->json('data.stats');

        $this->assertEquals(4, $stats['total_properties']);
        $this->assertEquals(1, $stats['published_properties']);
        $this->assertEquals(1, $stats['draft_properties']);
        $this->assertEquals(1, $stats['sold_properties']);
        $this->assertEquals(1, $stats['rented_properties']);
        $this->assertEquals(2, $stats['total_enquiries']);
        $this->assertEquals(1, $stats['new_enquiries']);
    }

    public function test_dashboard_includes_recent_properties_and_enquiries(): void
    {
        $this->actingAsAdmin();
        Property::factory()->create(['created_by' => auth()->id()]);
        Enquiry::factory()->create();

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertOk()
            ->assertJsonCount(1, 'data.recent_properties')
            ->assertJsonCount(1, 'data.recent_enquiries');
    }

    public function test_admin_can_list_and_search_users(): void
    {
        $this->actingAsAdmin();
        $this->user(['name' => 'Findable Person', 'email' => 'findable-user@example.com']);
        $this->user(['name' => 'Someone Else']);

        $response = $this->getJson('/api/v1/admin/users?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_user_role(): void
    {
        $this->actingAsAdmin();
        $target = $this->user();

        $response = $this->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'admin']);

        $response->assertOk()->assertJsonPath('data.role', 'admin');
    }

    public function test_non_admin_cannot_update_user_roles(): void
    {
        $this->actingAsUser();
        $target = $this->user();

        $this->patchJson("/api/v1/admin/users/{$target->id}", ['role' => 'admin'])->assertStatus(403);
    }

    public function test_activity_logs_are_created_for_administrative_actions(): void
    {
        $this->actingAsAdmin();

        $this->putJson('/api/v1/admin/settings', ['company_name' => 'Logged Update Inc']);

        $this->assertDatabaseHas('activity_logs', ['action' => 'settings.updated']);
    }

    public function test_activity_logs_are_paginated(): void
    {
        $this->actingAsAdmin();
        ActivityLog::factory()->count(30)->create();

        $response = $this->getJson('/api/v1/admin/activity-logs?per_page=10');

        $response->assertOk()->assertJsonCount(10, 'data')->assertJsonPath('meta.total', 30);
    }

    public function test_only_admins_can_view_activity_logs(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/admin/activity-logs')->assertStatus(403);
    }

    public function test_dashboard_is_forbidden_for_non_admin(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }
}
