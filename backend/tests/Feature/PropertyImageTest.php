<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class PropertyImageTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Build a fake upload with a real image MIME type without requiring the
     * GD extension (this environment does not have it installed).
     */
    private function fakeImage(string $name, int $sizeKb = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $sizeKb, 'image/jpeg');
    }

    public function test_admin_can_upload_multiple_images(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $response = $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [
                $this->fakeImage('one.jpg'),
                $this->fakeImage('two.jpg'),
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('property_images', 2);
    }

    public function test_image_upload_rejects_invalid_mime_type(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $response = $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload')],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['images.0']);
        $this->assertDatabaseCount('property_images', 0);
    }

    public function test_image_upload_rejects_disguised_executable_with_image_extension(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        // Same payload as above but named like an image — MIME sniffing must still reject it.
        $response = $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [UploadedFile::fake()->create('fake.jpg', 10, 'application/x-msdownload')],
        ]);

        $response->assertStatus(422);
    }

    public function test_image_upload_rejects_oversized_file(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $response = $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [$this->fakeImage('huge.jpg', 6000)], // over the 5MB (5120KB) limit
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['images.0']);
    }

    public function test_first_uploaded_image_becomes_cover_by_default(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [$this->fakeImage('one.jpg'), $this->fakeImage('two.jpg')],
        ])->assertCreated();

        $this->assertDatabaseHas('property_images', ['sort_order' => 1, 'is_cover' => true]);
        $this->assertDatabaseHas('property_images', ['sort_order' => 2, 'is_cover' => false]);
    }

    public function test_admin_can_set_a_different_cover_image(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);
        $imageA = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'properties/1/a.jpg', 'sort_order' => 1, 'is_cover' => true]);
        $imageB = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'properties/1/b.jpg', 'sort_order' => 2, 'is_cover' => false]);

        $response = $this->patchJson("/api/v1/admin/properties/{$property->id}/images/{$imageB->id}/cover");

        $response->assertOk();
        $this->assertDatabaseHas('property_images', ['id' => $imageB->id, 'is_cover' => true]);
        $this->assertDatabaseHas('property_images', ['id' => $imageA->id, 'is_cover' => false]);
    }

    public function test_only_one_cover_image_can_exist_per_property(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);
        $imageA = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'a.jpg', 'sort_order' => 1, 'is_cover' => true]);
        $imageB = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'b.jpg', 'sort_order' => 2, 'is_cover' => false]);

        $this->patchJson("/api/v1/admin/properties/{$property->id}/images/{$imageB->id}/cover");

        $coverCount = PropertyImage::where('property_id', $property->id)->where('is_cover', true)->count();
        $this->assertEquals(1, $coverCount);
    }

    public function test_admin_can_reorder_images(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);
        $imageA = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'a.jpg', 'sort_order' => 1]);
        $imageB = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'b.jpg', 'sort_order' => 2]);

        $response = $this->postJson("/api/v1/admin/properties/{$property->id}/images/reorder", [
            'order' => [$imageB->id, $imageA->id],
        ]);

        $response->assertOk();
        $this->assertEquals(1, $imageB->fresh()->sort_order);
        $this->assertEquals(2, $imageA->fresh()->sort_order);
    }

    public function test_admin_can_update_image_alt_text(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);
        $image = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'a.jpg', 'sort_order' => 1]);

        $response = $this->putJson("/api/v1/admin/properties/{$property->id}/images/{$image->id}", ['alt_text' => 'Front view']);

        $response->assertOk()->assertJsonPath('data.alt_text', 'Front view');
    }

    public function test_admin_can_delete_an_individual_image(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);
        $file = $this->fakeImage('to-delete.jpg');
        $path = $file->store("properties/{$property->id}", 'public');
        $image = PropertyImage::create(['property_id' => $property->id, 'image_path' => $path, 'sort_order' => 1]);

        Storage::disk('public')->assertExists($path);

        $response = $this->deleteJson("/api/v1/admin/properties/{$property->id}/images/{$image->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('property_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_unauthorized_user_cannot_manage_property_images(): void
    {
        $this->actingAsUser();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);

        $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [$this->fakeImage('one.jpg')],
        ])->assertStatus(403);
    }

    public function test_uploaded_filenames_are_not_derived_from_the_original_client_filename(): void
    {
        $this->actingAsAdmin();
        $property = Property::factory()->create(['created_by' => auth()->id()]);

        $this->postJson("/api/v1/admin/properties/{$property->id}/images", [
            'images' => [$this->fakeImage('../../etc/passwd.jpg')],
        ])->assertCreated();

        $image = PropertyImage::first();
        $this->assertStringNotContainsString('passwd', $image->image_path);
        $this->assertStringNotContainsString('..', $image->image_path);
    }

    public function test_property_image_url_is_correctly_formatted(): void
    {
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);
        $image = PropertyImage::create(['property_id' => $property->id, 'image_path' => 'properties/1/photo.jpg', 'sort_order' => 1, 'is_cover' => true]);

        $response = $this->getJson("/api/v1/properties/{$property->slug}");

        $url = $response->json('data.images.0.url');
        $this->assertStringContainsString('/storage/properties/1/photo.jpg', $url);
    }
}
