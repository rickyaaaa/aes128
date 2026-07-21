<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileProcessController;
use App\Http\Controllers\Owner\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->isOwner() ? 'owner.dashboard' : 'staff.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/about', 'about', [
        'pageTitle' => 'Tentang Aplikasi',
        'pageDescription' => 'Ringkasan fungsi, alur kerja, dan keamanan aplikasi enkripsi file AES-128.',
    ])->name('about');

    Route::get('/encrypt', [FileProcessController::class, 'createEncryption'])->name('files.encrypt');
    Route::post('/encrypt', [FileProcessController::class, 'storeEncryption'])->name('files.encrypt.store');
    Route::get('/decrypt', [FileProcessController::class, 'createDecryption'])->name('files.decrypt.create');
    Route::post('/decrypt', [FileProcessController::class, 'storeDecryption'])->name('files.decrypt.store');
    Route::get('/file-logs/{fileLog}', [FileProcessController::class, 'show'])->name('files.show');
    Route::post('/file-logs/{fileLog}/decrypt', [FileProcessController::class, 'decrypt'])->name('files.decrypt');
    Route::post('/file-logs/{fileLog}/password', [FileProcessController::class, 'updatePassword'])->name('files.password.update');
    Route::get('/file-logs/{fileLog}/download', [FileProcessController::class, 'download'])->name('files.download');
    Route::get('/history', [FileProcessController::class, 'history'])->name('history');
    Route::delete('/file-logs/{fileLog}', [FileProcessController::class, 'destroy'])->name('files.destroy');

    Route::get('/staff/dashboard', [DashboardController::class, 'staff'])
        ->middleware('role:staff')
        ->name('staff.dashboard');

    Route::middleware('role:owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'owner'])->name('dashboard');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    });
});

Route::view('/unauthorized', 'unauthorized', [
    'role' => 'staff',
    'pageTitle' => 'Akses Ditolak',
    'pageDescription' => 'Halaman simulasi ketika Staff mencoba membuka area Owner.',
])->name('unauthorized');
