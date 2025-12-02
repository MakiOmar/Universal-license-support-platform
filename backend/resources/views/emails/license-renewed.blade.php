<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Renewed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h1 style="color: #4a5568; margin: 0;">License Renewed</h1>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e2e8f0; border-radius: 5px;">
        <p>Hello {{ $customer->first_name ?? $customer->email }},</p>

        <p>Your license for <strong>{{ $product->name }}</strong> has been successfully renewed.</p>

        <div style="background-color: #f7fafc; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>License Key:</strong> <code style="background-color: #edf2f7; padding: 2px 6px; border-radius: 3px;">{{ $license->license_key }}</code></p>
            <p style="margin: 5px 0;"><strong>Renewal Period:</strong> {{ $periodValue }} {{ $periodUnit }}</p>
            <p style="margin: 5px 0;"><strong>New Expiration Date:</strong> {{ $license->expires_at ? $license->expires_at->format('F j, Y') : 'N/A' }}</p>
            <p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: #48bb78; font-weight: bold;">{{ ucfirst($license->status) }}</span></p>
        </div>

        <p>Your license is now active and will remain valid until {{ $license->expires_at ? $license->expires_at->format('F j, Y') : 'further notice' }}.</p>

        <p>Thank you for your continued support!</p>

        <p style="margin-top: 30px;">
            Best regards,<br>
            {{ config('app.name') }} Team
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #718096; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>

