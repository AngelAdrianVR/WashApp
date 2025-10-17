<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRewardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
        Route::patch('services/{service}/toggle-active', [ServiceController::class, 'toggleActive'])->name('services.toggleActive');
        Route::post('services/{service}/add-images', [ServiceController::class, 'addImages'])->name('services.addImages'); // Ruta para agregar nuevas imágenes a un servicio


        // PROMOCIONES --------------------------
        // --------------------------------------
        Route::resource('promotions', PromotionController::class);


        // RESERVACIONES ------------------------
        // --------------------------------------
        Route::resource('bookings', BookingController::class);
        // Ruta para que el admin pueda cambiar el estado de una reserva.
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.updateStatus');
        // Ruta para asignar un empleado a una reserva.
        Route::patch('bookings/{booking}/assign', [BookingController::class, 'assignEmployee'])->name('bookings.assignEmployee');


        // RECOMPENSAS DE USUARIO ---------------
        // --------------------------------------
        // Gestión de las recompensas otorgadas a los usuarios.
        Route::resource('rewards', UserRewardController::class);
    });

    // --- Rutas para Empleados ---
    // Nota: Los admins también suelen tener acceso a las rutas de empleados.
    Route::middleware(['role:Admin,Empleado'])->prefix('employee')->name('employee.')->group(function () {
        // AGENDA / RESERVACIONES DEL EMPLEADO --
        // (Añadido)
        // --------------------------------------
        // El empleado ve su lista de trabajos/reservas asignadas.
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        // El empleado ve el detalle de un trabajo específico.
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        // El empleado actualiza el estado de su trabajo (ej. 'en_camino', 'completado').
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.updateStatus');
    });

    // --- Rutas para Clientes ---
    Route::middleware(['role:Cliente'])->prefix('client')->name('client.')->group(function () {
        // MIS RESERVACIONES --------------------
        // --------------------------------------
        // El cliente solo puede ver su historial, ver una reserva, y cancelarla.
        // La creación se hace desde la ruta pública.
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

        // MIS RECOMPENSAS ----------------------
        // --------------------------------------
        // El cliente puede ver las recompensas/cupones que ha ganado.
        Route::get('rewards', [UserRewardController::class, 'index'])->name('rewards.index');
    });


    // Ruta general para eliminar cualquier archivo de la librería de medios
    Route::delete('media/{media}', function (Media $media) {
        // Aquí podrías agregar políticas de seguridad para asegurar que el usuario
        // tiene permiso para eliminar este archivo específico.
        $media->delete();
        return back();
    })->middleware('auth')->name('media.destroy');
