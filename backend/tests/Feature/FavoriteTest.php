<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_authenticated_user_can_add_favorite(): void
    {
        $this->actingAsUser();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);

        $response = $this->postJson("/api/v1/favorites/{$property->slug}");

        $response->assertCreated()->assertJsonPath('data.favorited', true);
        $this->assertDatabaseHas('favorites', ['property_id' => $property->id]);
    }

    public function test_authenticated_user_can_remove_favorite(): void
    {
        $user = $this->actingAsUser();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);
        Favorite::create(['user_id' => $user->id, 'property_id' => $property->id]);

        $response = $this->deleteJson("/api/v1/favorites/{$property->slug}");

        $response->assertOk()->assertJsonPath('data.favorited', false);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_user_can_list_their_favorites(): void
    {
        $user = $this->actingAsUser();
        $favorited = Property::factory()->create(['created_by' => $this->admin()->id]);
        Property::factory()->create(['created_by' => $this->admin()->id]); // not favorited
        Favorite::create(['user_id' => $user->id, 'property_id' => $favorited->id]);

        $response = $this->getJson('/api/v1/favorites');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $favorited->id);
    }

    public function test_duplicate_favorite_is_not_created(): void
    {
        $this->actingAsUser();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);

        $this->postJson("/api/v1/favorites/{$property->slug}")->assertCreated();
        $this->postJson("/api/v1/favorites/{$property->slug}")->assertCreated();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_database_enforces_unique_constraint_on_user_and_property(): void
    {
        $user = $this->user();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);
        Favorite::create(['user_id' => $user->id, 'property_id' => $property->id]);

        $this->expectException(QueryException::class);
        Favorite::create(['user_id' => $user->id, 'property_id' => $property->id]);
    }

    public function test_guest_cannot_manage_favorites(): void
    {
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);

        $this->postJson("/api/v1/favorites/{$property->slug}")->assertStatus(401);
        $this->getJson('/api/v1/favorites')->assertStatus(401);
        $this->deleteJson("/api/v1/favorites/{$property->slug}")->assertStatus(401);
    }

    public function test_favorites_are_isolated_per_user(): void
    {
        $userA = $this->user();
        $userB = $this->user();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);
        Favorite::create(['user_id' => $userA->id, 'property_id' => $property->id]);

        Sanctum::actingAs($userB, ['*']);

        $response = $this->getJson('/api/v1/favorites');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_draft_property_cannot_be_favorited(): void
    {
        $this->actingAsUser();
        $draft = Property::factory()->draft()->create(['created_by' => $this->admin()->id]);

        // Regression test for a fixed bug: draft properties were favoritable
        // by any authenticated user who knew (or guessed) the slug.
        $response = $this->postJson("/api/v1/favorites/{$draft->slug}");

        $response->assertStatus(404);
        $this->assertDatabaseMissing('favorites', ['property_id' => $draft->id]);
    }
}
