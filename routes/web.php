<?php

use App\Http\Controllers\LandingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ruta principal que muestra la página de inicio ---------------------------------------------
// --------------------------------------------------------------------------------------------
Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::get('/servicios', [LandingController::class, 'services'])->name('landing.servicios');
Route::get('/agendar', [LandingController::class, 'appointment'])->name('landing.agendar');
Route::get('/promociones', [LandingController::class, 'promotions'])->name('landing.promociones');



Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
