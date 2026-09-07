<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>New enquiry received</h2>
    <p><strong>Name:</strong> {{ $enquiry->name }}</p>
    <p><strong>Email:</strong> {{ $enquiry->email }}</p>
    <p><strong>Phone:</strong> {{ $enquiry->phone ?? 'N/A' }}</p>
    @if ($enquiry->property)
        <p><strong>Property:</strong> {{ $enquiry->property->title }} ({{ $enquiry->property->reference_number }})</p>
    @endif
    <p><strong>Subject:</strong> {{ $enquiry->subject ?? 'General enquiry' }}</p>
    <p><strong>Message:</strong></p>
    <p>{{ $enquiry->message }}</p>
    <hr>
    <p style="color: #6b7280; font-size: 12px;">Received {{ $enquiry->created_at }} from {{ $enquiry->ip_address }}</p>
</body>
</html>
