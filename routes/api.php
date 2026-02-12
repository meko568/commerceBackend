<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\CommentController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check endpoint for Railway
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'e-commerce-backend'
    ]);
});

Route::post('/signup', [AuthController::class, 'signup']);
Route::get('/check-email', [AuthController::class, 'checkEmail']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::put('/update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');

// Users route for analytics (public for testing)
Route::get('/users', [AuthController::class, 'users']);

// Product routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/products', [ProductController::class, 'store'])->middleware('auth:sanctum');
Route::put('/products/{id}', [ProductController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('auth:sanctum');

// Comment routes
Route::get('/products/{id}/comments', [CommentController::class, 'index']);
Route::post('/products/{id}/comments', [CommentController::class, 'store'])->middleware('auth:sanctum');
Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->middleware('auth:sanctum');

// Order routes
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store'])->middleware('auth:sanctum');
Route::get('/orders/{id}', [OrderController::class, 'show'])->middleware('auth:sanctum');
Route::put('/orders/{id}', [OrderController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->middleware('auth:sanctum');

// Cache management routes (admin only)
Route::get('/cache/stats', [CacheController::class, 'stats'])->middleware('auth:sanctum');
Route::post('/cache/clear/all', [CacheController::class, 'clearAll'])->middleware('auth:sanctum');
Route::post('/cache/clear/products', [CacheController::class, 'clearProducts'])->middleware('auth:sanctum');
Route::post('/cache/clear/orders', [CacheController::class, 'clearOrders'])->middleware('auth:sanctum');
Route::post('/cache/clear/users', [CacheController::class, 'clearUsers'])->middleware('auth:sanctum');
Route::post('/cache/warmup', [CacheController::class, 'warmUp'])->middleware('auth:sanctum');
