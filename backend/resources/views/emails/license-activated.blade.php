<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Activated</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4f46e5; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">License Activated</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 20px; margin-top: 20px;">
        <p>Hello {{ $customer->first_name ? $customer->first_name : $customer->email }},</p>
        
        <p>Your license for <strong>{{ $product->name }}</strong> has been successfully activated.</p>
        
        <div style="background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4f46e5;">
            <p style="margin: 5px 0;"><strong>License Key:</strong></p>
            <p style="margin: 5px 0; font-family: monospace; font-size: 14px; background-color: #f3f4f6; padding: 10px; border-radius: 4px;">{{ $license->license_key }}</p>
        </div>
        
        <p><strong>Product:</strong> {{ $product->name }}</p>
        <p><strong>License Type:</strong> {{ $license->license_type }}</p>
        @if($license->expires_at)
        <p><strong>Expires:</strong> {{ $license->expires_at->format('F d, Y') }}</p>
        @endif
        
        <p style="margin-top: 30px;">You can view and manage your licenses in your customer portal.</p>
        
        <p style="margin-top: 20px;">
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/licenses/{{ $license->id }}" 
               style="background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                View License
            </a>
        </p>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; font-size: 12px; color: #6b7280; text-align: center;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>

