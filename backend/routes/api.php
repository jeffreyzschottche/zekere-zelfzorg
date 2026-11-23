<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CareController;
use App\Http\Controllers\Api\V1\ExecutionController;
use App\Http\Controllers\Api\V1\FollowupController;
use App\Http\Controllers\Api\V1\ReviewController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Email verification
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/care/profiles', [CareController::class, 'profiles']);
        Route::get('/care/profiles/{profile}/today', [CareController::class, 'today']);
        Route::post('/care/profiles/{profile}/assignments', [CareController::class, 'assign']);
        Route::get('/care/profiles/{profile}/log', [ExecutionController::class, 'log']);
        Route::get('/care/profiles/{profile}/assignments/{assignment}/{day}', [ExecutionController::class, 'show']);
        Route::post('/care/profiles/{profile}/assignments/{assignment}/{day}', [ExecutionController::class, 'store']);
        Route::get('/care/profiles/{profile}/followups', [FollowupController::class, 'index']);
        Route::post('/care/profiles/{profile}/followups/{check}', [FollowupController::class, 'answer']);
        Route::get('/care/profiles/{profile}/reviews', [ReviewController::class, 'index']);
        Route::post('/care/profiles/{profile}/reviews/{execution}', [ReviewController::class, 'review']);
        Route::get('/care/profiles/{profile}/skills', [ReviewController::class, 'skills']);
        Route::post('/care/profiles/{profile}/skills', [ReviewController::class, 'assess']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/email/resend', [AuthController::class, 'resendVerification']);
    });
});
