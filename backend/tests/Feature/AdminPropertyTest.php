<?php

namespace Tests\Feature;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AdminPropertyTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Property',
            'description' => 'A lovely test property with plenty of space.',
            'purpose' => 'sale',
            'property_type' => 'Apartment',
            'price' => 250000,
            'address' => '123 Test Street',
            'city' => 'Testville',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'area' => 1500,
        ], $overrides);
    }

    public function test_admin_can_create_property(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/properties', $this->payload());

        $response->assertCreated()->assertJsonPath('data.title', 'Test Property');
        $this->assertDatabaseHas('properties', ['title' => 'Test Property']);
    }

    public function test_create_property_requires_mandatory_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/properties', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['title', 'description', 'purpose', 'price', 'address', 'city', 'bedrooms', 'bathrooms', 'area']);
    }

    public function test_slug_and_reference_number_are_generated_automatically(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/properties', $this->payload(['title' => 'Unique Auto Slug House']));

        $response->assertCreated();
        $this->assertDatabaseHas('properties', ['slug' => 'unique-auto-slug-house']);
        $this->assertNotEmpty($response->json('data.reference_number'));
    }

    public function test_duplicate_titles_generate_unique_slugs(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/properties', $this->payload(['title' => 'Duplicate Title House']))->assertCreated();
        $second = $this->postJson('/api/v1/admin/properties', $this->payload(['title' => 'Duplicate Title House']))->assertCreated();

        $this->assertNotEquals('duplicate-title-house', $second->json('data.slug'));
        $this->assertDatabaseCount('properties', 2);
    }

    public function test_admin_can_update_property(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id(), 'title' => 'Old Title']);

        $response = $this->putJson("/api/v1/admin/properties/{$property->id}", ['title' => 'New Title']);

        $response->assertOk()->assertJsonPath('data.title', 'New Title');
    }

    public function test_admin_can_publish_and_unpublish_property(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->draft()->create(['created_by' => auth()->id()]);

        $publish = $this->patchJson("/api/v1/admin/properties/{$property->id}/status", ['status' => 'published']);
        $publish->assertOk()->assertJsonPath('data.status', 'published');
        $this->assertNotNull($property->fresh()->published_at);

        $unpublish = $this->patchJson("/api/v1/admin/properties/{$property->id}/status", ['status' => 'draft']);
        $unpublish->assertOk();
        $this->assertNull($property->fresh()->published_at);
    }

    public function test_admin_can_toggle_featured_status(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id(), 'featured' => false]);

        $response = $this->patchJson("/api/v1/admin/properties/{$property->id}/featured", ['featured' => true]);

        $response->assertOk()->assertJsonPath('data.featured', true);
    }

    public function test_admin_can_mark_property_sold(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $response = $this->patchJson("/api/v1/admin/properties/{$property->id}/status", ['status' => 'sold']);

        $response->assertOk()->assertJsonPath('data.status', 'sold');
    }

    public function test_admin_can_mark_property_rented(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id(), 'purpose' => 'rent']);

        $response = $this->patchJson("/api/v1/admin/properties/{$property->id}/status", ['status' => 'rented']);

        $response->assertOk()->assertJsonPath('data.status', 'rented');
    }

    public function test_admin_can_soft_delete_and_restore_property(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $this->deleteJson("/api/v1/admin/properties/{$property->id}")->assertOk();
        $this->assertSoftDeleted('properties', ['id' => $property->id]);

        $this->getJson("/api/v1/properties/{$property->slug}")->assertStatus(404);

        $restore = $this->postJson("/api/v1/admin/properties/{$property->id}/restore");
        $restore->assertOk();
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'deleted_at' => null]);
    }

    public function test_admin_can_permanently_delete_property(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);
        $property->delete();

        $response = $this->deleteJson("/api/v1/admin/properties/{$property->id}/force");

        $response->assertOk();
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
    }

    public function test_non_admin_cannot_create_property(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/admin/properties', $this->payload())->assertStatus(403);
    }

    public function test_activity_log_is_created_when_property_is_created(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/admin/properties', $this->payload())->assertCreated();

        $this->assertDatabaseHas('activity_logs', ['action' => 'property.created']);
    }

    public function test_activity_log_is_created_when_property_is_updated(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $this->putJson("/api/v1/admin/properties/{$property->id}", ['title' => 'Updated Title']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'property.updated',
            'entity_type' => 'Property',
            'entity_id' => $property->id,
        ]);
    }

    public function test_activity_log_is_created_when_property_is_deleted_and_restored(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $this->deleteJson("/api/v1/admin/properties/{$property->id}");
        $this->postJson("/api/v1/admin/properties/{$property->id}/restore");

        $this->assertDatabaseHas('activity_logs', ['action' => 'property.deleted', 'entity_id' => $property->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'property.restored', 'entity_id' => $property->id]);
    }

    public function test_property_update_is_transactional_and_does_not_partially_apply_on_failure(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id(), 'title' => 'Original']);

        // An invalid enum value for status must fail validation before any DB write happens.
        $response = $this->putJson("/api/v1/admin/properties/{$property->id}", [
            'title' => 'Should Not Persist',
            'status' => 'not-a-real-status',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'title' => 'Original']);
    }

    public function test_admin_property_listing_includes_drafts_and_soft_deleted(): void
    {
        $this->actingAsAdmin();
        $draft = Property::factory()->draft()->create(['created_by' => auth()->id()]);
        $deleted = Property::factory()->create(['created_by' => auth()->id()]);
        $deleted->delete();

        $response = $this->getJson('/api/v1/admin/properties');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($draft->id));
        $this->assertTrue($ids->contains($deleted->id));
    }

    public function test_admin_property_status_rejects_invalid_enum_value(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $response = $this->patchJson("/api/v1/admin/properties/{$property->id}/status", ['status' => 'bogus']);

        $response->assertStatus(422);
    }
}
