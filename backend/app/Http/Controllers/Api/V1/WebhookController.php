<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Payment;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle license activated webhook.
     */
    public function licenseActivated(Request $request)
    {
        $data = $request->validate([
            'license_id' => ['required', 'integer', 'exists:licenses,id'],
            'activation_id' => ['nullable', 'integer'],
        ]);

        $license = License::with(['customer', 'product'])->findOrFail($data['license_id']);

        // Log webhook event
        Log::info('License activated webhook', [
            'license_id' => $license->id,
            'license_key' => $license->license_key,
            'customer_id' => $license->customer_id,
        ]);

        // In production, dispatch event for notifications
        // event(new LicenseActivated($license));

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
            'license' => new \App\Http\Resources\Api\V1\LicenseResource($license),
        ]);
    }

    /**
     * Handle license expired webhook.
     */
    public function licenseExpired(Request $request)
    {
        $data = $request->validate([
            'license_id' => ['required', 'integer', 'exists:licenses,id'],
        ]);

        $license = License::with(['customer', 'product'])->findOrFail($data['license_id']);

        Log::info('License expired webhook', [
            'license_id' => $license->id,
            'license_key' => $license->license_key,
        ]);

        // Update license status if needed
        if ($license->status === 'active') {
            $license->status = 'expired';
            $license->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
        ]);
    }

    /**
     * Handle ticket created webhook.
     */
    public function ticketCreated(Request $request)
    {
        $data = $request->validate([
            'ticket_id' => ['required', 'integer', 'exists:support_tickets,id'],
        ]);

        $ticket = SupportTicket::with(['customer', 'product'])->findOrFail($data['ticket_id']);

        Log::info('Ticket created webhook', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'customer_id' => $ticket->customer_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
            'ticket' => new \App\Http\Resources\Api\V1\SupportTicketResource($ticket),
        ]);
    }

    /**
     * Handle payment received webhook.
     */
    public function paymentReceived(Request $request)
    {
        $data = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ]);

        $payment = Payment::with(['customer', 'license'])->findOrFail($data['payment_id']);

        Log::info('Payment received webhook', [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => $payment->amount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed',
            'payment' => $payment,
        ]);
    }
}

