<?php

use App\Http\Controllers\App\CustomerSearchController;
use App\Http\Controllers\App\CustomerStoreController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\PosController;
use App\Http\Controllers\App\ProductSearchController;
use App\Http\Controllers\App\SaleController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->hasRole('Almacén')
            ? redirect('/admin')
            : redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)
        ->middleware('role:Admin|Vendedor')
        ->name('dashboard');

    Route::middleware('role:Admin|Vendedor')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/pos', PosController::class)->name('sales.pos');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::post('sales/{sale}/payments', [SaleController::class, 'payment'])->name('sales.payments.store');

        Route::prefix('api')->group(function () {
            Route::get('products/search', ProductSearchController::class);
            Route::get('customers/search', CustomerSearchController::class);
            Route::post('customers', CustomerStoreController::class);
        });
    });
});
