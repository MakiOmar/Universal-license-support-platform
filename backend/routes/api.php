<?php

use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and assigned to the "api"
| middleware group. Build your application API here.
|
*/

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
        ]);
    });

    // Admin authentication routes (public)
    Route::post('/admin/login', [\App\Http\Controllers\Api\V1\AdminAuthController::class, 'login']);

    // Admin routes (protected with Sanctum - for dashboard)
    Route::middleware(['auth:sanctum', 'sanitize.input', 'rate.limit:60,1'])->prefix('admin')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\V1\AdminAuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\Api\V1\AdminAuthController::class, 'logout']);
        Route::get('/admins', [\App\Http\Controllers\Api\V1\AdminAuthController::class, 'listAdmins']);

        // Products
        Route::apiResource('products', ProductController::class);

        // Customers - specific routes must come before resource routes
        Route::post('customers/import', [CustomerController::class, 'import']);
        Route::get('customers/export', [CustomerController::class, 'export']);
        Route::get('customers/import/status', [CustomerController::class, 'importStatus']);
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/licenses', [CustomerController::class, 'getLicenses']);
        Route::get('customers/{customer}/tickets', [CustomerController::class, 'getTickets']);

        // Licenses
        Route::get('licenses/validate', [LicenseController::class, 'validateKey']);
        Route::post('licenses/activate', [LicenseController::class, 'activate']);
        Route::post('licenses/deactivate', [LicenseController::class, 'deactivate']);
        Route::get('licenses/by-key/{license_key}/activations', [LicenseController::class, 'getActivations']);
        Route::get('licenses/by-key/{license_key}/updates', [LicenseController::class, 'checkUpdates']);
        Route::apiResource('licenses', LicenseController::class);
        Route::post('licenses/bulk', [LicenseController::class, 'bulkOperation']);
        Route::post('licenses/{license}/transfer', [LicenseController::class, 'transfer']);
        Route::post('licenses/{license}/renew', [LicenseController::class, 'renew']);

        // Support tickets
        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets', [TicketController::class, 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);
        Route::put('tickets/{ticket}', [TicketController::class, 'update']);
        Route::post('tickets/{ticket}/close', [TicketController::class, 'close']);
        Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign']);
        Route::get('tickets/{ticket}/replies', [TicketController::class, 'listReplies']);
        Route::post('tickets/{ticket}/replies', [TicketController::class, 'addReply']);
        Route::post('tickets/{ticket}/attachments', [TicketController::class, 'uploadAttachment'])->middleware('secure.upload');

        // Payments
        Route::apiResource('payments', \App\Http\Controllers\Api\V1\PaymentController::class);

        // API Keys
        Route::apiResource('api-keys', \App\Http\Controllers\Api\V1\ApiKeyController::class);
        Route::post('api-keys/{api_key}/regenerate-secret', [\App\Http\Controllers\Api\V1\ApiKeyController::class, 'regenerateSecret']);
    });

    // Public customer authentication routes
    Route::post('/auth/register', [\App\Http\Controllers\Api\V1\AuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [\App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword']);

    // Public product routes (no auth required for viewing)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Customer routes (protected with customer token authentication)
    Route::middleware(['customer.auth', 'sanitize.input', 'rate.limit:60,1'])->prefix('customer')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\V1\AuthController::class, 'me']);
        Route::get('/licenses', [\App\Http\Controllers\Api\V1\CustomerController::class, 'myLicenses']);
        Route::get('/licenses/{license}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'myLicense']);
        Route::get('/tickets', [\App\Http\Controllers\Api\V1\CustomerController::class, 'myTickets']);
        Route::post('/tickets', [\App\Http\Controllers\Api\V1\TicketController::class, 'store']);
        Route::get('/tickets/{ticket}', [\App\Http\Controllers\Api\V1\CustomerController::class, 'myTicket']);
        Route::post('/tickets/{ticket}/replies', [\App\Http\Controllers\Api\V1\TicketController::class, 'addReply']);
        Route::put('/profile', [\App\Http\Controllers\Api\V1\CustomerController::class, 'updateProfile']);
    });

    // Webhook routes (no auth required, but should verify signature in production)
    Route::post('/webhooks/payment/{gateway}', [\App\Http\Controllers\Api\V1\PaymentController::class, 'webhook']);
    Route::post('/webhooks/license-activated', [\App\Http\Controllers\Api\V1\WebhookController::class, 'licenseActivated']);
    Route::post('/webhooks/license-expired', [\App\Http\Controllers\Api\V1\WebhookController::class, 'licenseExpired']);
    Route::post('/webhooks/ticket-created', [\App\Http\Controllers\Api\V1\WebhookController::class, 'ticketCreated']);
    Route::post('/webhooks/payment-received', [\App\Http\Controllers\Api\V1\WebhookController::class, 'paymentReceived']);

    // Public API routes (protected with API key - for external integrations)
    Route::middleware(['sanitize.input', 'api.key', 'rate.limit:60,1'])->group(function () {
        // Products
        Route::apiResource('products', ProductController::class);

        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/licenses', [CustomerController::class, 'getLicenses']);
        Route::get('customers/{customer}/tickets', [CustomerController::class, 'getTickets']);

        // Licenses - specific routes must come before resource routes
        Route::get('licenses/validate', [LicenseController::class, 'validateKey']);
        Route::post('licenses/activate', [LicenseController::class, 'activate']);
        Route::post('licenses/deactivate', [LicenseController::class, 'deactivate']);
        Route::get('licenses/by-key/{license_key}/activations', [LicenseController::class, 'getActivations']);
        Route::get('licenses/by-key/{license_key}/updates', [LicenseController::class, 'checkUpdates']);
        Route::apiResource('licenses', LicenseController::class);
        Route::post('licenses/bulk', [LicenseController::class, 'bulkOperation']);
        Route::post('licenses/{license}/transfer', [LicenseController::class, 'transfer']);
        Route::post('licenses/{license}/renew', [LicenseController::class, 'renew']);

        // Support tickets
        Route::get('tickets', [TicketController::class, 'index']);
        Route::post('tickets', [TicketController::class, 'store']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);
        Route::put('tickets/{ticket}', [TicketController::class, 'update']);
        Route::post('tickets/{ticket}/close', [TicketController::class, 'close']);
        Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign']);
        Route::get('tickets/{ticket}/replies', [TicketController::class, 'listReplies']);
        Route::post('tickets/{ticket}/replies', [TicketController::class, 'addReply']);
        Route::post('tickets/{ticket}/attachments', [TicketController::class, 'uploadAttachment'])->middleware('secure.upload');

        // Payments
        Route::apiResource('payments', \App\Http\Controllers\Api\V1\PaymentController::class);
    });
});
