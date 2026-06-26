<?php

use App\Livewire\Auth\LoginForm;
use App\Livewire\CashierScreen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('kasir') : redirect()->route('login');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', LoginForm::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// Layar kasir (POS)
Route::middleware('auth')->group(function () {
    Route::get('/kasir', CashierScreen::class)->name('kasir');
});
