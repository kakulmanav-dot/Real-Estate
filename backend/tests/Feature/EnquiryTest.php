<?php

namespace Tests\Feature;

use App\Mail\EnquiryConfirmationMail;
use App\Mail\NewEnquiryAdminMail;
use App\Models\Enquiry;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_general_enquiry_can_be_submitted(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/enquiries', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I have a general question.',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('enquiries', ['email' => 'john@example.com', 'property_id' => null]);
    }

    public function test_property_specific_enquiry_can_be_submitted(): void
    {
        Mail::fake();
        $property = Property::factory()->create(['created_by' => $this->admin()->id]);

        $response = $this->postJson('/api/v1/enquiries', [
            'property_id' => $property->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'message' => 'Is this still available?',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('enquiries', ['property_id' => $property->id, 'email' => 'jane@example.com']);
    }

    public function test_enquiry_requires_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/enquiries', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'message']);
    }

    public function test_enquiry_rejects_invalid_property_id(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/enquiries', [
            'property_id' => 999999,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['property_id']);
    }

    public function test_honeypot_field_silently_rejects_submission_without_persisting(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/enquiries', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Spam message',
            'website' => 'http://spam.example.com',
        ]);

        // Bots should not be tipped off — still a success-shaped response...
        $response->assertCreated();
        // ...but nothing was actually persisted or emailed.
        $this->assertDatabaseMissing('enquiries', ['email' => 'bot@example.com']);
        Mail::assertNothingSent();
    }

    public function test_enquiry_submission_is_rate_limited(): void
    {
        Mail::fake();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/enquiries', [
                'name' => 'Spammer',
                'email' => "spam{$i}@example.com",
                'message' => 'Spam',
            ]);
        }

        $response = $this->postJson('/api/v1/enquiries', [
            'name' => 'Spammer',
            'email' => 'spam-final@example.com',
            'message' => 'Spam',
        ]);

        $response->assertStatus(429);
    }

    public function test_enquiry_captures_ip_and_user_agent(): void
    {
        Mail::fake();

        $this->withHeader('User-Agent', 'TestBrowser/1.0')
            ->postJson('/api/v1/enquiries', [
                'name' => 'John Doe',
                'email' => 'john2@example.com',
                'message' => 'Test',
            ]);

        $enquiry = Enquiry::where('email', 'john2@example.com')->first();
        $this->assertNotNull($enquiry->ip_address);
        $this->assertEquals('TestBrowser/1.0', $enquiry->user_agent);
    }

    public function test_authenticated_user_enquiry_is_associated_with_their_account(): void
    {
        Mail::fake();
        $user = $this->actingAsUser();

        $this->postJson('/api/v1/enquiries', [
            'name' => $user->name,
            'email' => $user->email,
            'message' => 'Logged in enquiry',
        ]);

        $this->assertDatabaseHas('enquiries', ['user_id' => $user->id, 'email' => $user->email]);
    }

    public function test_enquiry_persists_even_if_mail_delivery_throws(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $response = $this->postJson('/api/v1/enquiries', [
            'name' => 'Resilient User',
            'email' => 'resilient@example.com',
            'message' => 'This must still be saved.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('enquiries', ['email' => 'resilient@example.com']);
    }

    public function test_admin_notification_and_sender_confirmation_are_dispatched(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/enquiries', [
            'name' => 'Notify Me',
            'email' => 'notifyme@example.com',
            'message' => 'Please contact me.',
        ]);

        Mail::assertQueued(NewEnquiryAdminMail::class);
        Mail::assertQueued(EnquiryConfirmationMail::class, function ($mail) {
            return $mail->hasTo('notifyme@example.com');
        });
    }

    public function test_admin_can_list_and_search_enquiries(): void
    {
        $this->actingAsAdmin();
        Enquiry::factory()->create(['name' => 'Findable Person', 'email' => 'findable@example.com']);
        Enquiry::factory()->create(['name' => 'Someone Else']);

        $response = $this->getJson('/api/v1/admin/enquiries?search=Findable');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_filter_enquiries_by_status(): void
    {
        $this->actingAsAdmin();
        Enquiry::factory()->create(['status' => 'new']);
        Enquiry::factory()->create(['status' => 'spam']);

        $response = $this->getJson('/api/v1/admin/enquiries?status=spam');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_enquiry_status(): void
    {
        $this->actingAsAdmin();
        $enquiry = Enquiry::factory()->create(['status' => 'new']);

        $response = $this->patchJson("/api/v1/admin/enquiries/{$enquiry->id}", ['status' => 'contacted']);

        $response->assertOk()->assertJsonPath('data.status', 'contacted');
    }

    public function test_admin_can_assign_enquiry_to_another_admin(): void
    {
        $this->actingAsAdmin();
        $assignee = $this->admin();
        $enquiry = Enquiry::factory()->create();

        $response = $this->patchJson("/api/v1/admin/enquiries/{$enquiry->id}", ['assigned_to' => $assignee->id]);

        $response->assertOk();
        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'assigned_to' => $assignee->id]);
    }

    public function test_admin_can_add_private_notes(): void
    {
        $this->actingAsAdmin();
        $enquiry = Enquiry::factory()->create();

        $response = $this->patchJson("/api/v1/admin/enquiries/{$enquiry->id}", ['admin_notes' => 'Called, left voicemail.']);

        $response->assertOk()->assertJsonPath('data.admin_notes', 'Called, left voicemail.');
    }

    public function test_admin_can_mark_enquiry_as_spam(): void
    {
        $this->actingAsAdmin();
        $enquiry = Enquiry::factory()->create(['status' => 'new']);

        $response = $this->patchJson("/api/v1/admin/enquiries/{$enquiry->id}", ['status' => 'spam']);

        $response->assertOk()->assertJsonPath('data.status', 'spam');
    }

    public function test_admin_can_delete_enquiry(): void
    {
        $this->actingAsAdmin();
        $enquiry = Enquiry::factory()->create();

        $this->deleteJson("/api/v1/admin/enquiries/{$enquiry->id}")->assertOk();

        $this->assertDatabaseMissing('enquiries', ['id' => $enquiry->id]);
    }

    public function test_admin_can_export_enquiries_to_csv(): void
    {
        $this->actingAsAdmin();
        Enquiry::factory()->create(['name' => 'Export Me']);

        $response = $this->get('/api/v1/admin/enquiries/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Export Me', $response->streamedContent());
    }

    public function test_csv_export_neutralizes_formula_injection_payloads(): void
    {
        $this->actingAsAdmin();
        Enquiry::factory()->create([
            'name' => '=cmd|\'/C calc\'!A1',
            'subject' => '+SUM(1+1)',
            'email' => 'safe@example.com',
        ]);

        $response = $this->get('/api/v1/admin/enquiries/export');
        $content = $response->streamedContent();

        // A leading =, +, -, or @ must be neutralized with a leading quote so
        // spreadsheet software never interprets the cell as a formula.
        $this->assertStringContainsString("'=cmd", $content);
        $this->assertStringContainsString("'+SUM", $content);
        $this->assertStringNotContainsString(',=cmd', $content);
    }

    public function test_non_admin_cannot_access_enquiry_management(): void
    {
        $this->actingAsUser();
        $enquiry = Enquiry::factory()->create();

        $this->getJson('/api/v1/admin/enquiries')->assertStatus(403);
        $this->getJson("/api/v1/admin/enquiries/{$enquiry->id}")->assertStatus(403);
        $this->patchJson("/api/v1/admin/enquiries/{$enquiry->id}", ['status' => 'spam'])->assertStatus(403);
        $this->deleteJson("/api/v1/admin/enquiries/{$enquiry->id}")->assertStatus(403);
        $this->get('/api/v1/admin/enquiries/export')->assertStatus(403);
    }
}
