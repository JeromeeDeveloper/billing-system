<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtmController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RemittanceController;
use App\Http\Controllers\DocumentUploadController;

//Login
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

//Billing
Route::get('/Billing', [BillingController::class, 'index'])->name('billing');

//Member
Route::get('/Member', [MemberController::class, 'index'])->name('member');

//Document
Route::post('/upload', [DocumentUploadController::class, 'store'])->name('document.upload');

//Atm
Route::get('/Atm', [AtmController::class, 'index'])->name('atm');

//Branch
Route::get('/Branch', [BranchController::class, 'index'])->name('branch');

//Remittance
Route::get('/Remittance', [RemittanceController::class, 'index'])->name('remittance');
