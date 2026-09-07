<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Thank you for reaching out, {{ $enquiry->name }}!</h2>
    <p>We've received your enquiry and a member of our team will get back to you shortly.</p>
    @if ($enquiry->property)
        <p><strong>Regarding:</strong> {{ $enquiry->property->title }}</p>
    @endif
    <p><strong>Your message:</strong></p>
    <p>{{ $enquiry->message }}</p>
    <hr>
    <p style="color: #6b7280; font-size: 12px;">{{ config('app.name') }}</p>
</body>
</html>
