<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_only_approved_testimonials_are_public(): void
    {
        Testimonial::factory()->create(['is_approved' => true, 'sort_order' => 1]);
        Testimonial::factory()->create(['is_approved' => false, 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/testimonials');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_public_testimonials_are_ordered_by_sort_order(): void
    {
        Testimonial::factory()->create(['is_approved' => true, 'sort_order' => 2, 'name' => 'Second']);
        Testimonial::factory()->create(['is_approved' => true, 'sort_order' => 1, 'name' => 'First']);

        $response = $this->getJson('/api/v1/testimonials');

        $response->assertJsonPath('data.0.name', 'First')->assertJsonPath('data.1.name', 'Second');
    }

    public function test_admin_can_create_testimonial(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/testimonials', [
            'name' => 'Happy Customer',
            'text' => 'Great experience overall.',
            'rating' => 5,
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Happy Customer');
    }

    public function test_admin_can_update_testimonial(): void
    {
        $this->actingAsAdmin();
        $testimonial = Testimonial::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/admin/testimonials/{$testimonial->id}", ['name' => 'New Name']);

        $response->assertOk()->assertJsonPath('data.name', 'New Name');
    }

    public function test_admin_can_delete_testimonial(): void
    {
        $this->actingAsAdmin();
        $testimonial = Testimonial::factory()->create();

        $this->deleteJson("/api/v1/admin/testimonials/{$testimonial->id}")->assertOk();

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_admin_can_toggle_approval(): void
    {
        $this->actingAsAdmin();
        $testimonial = Testimonial::factory()->create(['is_approved' => false]);

        $response = $this->patchJson("/api/v1/admin/testimonials/{$testimonial->id}/approval");

        $response->assertOk()->assertJsonPath('data.is_approved', true);
    }

    public function test_admin_can_upload_and_replace_testimonial_image(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $testimonial = Testimonial::factory()->create();

        $upload = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');
        $formData = ['name' => $testimonial->name, 'text' => $testimonial->text, 'rating' => $testimonial->rating, 'image' => $upload, '_method' => 'PUT'];

        $response = $this->post("/api/v1/admin/testimonials/{$testimonial->id}", $formData);

        $response->assertOk();
        $this->assertNotNull($testimonial->fresh()->image_path);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/admin/testimonials', [
            'name' => 'Bad Rating',
            'text' => 'Test',
            'rating' => 6,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    public function test_non_admin_cannot_manage_testimonials(): void
    {
        $this->actingAsUser();
        $testimonial = Testimonial::factory()->create();

        $this->postJson('/api/v1/admin/testimonials', ['name' => 'X', 'text' => 'Y', 'rating' => 5])->assertStatus(403);
        $this->putJson("/api/v1/admin/testimonials/{$testimonial->id}", ['name' => 'Z'])->assertStatus(403);
        $this->deleteJson("/api/v1/admin/testimonials/{$testimonial->id}")->assertStatus(403);
        $this->patchJson("/api/v1/admin/testimonials/{$testimonial->id}/approval")->assertStatus(403);
    }
}
