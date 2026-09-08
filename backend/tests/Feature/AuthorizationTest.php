<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_guest_cannot_access_authenticated_endpoints(): void
    {
        $this->getJson('/api/v1/favorites')->assertStatus(401);
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_regular_user_cannot_access_admin_endpoints(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->getJson('/api/v1/admin/properties')->assertStatus(403);
        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_admin_can_access_admin_endpoints(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/properties')->assertOk();
    }

    public function test_inactive_admin_is_rejected_by_admin_middleware(): void
    {
        $admin = $this->admin(['is_active' => false]);
        Sanctum::actingAs($admin, ['*']);

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_property_policy_blocks_non_admin_from_mutating_properties(): void
    {
        $owner = $this->admin();
        $property = Property::factory()->for($owner, 'creator')->create();

        $this->actingAsUser();

        $this->putJson("/api/v1/admin/properties/{$property->id}", ['title' => 'Hacked'])
            ->assertStatus(403);

        $this->deleteJson("/api/v1/admin/properties/{$property->id}")
            ->assertStatus(403);
    }

    public function test_user_cannot_remove_another_users_favorite(): void
    {
        $owner = $this->user();
        $property = Property::factory()->for($this->admin(), 'creator')->create();
        Favorite::create(['user_id' => $owner->id, 'property_id' => $property->id]);

        $this->actingAsUser(); // a different user

        $this->deleteJson("/api/v1/favorites/{$property->slug}")->assertOk();

        // The other user's favorite must remain untouched — deleting your own
        // (non-existent) favorite must never cascade to someone else's record.
        $this->assertDatabaseHas('favorites', ['user_id' => $owner->id, 'property_id' => $property->id]);
    }

    public function test_admin_cannot_demote_the_last_active_administrator(): void
    {
        $admin = $this->admin();
        $this->actingAsAdmin(); // acting as a second admin to avoid self-referential edge cases
        // Remove the acting admin from contention by deactivating below via the *other* admin id.

        $response = $this->patchJson("/api/v1/admin/users/{$admin->id}", ['role' => 'user']);

        // There are two admins at this point (the actor + $admin), so demotion is allowed.
        $response->assertOk();

        // Now only the actor remains as admin; attempting to demote the actor itself must fail.
        $actorId = auth()->id();
        $this->patchJson("/api/v1/admin/users/{$actorId}", ['role' => 'user'])
            ->assertStatus(422);
    }

    public function test_admin_cannot_deactivate_the_last_active_administrator(): void
    {
        $this->actingAsAdmin();
        $actorId = auth()->id();

        $this->patchJson("/api/v1/admin/users/{$actorId}", ['is_active' => false])
            ->assertStatus(422);
    }

    public function test_draft_property_is_not_exposed_on_public_endpoints(): void
    {
        $draft = Property::factory()->for($this->admin(), 'creator')->draft()->create();

        $this->getJson("/api/v1/properties/{$draft->slug}")->assertStatus(404);
        $this->getJson('/api/v1/properties')->assertJsonMissing(['slug' => $draft->slug]);
    }

    public function test_soft_deleted_property_is_not_exposed_publicly(): void
    {
        $property = Property::factory()->for($this->admin(), 'creator')->create([
            'status' => PropertyStatus::Published,
            'published_at' => now(),
        ]);
        $property->delete();

        $this->getJson("/api/v1/properties/{$property->slug}")->assertStatus(404);
    }

    public function test_admin_property_index_is_forbidden_for_property_owner_who_is_not_admin(): void
    {
        // Even the creator of a property is not exempt from the admin gate if not an admin.
        $creatorAsUser = $this->user();
        Property::factory()->create(['created_by' => $creatorAsUser->id]);

        Sanctum::actingAs($creatorAsUser, ['*']);

        $this->getJson('/api/v1/admin/properties')->assertStatus(403);
    }
}
