<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4f46e5; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Payment Confirmation</h1>
    </div>
    
    <div style="background-color: #f9fafb; padding: 20px; margin-top: 20px;">
        <p>Hello {{ $customer->first_name ? $customer->first_name : $customer->email }},</p>
        
        <p>Thank you for your payment. Your transaction has been processed successfully.</p>
        
        <div style="background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4f46e5;">
            <p style="margin: 5px 0;"><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
            <p style="margin: 5px 0;"><strong>Amount:</strong> {{ number_format($payment->amount, 2) }} {{ strtoupper($payment->currency) }}</p>
            <p style="margin: 5px 0;"><strong>Payment Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
            <p style="margin: 5px 0;"><strong>Status:</strong> {{ ucfirst($payment->status) }}</p>
            <p style="margin: 5px 0;"><strong>Date:</strong> {{ $payment->created_at->format('F d, Y H:i') }}</p>
        </div>
        
        <p style="margin-top: 30px;">Your payment has been recorded and your account has been updated accordingly.</p>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background-color: #f3f4f6; font-size: 12px; color: #6b7280; text-align: center;">
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>

