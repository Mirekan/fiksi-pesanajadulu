<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', [AuthController::class, 'user'])->name('user');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::apiResource('table', TableController::class);
    Route::apiResource('menu', MenuController::class);
    
});
