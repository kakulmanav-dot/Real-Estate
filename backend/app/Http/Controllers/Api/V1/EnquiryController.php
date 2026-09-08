<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enquiry\StoreEnquiryRequest;
use App\Mail\EnquiryConfirmationMail;
use App\Mail\NewEnquiryAdminMail;
use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnquiryController extends Controller
{
    use ApiResponse;

    public function store(StoreEnquiryRequest $request)
    {
        // Honeypot: silently accept without persisting to avoid tipping off bots.
        if ($request->filled('website')) {
            return $this->success(null, 'Thank you for your message.', 201);
        }

        $enquiry = Enquiry::create([
            ...$request->safe()->only(['property_id', 'name', 'email', 'phone', 'subject', 'message', 'source']),
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $this->dispatchNotifications($enquiry);

        return $this->success(null, 'Thank you for your message. We will get back to you shortly.', 201);
    }

    private function dispatchNotifications(Enquiry $enquiry): void
    {
        $adminEmail = Setting::allSettings()['company_email'] ?? config('mail.from.address');

        try {
            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new NewEnquiryAdminMail($enquiry));
            }

            Mail::to($enquiry->email)->queue(new EnquiryConfirmationMail($enquiry));
        } catch (\Throwable $e) {
            // Never let a mail transport failure roll back an already-saved enquiry.
            Log::warning('Failed to send enquiry notification email', [
                'enquiry_id' => $enquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
