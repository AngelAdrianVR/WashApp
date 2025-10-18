<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRewardController;
use App\Models\Booking;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
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

    // Lógica para despachar el dashboard segun el rol
    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ])->group(function () {
        Route::get('/dashboard', function () {
            $user = Auth::user();

            if ($user->role === 'Admin') {
                // Logic and data for the Admin dashboard
                return Inertia::render('Admin/Dashboard', [
                    // Pass admin-specific data here
                ]);
            } 
            
            if ($user->role === 'Empleado') {
                // Logic and data for the Employee dashboard
                return Inertia::render('Employee/Dashboard', [
                    // Pass employee-specific data here
                ]);
            } 
            
            // --- Client Dashboard Logic ---

            // Query to get the next 5 upcoming (pending) bookings.
            $upcomingBookings = Booking::where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('scheduled_at', '>=', now())
                ->with('services') // Eager load the services
                ->orderBy('scheduled_at', 'asc')
                ->limit(5)
                ->get();
            
            // Query to get active bookings for the stepper.
            $activeBookings = Booking::where('user_id', $user->id)
                ->whereIn('status', ['confirmed', 'on_way', 'in_progress'])
                ->with('services') // Eager load the services
                ->orderBy('scheduled_at', 'asc')
                ->get();

            // Calculate statistics for the client.
            $stats = [
                'completedBookingsCount' => Booking::where('user_id', $user->id)->where('status', 'completed')->count(),
                'totalSpent' => Booking::where('user_id', $user->id)->where('status', 'completed')->sum('total_price'),
                // This assumes you have a 'rewards' relationship defined in your User model.
                'availableRewards' => $user->rewards()->where('is_claimed', false)->count(),
            ];

            return Inertia::render('Client/Dashboard', [
                'user' => $user->only('id', 'name', 'email'),
                'upcomingBookings' => $upcomingBookings,
                'activeBookings' => $activeBookings, // Pass active bookings to the component
                'stats' => $stats,
            ]);

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


    Route::middleware(['auth', 'role:Cliente'])->prefix('client')->name('client.')->group(function () {
        
        // MIS RESERVACIONES --------------------
        // --------------------------------------

        // Ruta para obtener horarios disponibles (vía API). 
        // Se declara antes del resource para que 'available-times' no sea interpretado como un ID de reserva.
        Route::post('bookings/available-times', [BookingController::class, 'getAvailableTimes'])->name('bookings.availableTimes');

        // Rutas resource para las operaciones CRUD estándar de las reservas.
        // Esto genera automáticamente las rutas para index, create, store, show, edit y update.
        Route::resource('bookings', BookingController::class)->only([
            'index', 'create', 'store', 'show', 'edit', 'update'
        ]);

        // Rutas personalizadas para acciones específicas sobre una reserva.
        // Se declaran después del resource.
        Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::patch('bookings/{booking}/update-notes', [BookingController::class, 'updateNotes'])->name('bookings.updateNotes');


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
