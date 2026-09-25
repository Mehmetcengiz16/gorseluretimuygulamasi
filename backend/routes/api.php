<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\GenerationController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Middleware\EnsureAppAvailable;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([EnsureAppAvailable::class, 'throttle:api'])->group(function () {
    Route::get('app/config', [CatalogController::class, 'config']);

    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('me', [MeController::class, 'show']);
        Route::post('me', [MeController::class, 'update']); // multipart (avatar) için POST
        Route::delete('me', [MeController::class, 'destroy']);
        Route::get('me/credit-transactions', [MeController::class, 'creditTransactions']);
        Route::get('favorites', [MeController::class, 'favorites']);

        Route::get('catalog', [CatalogController::class, 'catalog']);
        Route::get('templates', [CatalogController::class, 'templates']);
        Route::get('templates/{template}', [CatalogController::class, 'template']);
        Route::post('templates/{template}/like', [CatalogController::class, 'toggleLike']);

        Route::get('projects', [ProjectController::class, 'index']);
        Route::post('projects', [ProjectController::class, 'store']);
        Route::get('projects/{project}', [ProjectController::class, 'show']);
        Route::patch('projects/{project}', [ProjectController::class, 'update']);
        Route::post('projects/{project}/image', [ProjectController::class, 'replaceImage']);
        Route::post('projects/{project}/cutout', [ProjectController::class, 'cutout']);
        Route::delete('projects/{project}', [ProjectController::class, 'destroy']);

        Route::post('prompt/enhance', [GenerationController::class, 'enhancePrompt'])->middleware('throttle:enhance');
        Route::post('generations/estimate', [GenerationController::class, 'estimate']);
        Route::post('projects/{project}/generations', [GenerationController::class, 'store'])->middleware('throttle:generations');
        Route::get('generations/{generation}', [GenerationController::class, 'show']);
        Route::post('generations/{generation}/more', [GenerationController::class, 'more'])->middleware('throttle:generations');
        Route::patch('generation-images/{image}', [GenerationController::class, 'updateImage']);
        Route::post('generation-images/{image}/edit', [GenerationController::class, 'editImage'])->middleware('throttle:generations');
        Route::get('generation-images/{image}/download', [GenerationController::class, 'download']);
    });
});
