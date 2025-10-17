<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
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


// --- Rutas para Administradores ---
    Route::middleware(['role:Admin'])->prefix('admin')->name('admin.')->group(function () {
        // USUARIOS -----------------------------
        // --------------------------------------
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleIsActive'])->name('users.toggleActive');

        // SERVICIOS ----------------------------
        // --------------------------------------
        Route::resource('services', ServiceController::class);

        // PROMOCIONES --------------------------
        // --------------------------------------
        Route::resource('promotions', PromotionController::class);

        // USUARIOS -----------------------------
        // --------------------------------------
        // Route::resource('bookings', AdminBookingController::class)->names('bookings');
    });

    // --- Rutas para Empleados ---
    // Nota: Los admins también suelen tener acceso a las rutas de empleados.
    Route::middleware(['role:Admin,Empleado'])->prefix('employee')->name('employee.')->group(function () {
        // Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index'); // employee.schedule.index
        // Aquí podrías agregar más rutas específicas para empleados
    });

    // --- Rutas para Clientes ---
    Route::middleware(['role:Cliente'])->prefix('client')->name('client.')->group(function () {
        // Route::resource('bookings', ClientBookingController::class)->names('bookings'); // client.bookings.index, .create, ...
    });
