<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseStockController;
use App\Http\Controllers\StatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::apiResource('blogs', BlogController::class);
Route::get('/products/best-sellers', [ProductController::class, 'bestSellers']);
Route::apiResource('products', ProductController::class);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/warehouses', [WarehouseController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

    Route::post('/email/verify-notification', [AuthController::class, 'verifyEmailNotification']);
    Route::post('/products/{product}/approve', [ProductController::class, 'approve']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/fulfillment', [OrderController::class, 'employeeOrders']);
    Route::post('/orders/{order}/assign-warehouses', [OrderController::class, 'assignWarehouses']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::post('/return-requests', [ReturnRequestController::class, 'store']);
    Route::get('/return-requests', [ReturnRequestController::class, 'index']);
    Route::post('/return-requests/{returnRequest}/decide', [ReturnRequestController::class, 'decide']);
    Route::post('/warehouses/{warehouse}/stock', [WarehouseStockController::class, 'update']);
    Route::get('/stats/employee', [StatsController::class, 'employeeStats']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/products/{product}/reviews', [ReviewController::class, 'productReviews']);
