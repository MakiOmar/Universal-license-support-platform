<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Created</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4f46e5; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Support Ticket Created</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 20px; margin-top: 20px;">
        <p>Hello {{ $customer->first_name ? $customer->first_name : $customer->email }},</p>
        
        <p>Your support ticket has been created successfully.</p>
        
        <div style="background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4f46e5;">
            <p style="margin: 5px 0;"><strong>Ticket Number:</strong> {{ $ticket->ticket_number }}</p>
            <p style="margin: 5px 0;"><strong>Subject:</strong> {{ $ticket->subject }}</p>
            <p style="margin: 5px 0;"><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>
            <p style="margin: 5px 0;"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</p>
        </div>
        
        <div style="background-color: white; padding: 15px; margin: 20px 0;">
            <p style="margin-top: 0;"><strong>Description:</strong></p>
            <p style="white-space: pre-wrap;">{{ $ticket->description }}</p>
        </div>
        
        <p style="margin-top: 30px;">Our support team will review your ticket and respond as soon as possible.</p>
        
        <p style="margin-top: 20px;">
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/tickets/{{ $ticket->id }}" 
               style="background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                View Ticket
            </a>
        </p>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; font-size: 12px; color: #6b7280; text-align: center;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>

