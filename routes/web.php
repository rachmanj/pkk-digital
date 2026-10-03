<?php

use App\Http\Controllers\AgendaSuratController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\OrangController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('orang/daftar-anggota', [OrangController::class, 'daftarAnggota'])
        ->name('orang.daftar-anggota');
    Route::get('orang/{orang}/foto', [OrangController::class, 'foto'])
        ->name('orang.foto');
    Route::resource('orang', OrangController::class);

    Route::get('agenda-surat/{agenda_surat}/berkas', [AgendaSuratController::class, 'berkas'])
        ->name('agenda-surat.berkas');
    Route::post('agenda-surat/{agenda_surat}/disposisi', [DisposisiController::class, 'store'])
        ->name('agenda-surat.disposisi.store');
    Route::put('disposisi/{disposisi}', [DisposisiController::class, 'update'])
        ->name('disposisi.update');
    Route::resource('agenda-surat', AgendaSuratController::class);
});
