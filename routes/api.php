<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PaystackWebhookController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'apiLogin']);
// TODO: Re-enable when role-based system is implemented
// Route::post('/register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'apiStore']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'apiDestroy']);
    
    // Dashboard data
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // User profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    
    // Health check (protected)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'paystack healthy',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'user' => auth()->user()->username ?? 'unknown'
        ]);
    });

    // Payments (from Nexus)
    Route::prefix('payments')->group(function () {
        Route::post('/', [PaymentController::class, 'store']);
    });
});

// Public health check
Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'timestamp' => now()->format('Y-m-d H:i:s.v')
    ]);
});

// Paystack Webhook Routes (Public - no authentication required)
Route::prefix('webhooks/paystack')->group(function () {
    // Payment webhook
    Route::post('/payment', [PaystackWebhookController::class, 'handlePaymentWebhook']);
    
    // Transfer webhook - Not currently used, commented out for security
    // Route::post('/transfer', [PaystackWebhookController::class, 'handleTransferWebhook']);
    
    // Generic webhook (catch-all) - Not currently used, commented out for security
    // Route::post('/generic', [PaystackWebhookController::class, 'handleGenericWebhook']);
    
    // Test webhook endpoint - Not currently used, commented out for security
    // Route::post('/test', [PaystackWebhookController::class, 'handleGenericWebhook']);
});
