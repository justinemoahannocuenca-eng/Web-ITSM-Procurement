<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\AccountsController;
use Modules\Finance\Http\Controllers\ExpensesController;
use Modules\Finance\Http\Controllers\FinanceController;
use Modules\Finance\Http\Controllers\InvoiceController;

Route::get('/', fn () => redirect()->route('finance.dashboard'));

Route::middleware('finance.access')->name('finance.')->group(function (): void {
    Route::get('/dashboard', [FinanceController::class, 'index'])->name('dashboard');
    Route::get('/overview', [FinanceController::class, 'dashboard'])->name('overview');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::put('/invoice/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::put('/invoice/{invoice}/reject', [InvoiceController::class, 'reject'])->name('invoices.reject');
    Route::get('/expenses', [ExpensesController::class, 'index'])->name('expenses');
    Route::get('/sales', [FinanceController::class, 'sales'])->name('sales');
    Route::get('/cash-flow', [FinanceController::class, 'cashflow'])->name('cashflow');
    Route::get('/accounts', [AccountsController::class, 'index'])->name('accounts');
    Route::post('/accounts', [AccountsController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{account}', [AccountsController::class, 'update'])->name('accounts.update');
    Route::delete('/accounts/{account}', [AccountsController::class, 'destroy'])->name('accounts.destroy');
});
