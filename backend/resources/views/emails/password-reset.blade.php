<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4f46e5; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Reset Your Password</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 20px; margin-top: 20px;">
        <p>Hello {{ $customer->first_name ? $customer->first_name : $customer->email }},</p>
        
        <p>You requested to reset your password. Click the button below to reset it:</p>
        
        <p style="margin-top: 30px; text-align: center;">
            <a href="{{ $resetUrl }}" 
               style="background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Reset Password
            </a>
        </p>
        
        <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">
            Or copy and paste this URL into your browser:<br>
            <span style="word-break: break-all;">{{ $resetUrl }}</span>
        </p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #6b7280;">
            This password reset link will expire in 1 hour. If you did not request a password reset, please ignore this email.
        </p>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; font-size: 12px; color: #6b7280; text-align: center;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>

