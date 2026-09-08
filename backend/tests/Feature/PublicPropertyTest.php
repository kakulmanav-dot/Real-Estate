<?php

namespace Tests\Feature;

use App\Enums\PropertyStatus;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class PublicPropertyTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_published_properties_are_listed(): void
    {
        Property::factory()->count(3)->create(['created_by' => $this->admin()->id]);

        $response = $this->getJson('/api/v1/properties');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_draft_properties_are_hidden_from_listing(): void
    {
        Property::factory()->draft()->create(['created_by' => $this->admin()->id]);

        $response = $this->getJson('/api/v1/properties');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_archived_properties_are_hidden_from_listing(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'status' => PropertyStatus::Archived, 'published_at' => now()]);

        $this->getJson('/api/v1/properties')->assertJsonCount(0, 'data');
    }

    public function test_soft_deleted_properties_are_hidden_from_listing(): void
    {
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);
        $property->delete();

        $this->getJson('/api/v1/properties')->assertJsonCount(0, 'data');
    }

    public function test_property_details_resolve_by_slug(): void
    {
        $property = Property::factory()->create(['created_by' => $this->admin()->id, 'title' => 'Ocean View Villa']);

        $response = $this->getJson("/api/v1/properties/{$property->slug}");

        $response->assertOk()->assertJsonPath('data.title', 'Ocean View Villa');
    }

    public function test_missing_slug_returns_404(): void
    {
        $this->getJson('/api/v1/properties/does-not-exist')->assertStatus(404);
    }

    public function test_featured_endpoint_only_returns_featured_published_properties(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'featured' => true]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'featured' => false]);

        $response = $this->getJson('/api/v1/properties/featured');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_similar_properties_endpoint(): void
    {
        $property = Property::factory()->create(['created_by' => $this->admin()->id, 'city' => 'Austin']);
        Property::factory()->count(2)->create(['created_by' => $this->admin()->id, 'city' => 'Austin']);
        Property::factory()->create(['created_by' => $this->admin()->id, 'city' => 'Denver', 'property_type' => 'Studio']);

        $response = $this->getJson("/api/v1/properties/{$property->slug}/similar");

        $response->assertOk();
        $this->assertLessThanOrEqual(4, count($response->json('data')));
    }

    public function test_pagination_structure_is_present(): void
    {
        Property::factory()->count(15)->create(['created_by' => $this->admin()->id]);

        $response = $this->getJson('/api/v1/properties?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_search_by_title(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'title' => 'Sunset Ridge Villa']);
        Property::factory()->create(['created_by' => $this->admin()->id, 'title' => 'Downtown Loft']);

        $response = $this->getJson('/api/v1/properties?search=Sunset');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Sunset Ridge Villa');
    }

    public function test_search_by_city(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'city' => 'Miami']);
        Property::factory()->create(['created_by' => $this->admin()->id, 'city' => 'Dallas']);

        $this->getJson('/api/v1/properties?search=Miami')->assertJsonCount(1, 'data');
    }

    public function test_search_by_reference_number(): void
    {
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);

        $response = $this->getJson('/api/v1/properties?search='.$property->reference_number);

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_filter_by_purpose(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'purpose' => 'sale']);
        Property::factory()->create(['created_by' => $this->admin()->id, 'purpose' => 'rent']);

        $this->getJson('/api/v1/properties?purpose=rent')->assertJsonCount(1, 'data');
    }

    public function test_filter_by_property_type(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'property_type' => 'Studio']);
        Property::factory()->create(['created_by' => $this->admin()->id, 'property_type' => 'Villa']);

        $this->getJson('/api/v1/properties?property_type=Studio')->assertJsonCount(1, 'data');
    }

    public function test_filter_by_city(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'city' => 'Seattle']);
        Property::factory()->create(['created_by' => $this->admin()->id, 'city' => 'Boston']);

        $this->getJson('/api/v1/properties?city=Seattle')->assertJsonCount(1, 'data');
    }

    public function test_filter_by_bedrooms(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'bedrooms' => 2]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'bedrooms' => 4]);

        $this->getJson('/api/v1/properties?bedrooms=4')->assertJsonCount(1, 'data');
    }

    public function test_filter_by_price_range(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 100000]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 500000]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 900000]);

        $response = $this->getJson('/api/v1/properties?min_price=200000&max_price=600000');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.price', 500000);
    }

    public function test_filter_by_area_range(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'area' => 800]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'area' => 2000]);

        $this->getJson('/api/v1/properties?min_area=1000&max_area=3000')->assertJsonCount(1, 'data');
    }

    public function test_sort_newest_first(): void
    {
        $older = Property::factory()->create(['created_by' => $this->admin()->id, 'published_at' => now()->subDays(5)]);
        $newer = Property::factory()->create(['created_by' => $this->admin()->id, 'published_at' => now()]);

        $response = $this->getJson('/api/v1/properties?sort=newest');

        $response->assertJsonPath('data.0.id', $newer->id);
    }

    public function test_sort_price_ascending(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 300000]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 100000]);

        $response = $this->getJson('/api/v1/properties?sort=price_asc');

        $response->assertJsonPath('data.0.price', 100000);
    }

    public function test_sort_price_descending(): void
    {
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 300000]);
        Property::factory()->create(['created_by' => $this->admin()->id, 'price' => 100000]);

        $response = $this->getJson('/api/v1/properties?sort=price_desc');

        $response->assertJsonPath('data.0.price', 300000);
    }

    public function test_invalid_filter_parameters_return_validation_errors(): void
    {
        $response = $this->getJson('/api/v1/properties?purpose=invalid-value');

        $response->assertStatus(422)->assertJsonValidationErrors(['purpose']);
    }

    public function test_invalid_price_range_returns_validation_error(): void
    {
        $response = $this->getJson('/api/v1/properties?min_price=500&max_price=100');

        $response->assertStatus(422)->assertJsonValidationErrors(['max_price']);
    }

    public function test_property_listing_query_count_does_not_grow_with_more_properties(): void
    {
        Property::factory()->count(3)->create(['created_by' => $this->admin()->id]);

        DB::enableQueryLog();
        $this->getJson('/api/v1/properties');
        $queriesForThree = count(DB::getQueryLog());
        DB::flushQueryLog();

        Property::factory()->count(10)->create(['created_by' => $this->admin()->id]);
        DB::flushQueryLog(); // discard the factory INSERT queries themselves

        $this->getJson('/api/v1/properties?per_page=20');
        $queriesForThirteen = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Query count should stay flat regardless of row count (no N+1 on the images relation).
        $this->assertEquals($queriesForThree, $queriesForThirteen);
    }
}
