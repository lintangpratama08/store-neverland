<?php

use App\Http\Controllers\AccountListingController;
use App\Http\Controllers\AdminDataController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MediaAssetController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\VisitorPresenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('throttle:api')->group(function (): void {
    Route::get('public/members', [AuthController::class, 'publicMembers']);
    Route::get('public/teams', [TeamController::class, 'index']);
    Route::get('public/tournaments', [TournamentController::class, 'index']);
    Route::get('public/media', [MediaAssetController::class, 'publicIndex']);
    Route::get('public/products', [ProductController::class, 'publicIndex']);
    Route::get('public/accounts', [AccountListingController::class, 'publicIndex']);
    Route::get('public/sponsors', [SponsorController::class, 'publicIndex']);
    Route::post('public/presence', [VisitorPresenceController::class, 'heartbeat'])->middleware('throttle:presence');
    Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:orders');

    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware('auth')->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::match(['put', 'post'], 'profile', [AuthController::class, 'updateProfile']);
        Route::post('tournaments/{tournament}/register', [TournamentController::class, 'register']);
        Route::get('tournaments/{tournament}/contact/{nickname}', [TournamentController::class, 'participantContact']);
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('admin/reset-data', [AdminDataController::class, 'reset'])->middleware('throttle:reset-data');
        Route::get('admin/members', [AuthController::class, 'adminMembers']);
        Route::post('admin/members', [AuthController::class, 'storeMember']);
        Route::put('admin/members/{user}', [AuthController::class, 'updateMember']);
        Route::delete('admin/members/{user}', [AuthController::class, 'destroyMember']);
        Route::apiResource('teams', TeamController::class)->except(['index', 'show']);
        Route::post('teams/transfer', [TeamController::class, 'transfer']);
        Route::get('admin/tournaments', [TournamentController::class, 'adminIndex']);
        Route::apiResource('tournaments', TournamentController::class)->except(['index', 'show']);
        Route::post('tournaments/{tournament}/randomize', [TournamentController::class, 'randomize']);
        Route::put('tournaments/{tournament}/bracket', [TournamentController::class, 'updateBracket']);
        Route::post('tournaments/{tournament}/participants', [TournamentController::class, 'addParticipant']);
        Route::delete('tournaments/{tournament}/participants/{user}', [TournamentController::class, 'removeParticipant']);
        Route::get('admin/media', [MediaAssetController::class, 'index']);
        Route::apiResource('media', MediaAssetController::class)->parameters(['media' => 'media'])->except(['index', 'show']);
        Route::get('admin/products', [ProductController::class, 'index']);
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        Route::get('admin/accounts', [AccountListingController::class, 'index']);
        Route::apiResource('account-listings', AccountListingController::class)->except(['index', 'show']);
        Route::delete('account-listing-images/{image}', [AccountListingController::class, 'destroyImage']);
        Route::get('admin/sponsors', [SponsorController::class, 'index']);
        Route::apiResource('sponsors', SponsorController::class)->except(['index', 'show']);
        Route::get('admin/orders', [OrderController::class, 'index']);
        Route::apiResource('orders', OrderController::class)->except(['index', 'show', 'store']);
    });
});

Route::view('/login', 'app')->name('login');
Route::view('/{any?}', 'app')->where('any', '.*');
