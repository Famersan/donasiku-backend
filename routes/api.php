<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\AdminController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::get('/campaigns',        [CampaignController::class, 'index']);
Route::get('/campaigns/{slug}', [CampaignController::class, 'show']);
Route::get('/leaderboard',      [LeaderboardController::class, 'index']);
Route::post('/donations/notification', [DonationController::class, 'notification']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/donations',        [DonationController::class, 'store']);
    Route::get('/donations/history', [DonationController::class, 'history']);
    Route::get('/donations/{id}',    [DonationController::class, 'show']);

    Route::middleware('admin')->group(function () {
        Route::post('/campaigns',        [CampaignController::class, 'store']);
        Route::put('/campaigns/{id}',    [CampaignController::class, 'update']);
        Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy']);
        Route::get('/admin/donations',         [AdminController::class, 'donations']);
        Route::get('/admin/users',             [AdminController::class, 'users']);
        Route::put('/admin/users/{id}/role',   [AdminController::class, 'updateUserRole']);
    });
});
