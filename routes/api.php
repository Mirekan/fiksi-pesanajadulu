<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', action: [AuthController::class, 'user'])->name('user');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::apiResource('table', TableController::class);
    Route::apiResource('menu', MenuController::class);
    Route::apiResource('restaurant', RestaurantController::class);

    Route::post('order/store', [OrderController::class, 'store']);
    Route::get('order', [OrderController::class, 'index']);
    Route::get('order/{id}', [OrderController::class, 'show']);
    Route::post('payment/handle', [PaymentController::class, 'handlePayment']);
    Route::get('payment/status', [PaymentController::class, 'getPaymentStatus']);
    Route::post('order/cancel', [PaymentController::class, 'cancelOrder']);
});

// Midtrans webhook - no auth required
Route::post('payment/notification', [PaymentController::class, 'handleNotification']);
