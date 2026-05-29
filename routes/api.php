<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\VNPayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
// Authentication
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);

// Shipping (GHN proxy - public)
Route::get('/shipping/provinces', [ShippingController::class, 'provinces']);
Route::get('/shipping/districts', [ShippingController::class, 'districts']);
Route::get('/shipping/wards', [ShippingController::class, 'wards']);
Route::post('/shipping/fee', [ShippingController::class, 'calculateFee']);

// Categories & Products
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/reviews', [ReviewController::class, 'index']);

// Order (Guest order creation is allowed)
Route::post('/orders', [OrderController::class, 'store']);
Route::post('/payment/vnpay/create', [VNPayController::class, 'createPaymentUrl']);

// VNPay Callback (VNPay gọi trực tiếp, phải để public)
Route::get('/payment/vnpay/return', [VNPayController::class, 'paymentReturn']);

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Orders (yêu cầu đăng nhập)
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/return-request', [OrderController::class, 'requestReturn']);

    // Vouchers
    Route::post('/vouchers/apply', [VoucherController::class, 'apply']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // VNPay
    Route::post('/payment/vnpay/create', [VNPayController::class, 'createPaymentUrl']);

    // Reviews
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Admin Only Routes (Sanctum + Admin Check)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard analytics
    Route::get('/dashboard', [AdminController::class, 'dashboardStats']);
    Route::get('/customers', [AdminController::class, 'customersList']);

    // Staff Management
    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Admin & Staff Routes (Sanctum + Staff/Admin Check)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'staff_or_admin'])->prefix('admin')->group(function () {
    // Category Management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Product Management
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Voucher Management (admin)
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::post('/vouchers', [VoucherController::class, 'store']);
    Route::put('/vouchers/{id}', [VoucherController::class, 'update']);
    Route::delete('/vouchers/{id}', [VoucherController::class, 'destroy']);

    // Order Management
    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::put('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);
    Route::post('/orders/{id}/return-process', [OrderController::class, 'processReturn']);
    Route::post('/orders/{id}/refund-complete', [OrderController::class, 'completeRefund']);
});

