<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenerationController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\SuperAdminOnly;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/credits', [UserController::class, 'adjustCredits'])->name('users.credits');
        Route::post('users/{user}/pro', [UserController::class, 'setPro'])->name('users.pro');
        Route::post('users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');

        Route::get('generations', [GenerationController::class, 'index'])->name('generations.index');
        Route::get('generations/{generation}', [GenerationController::class, 'show'])->name('generations.show');
        Route::post('generations/{generation}/retry', [GenerationController::class, 'retry'])->name('generations.retry');
        Route::post('generations/{generation}/refund', [GenerationController::class, 'refund'])->name('generations.refund');

        Route::prefix('catalog/{resource}')->name('catalog.')->group(function () {
            Route::get('/', [CatalogController::class, 'index'])->name('index');
            Route::get('create', [CatalogController::class, 'create'])->name('create');
            Route::post('/', [CatalogController::class, 'store'])->name('store');
            Route::get('{id}/edit', [CatalogController::class, 'edit'])->name('edit');
            Route::put('{id}', [CatalogController::class, 'update'])->name('update');
            Route::delete('{id}', [CatalogController::class, 'destroy'])->name('destroy');
            Route::get('{id}/preview', [CatalogController::class, 'preview'])->name('preview');
        });

        Route::get('logs/ai', [LogController::class, 'aiRequests'])->name('logs.ai');
        Route::get('logs/credits', [LogController::class, 'creditTransactions'])->name('logs.credits');
        Route::get('logs/failed-jobs', [LogController::class, 'failedJobs'])->name('logs.failed');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings');

        Route::middleware(SuperAdminOnly::class)->group(function () {
            Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
            Route::post('settings/test', [SettingController::class, 'testConnection'])->name('settings.test');
            Route::post('logs/failed-jobs/{uuid}/retry', [LogController::class, 'retryFailedJob'])->name('logs.failed.retry');
            Route::delete('logs/failed-jobs/{uuid}', [LogController::class, 'forgetFailedJob'])->name('logs.failed.forget');
            Route::get('admins', [AdminUserController::class, 'index'])->name('admins.index');
            Route::post('admins', [AdminUserController::class, 'store'])->name('admins.store');
            Route::delete('admins/{admin}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
        });
    });
});
