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

    // Products
    Route::apiResource('products', ProductController::class);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Licenses
    Route::get('licenses/validate', [LicenseController::class, 'validateKey']);
    Route::post('licenses/activate', [LicenseController::class, 'activate']);
    Route::post('licenses/deactivate', [LicenseController::class, 'deactivate']);
    Route::post('licenses/{license}/transfer', [LicenseController::class, 'transfer']);
    Route::apiResource('licenses', LicenseController::class);

    // Support tickets
    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::put('tickets/{ticket}', [TicketController::class, 'update']);
    Route::post('tickets/{ticket}/close', [TicketController::class, 'close']);
    Route::get('tickets/{ticket}/replies', [TicketController::class, 'listReplies']);
    Route::post('tickets/{ticket}/replies', [TicketController::class, 'addReply']);
});



