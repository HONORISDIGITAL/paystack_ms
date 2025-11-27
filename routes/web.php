<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/ping', function () {
    return response()->json([
        'message' => 'paystack ping successful',
        'timestamp' => now()->format('Y-m-d H:i:s.v')
    ]);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
