<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AtmController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\LoansController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\MasterController;
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


Route::middleware([AdminMiddleware::class])->group(function () {
//Users
Route::get('/users', [LoginController::class, 'userindex'])->name('users');
Route::put('/users/update', [LoginController::class, 'update'])->name('users.update');
Route::delete('/users/destroy', [LoginController::class, 'destroy'])->name('users.destroy');

//Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/store', [DashboardController::class, 'store'])->name('dashboard.store');

//Billing
Route::get('/Billing', [BillingController::class, 'index'])->name('billing');
Route::get('/billing/export', [BillingController::class, 'export'])->name('billing.export');
Route::put('/billing/{member}', [BillingController::class, 'update'])->name('billing.update');
Route::delete('/billing/{member}', [BillingController::class, 'destroy'])->name('billing.destroy');

//Member
Route::get('/Member', [MemberController::class, 'index'])->name('member');
Route::put('/members/{id}', [MemberController::class, 'update'])->name('member.update');
Route::delete('/members/{id}', [MemberController::class, 'destroy'])->name('member.delete');

//Loans
Route::get('/Loans', [LoansController::class, 'index'])->name('loans');
Route::get('/Loans/Members', [LoansController::class, 'list'])->name('list');
Route::post('/loans', [LoansController::class, 'store'])->name('loans.store');
Route::put('/loans/{loan}', [LoansController::class, 'update'])->name('loans.update');
Route::delete('/loans/{loan}', [LoansController::class, 'destroy'])->name('loans.destroy');
//Document
Route::post('/upload', [DocumentUploadController::class, 'store'])->name('document.upload');
Route::get('/Documents', [DocumentUploadController::class, 'index'])->name('documents');

//Atm
Route::get('/Atm', [AtmController::class, 'index'])->name('atm');

//Branch
Route::get('/Branch', [BranchController::class, 'index'])->name('branch');
Route::get('/branches/{id}', [BranchController::class, 'view'])->name('branch.view');
Route::get('/branches/{id}/edit', [BranchController::class, 'edit'])->name('branch.edit');
Route::put('/branches/{id}', [BranchController::class, 'update'])->name('branch.update');
Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('branch.destroy');

//Remittance
Route::get('/Remittance', [RemittanceController::class, 'index'])->name('remittance');

//Master List
Route::get('/Master', [MasterController::class, 'index'])->name('master');
Route::put('/members/{id}', [MasterController::class, 'update']);
Route::delete('/members/{id}', [MasterController::class, 'destroy']);

});
