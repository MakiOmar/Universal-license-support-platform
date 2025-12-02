<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Completed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4f46e5; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Import Completed</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 20px; margin-top: 20px;">
        <p>Hello,</p>
        
        <p>Your {{ ucfirst($importType) }} import has been completed.</p>
        
        <div style="background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4f46e5;">
            <h3 style="margin-top: 0;">Import Summary</h3>
            <p style="margin: 5px 0;"><strong>Total Processed:</strong> {{ $total }}</p>
            <p style="margin: 5px 0;"><strong>Successfully Imported:</strong> <span style="color: #10b981;">{{ $imported }}</span></p>
            <p style="margin: 5px 0;"><strong>Skipped/Failed:</strong> <span style="color: #ef4444;">{{ $skipped }}</span></p>
        </div>
        
        @if(count($errors) > 0)
        <div style="background-color: #fef2f2; padding: 15px; margin: 20px 0; border-left: 4px solid #ef4444;">
            <h3 style="margin-top: 0; color: #dc2626;">Errors ({{ count($errors) }})</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                @foreach(array_slice($errors, 0, 10) as $error)
                <li style="margin: 5px 0; font-size: 12px;">{{ $error }}</li>
                @endforeach
            </ul>
            @if(count($errors) > 10)
            <p style="font-size: 12px; color: #6b7280;">... and {{ count($errors) - 10 }} more errors</p>
            @endif
        </div>
        @endif
        
        <p style="margin-top: 30px;">You can view the imported {{ $importType }} in the admin dashboard.</p>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; font-size: 12px; color: #6b7280; text-align: center;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>

