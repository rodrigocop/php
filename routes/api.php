<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('article.editor.active')->group(function () {
        Route::get('/articles', [ArticleController::class, 'index']);
        Route::get('/articles/{article}', [ArticleController::class, 'show']);
        Route::post('/articles', [ArticleController::class, 'store']);
        Route::put('/articles/{article}', [ArticleController::class, 'update']);
        Route::patch('/articles/{article}', [ArticleController::class, 'update']);
        Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    });

    Route::apiResource('categories', CategoryController::class);

    Route::middleware('role.admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
