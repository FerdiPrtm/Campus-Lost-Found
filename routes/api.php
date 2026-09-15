<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('csrf', [AuthController::class, 'csrf']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('role');
        Route::put('profile', [AuthController::class, 'updateProfile'])->middleware('role');
        Route::put('password', [AuthController::class, 'updatePassword'])->middleware('role');
    });

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('locations', [LocationController::class, 'index']);

    Route::get('items', [ItemController::class, 'index']);
    Route::get('items/{item}', [ItemController::class, 'show']);
    Route::post('items', [ItemController::class, 'store'])->middleware('role');
    Route::put('items/{item}', [ItemController::class, 'update'])->middleware('role');
    Route::delete('items/{item}', [ItemController::class, 'destroy'])->middleware('role');
    

    Route::get('notifications', [NotificationController::class, 'index'])->middleware('role');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->middleware('role');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->middleware('role');

    Route::middleware('role')->group(function () {
        Route::get('messages/conversations', [MessageController::class, 'conversations']);
        Route::get('messages/thread', [MessageController::class, 'thread']);
        Route::get('messages/unread', [MessageController::class, 'unread']);
        Route::post('messages', [MessageController::class, 'store']);
    });

    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('role');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('admin/stats', [AdminController::class, 'stats']);
        Route::get('admin/reports', [AdminController::class, 'reports']);
        Route::post('admin/reports/{item}/moderate', [AdminController::class, 'moderate']);

        Route::middleware('role:admin')->group(function () {
            Route::get('admin/users', [AdminController::class, 'users']);
            Route::put('admin/users/{user}/role', [AdminController::class, 'updateRole']);
        });
    });
});