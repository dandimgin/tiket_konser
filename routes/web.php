<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public / Customer pages
Route::get('/', function () {
    return Inertia::render('Customer/Events/Index');
})->name('home');

Route::get('/events/{id}', function ($id) {
    return Inertia::render('Customer/Events/Show', [
        'eventId' => (int) $id,
    ]);
})->name('events.show');

Route::get('/checkout/{id}', function ($id) {
    return Inertia::render('Customer/Checkout', [
        'eventId' => (int) $id,
    ]);
})->name('checkout');

Route::get('/payment/{orderId}', function ($orderId) {
    return Inertia::render('Customer/Payment', [
        'orderId' => (int) $orderId,
    ]);
})->name('payment');

Route::get('/orders', function () {
    return Inertia::render('Customer/Orders/Index');
})->name('orders.index');

Route::get('/tickets', function () {
    return Inertia::render('Customer/Tickets/Index');
})->name('tickets.index');

// Auth pages
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

// Admin pages
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    Route::get('/events', function () {
        return Inertia::render('Admin/Events/Index');
    })->name('admin.events');

    Route::get('/artists', function () {
        return Inertia::render('Admin/Artists/Index');
    })->name('admin.artists');

    Route::get('/ticket-categories', function () {
        return Inertia::render('Admin/TicketCategories/Index');
    })->name('admin.ticket-categories');

    Route::get('/orders', function () {
        return Inertia::render('Admin/Orders/Index');
    })->name('admin.orders');

    Route::get('/payments', function () {
        return Inertia::render('Admin/Payments/Index');
    })->name('admin.payments');
});
