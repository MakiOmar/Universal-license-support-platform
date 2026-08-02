<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CustomerLicenseController;
use App\Http\Controllers\Api\V1\CustomerTicketController;
use App\Http\Controllers\Api\V1\LicenseIntegrationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product:slug}', [ProductController::class, 'show']);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware(['api.key', 'throttle:60,1'])->group(function (): void {
        Route::post('/licenses/validate', [LicenseIntegrationController::class, 'validate']);
        Route::post('/licenses/activate', [LicenseIntegrationController::class, 'activate']);
        Route::post('/licenses/deactivate', [LicenseIntegrationController::class, 'deactivate']);
        Route::get('/licenses/by-key/{licenseKey}/activations', [LicenseIntegrationController::class, 'activations']);
    });

    Route::post('/webhooks/payment/stripe', [StripeWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/customer/me', [AuthController::class, 'me']);
        Route::put('/customer/profile', [AuthController::class, 'updateProfile']);

        Route::get('/customer/licenses', [CustomerLicenseController::class, 'index']);
        Route::get('/customer/licenses/{license}', [CustomerLicenseController::class, 'show']);
        Route::get('/customer/licenses/{license}/activations', [CustomerLicenseController::class, 'activations']);
        Route::delete('/customer/licenses/{license}/activations/{activation}', [CustomerLicenseController::class, 'deactivateActivation']);

        Route::get('/customer/tickets', [CustomerTicketController::class, 'index']);
        Route::post('/customer/tickets', [CustomerTicketController::class, 'store']);
        Route::get('/customer/tickets/{ticket}', [CustomerTicketController::class, 'show']);
        Route::post('/customer/tickets/{ticket}/replies', [CustomerTicketController::class, 'reply']);
        Route::get('/customer/tickets/{ticket}/attachments/{attachment}', [CustomerTicketController::class, 'downloadAttachment']);

        Route::get('/customer/payments', [\App\Http\Controllers\Api\V1\CustomerPaymentController::class, 'index']);
        Route::get('/customer/payments/{payment}', [\App\Http\Controllers\Api\V1\CustomerPaymentController::class, 'show']);

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/email/verification-notification', [AuthController::class, 'sendVerificationEmail']);
        Route::post('/auth/email/verify', [AuthController::class, 'verifyEmail']);

        Route::post('/checkout/session', [CheckoutController::class, 'create']);
    });
});
