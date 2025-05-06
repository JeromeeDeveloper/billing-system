<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\Auth\LoginController;


Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');



Route::get('/dashboard', function () {
    return view('components.dashboard');
})->middleware('auth')->name('dashboard');

Route::get('/Billing', [BillingController::class, 'index'])->name('billing');
