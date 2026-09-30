<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventArtistController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TicketCategoryController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum', 'role:Admin'])->get('/admin-test', function () {
    return response()->json([
        'message' => 'Admin access berhasil.',
    ]);
});

Route::get('/artists', [ArtistController::class, 'index']);
Route::get('/artists/{artist}', [ArtistController::class, 'show']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);
Route::get('/events/{event}/artists', [EventArtistController::class, 'index']);
Route::get('/events/{event}/ticket-categories', [TicketCategoryController::class, 'index']);
Route::get('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::get('/orders/{order}/payment', [PaymentController::class, 'show']);
    Route::post('/orders/{order}/payment/proof', [PaymentController::class, 'submitProof']);

    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::post('/artists', [ArtistController::class, 'store']);
    Route::put('/artists/{artist}', [ArtistController::class, 'update']);
    Route::patch('/artists/{artist}', [ArtistController::class, 'update']);
    Route::delete('/artists/{artist}', [ArtistController::class, 'destroy']);

    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::patch('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);

    Route::post('/events/{event}/artists', [EventArtistController::class, 'store']);
    Route::delete('/events/{event}/artists/{artist}', [EventArtistController::class, 'destroy']);

    Route::post('/events/{event}/ticket-categories', [TicketCategoryController::class, 'store']);
    Route::put('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update']);
    Route::patch('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'update']);
    Route::delete('/events/{event}/ticket-categories/{ticketCategory}', [TicketCategoryController::class, 'destroy']);

    Route::get('/admin/payments', [PaymentController::class, 'indexAdmin']);
    Route::post('/admin/orders/{order}/payment/verify', [PaymentController::class, 'verify']);
    Route::post('/admin/orders/{order}/payment/reject', [PaymentController::class, 'reject']);

    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
});