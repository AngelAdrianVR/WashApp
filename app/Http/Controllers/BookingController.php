<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class BookingController extends Controller
{
    public function index()
    {
        // Obtenemos el usuario autenticado
        $user = Auth::user();

        // Dependiendo del rol, filtramos las reservas.
        // Para el cliente, solo mostramos las suyas.
        // Usamos when() para aplicar el filtro solo si es un cliente.
        $bookings = Booking::with('services') // Eager load para obtener los servicios de cada reserva
            ->when($user->role === 'Cliente', function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->orderBy('scheduled_at', 'desc') // Ordenamos por fecha, las más próximas primero
            ->paginate(20); // Paginamos los resultados

        // Renderizamos la vista de Inertia correspondiente.
        // Asumimos que has seguido la estructura de carpetas recomendada.
        return Inertia::render('Client/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva reserva.
     */
    public function create()
    {
        // Obtenemos solo los servicios activos para mostrarlos al cliente
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $user = Auth::user();

        return Inertia::render('Client/Bookings/Create', [
            'services' => $services,
            'user' => [ // Pasamos solo la info necesaria del usuario
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ]
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validación de los datos del formulario
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:Efectivo,Tarjeta,Transferencia',
            'notes' => 'nullable|string',
        ]);
        
        // **NUEVO**: No usaremos el rol 'client', sino 'Cliente' como en la migración
        $validatedData['role'] = $validatedData['role'] ?? 'Cliente';

        // 2. Calcular la duración total y obtener los servicios de la BD
        $selectedServices = Service::whereIn('id', $validatedData['services'])->get();
        $totalDuration = $selectedServices->sum('duration_minutes');
        $requestedStartTime = Carbon::parse($validatedData['scheduled_at']);
        $requestedEndTime = $requestedStartTime->copy()->addMinutes($totalDuration);

        // 3. Lógica para encontrar un empleado disponible
        $employees = User::where('role', 'Empleado')->where('is_active', true)->get();
        $availableEmployeeId = null;

        foreach ($employees as $employee) {
            $isBusy = Booking::where('employee_id', $employee->id)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where(function ($query) use ($requestedStartTime, $requestedEndTime) {
                    $query->where(function ($q) use ($requestedStartTime, $requestedEndTime) {
                        $q->where('scheduled_at', '<', $requestedEndTime)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL (SELECT SUM(s.duration_minutes) FROM services s JOIN booking_service bs ON s.id = bs.service_id WHERE bs.booking_id = bookings.id) MINUTE) > ?', [$requestedStartTime]);
                    });
                })->exists();
            
            if (!$isBusy) {
                $availableEmployeeId = $employee->id;
                break;
            }
        }
        
        if (!$availableEmployeeId) {
            return back()->withErrors(['scheduled_at' => 'El horario seleccionado ya no está disponible. Por favor, elige otro.']);
        }

        // 4. Iniciar una transacción
        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'employee_id' => $availableEmployeeId,
                'scheduled_at' => $validatedData['scheduled_at'],
                'status' => 'pending',
                'total_price' => $validatedData['total_price'],
                // **NUEVO**: Agregamos el método de pago que no estaba
                'payment_method' => $validatedData['payment_method'],
                'address' => $validatedData['address'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'notes' => $validatedData['notes'],
            ]);

            // **MODIFICADO**: Preparar datos para la tabla pivote con el precio actual
            $servicesToAttach = [];
            foreach ($selectedServices as $service) {
                $servicesToAttach[$service->id] = ['price_at_booking' => $service->price];
            }
            $booking->services()->attach($servicesToAttach);

            $user = Auth::user();
            if ($user->name !== $validatedData['name'] || $user->phone_number !== $validatedData['phone_number']) {
                $user->name = $validatedData['name'];
                $user->phone_number = $validatedData['phone_number'];
                $user->save();
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al crear tu reserva. Inténtalo de nuevo.');
        }

        return redirect()->route('client.bookings.index')->with('success', '¡Tu cita ha sido agendada exitosamente!');
    }

    public function show(Booking $booking)
    {
        // 1. Autorización: Nos aseguramos de que el usuario solo pueda ver sus propias reservas.
        // Si el user_id de la reserva no coincide con el ID del usuario autenticado, se deniega el acceso.
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'No autorizado para ver esta página.');
        }

        // 2. Carga Eficiente de Datos (Eager Loading):
        // Cargamos las relaciones 'services' y 'employee' para evitar múltiples consultas a la base de datos
        // en la vista. Esto mejora significativamente el rendimiento.
        $booking->load('services', 'employee');

        // 3. Renderizar la Vista:
        // Pasamos el objeto de la reserva (con los datos ya cargados) al componente de Vue.
        return Inertia::render('Client/Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function edit(Booking $booking)
    {
        // 1. Autorización: Asegurarnos de que el cliente solo puede editar sus propias reservas.
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'No autorizado para realizar esta acción.');
        }

        // 2. Lógica de negocio: Solo se pueden editar citas pendientes o confirmadas.
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Esta reserva ya no puede ser editada.');
        }

        // 3. Cargar datos necesarios para el formulario
        $services = Service::where('is_active', true)->orderBy('name')->get();
        
        // Cargamos los servicios que ya tiene la reserva para pre-seleccionarlos
        $booking->load('services');

        return Inertia::render('Client/Bookings/Edit', [
            'booking' => $booking,
            'services' => $services,
            'user' => [ // Pasamos info del usuario por consistencia con el create
                'name' => Auth::user()->name,
                'phone_number' => Auth::user()->phone_number,
            ]
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        // 1. Autorización
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'No autorizado para realizar esta acción.');
        }

        // 2. Lógica de negocio: Verificar estado de la reserva
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Esta reserva ya no puede ser actualizada.');
        }

        // 3. Validación de datos (muy similar a store)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i',
            'total_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:Efectivo,Tarjeta,Transferencia',
            'notes' => 'nullable|string',
        ]);

        // 4. Calcular duración y verificar disponibilidad de empleado
        $selectedServices = Service::whereIn('id', $validatedData['services'])->get();
        $totalDuration = $selectedServices->sum('duration_minutes');
        $requestedStartTime = Carbon::parse($validatedData['scheduled_at']);
        $requestedEndTime = $requestedStartTime->copy()->addMinutes($totalDuration);

        $employees = User::where('role', 'Empleado')->where('is_active', true)->get();
        $availableEmployeeId = null;

        foreach ($employees as $employee) {
            // Excluimos la reserva actual de la comprobación de disponibilidad
            $isBusy = Booking::where('employee_id', $employee->id)
                ->where('id', '!=', $booking->id) // <-- La diferencia clave
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where(function ($query) use ($requestedStartTime, $requestedEndTime) {
                    $query->where(function ($q) use ($requestedStartTime, $requestedEndTime) {
                        $q->where('scheduled_at', '<', $requestedEndTime)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL (SELECT SUM(s.duration_minutes) FROM services s JOIN booking_service bs ON s.id = bs.service_id WHERE bs.booking_id = bookings.id) MINUTE) > ?', [$requestedStartTime]);
                    });
                })->exists();
            
            if (!$isBusy) {
                $availableEmployeeId = $employee->id;
                break;
            }
        }
        
        if (!$availableEmployeeId) {
            return back()->withErrors(['scheduled_at' => 'El nuevo horario seleccionado no está disponible. Por favor, elige otro.']);
        }

        // 5. Transacción para actualizar de forma segura
        DB::beginTransaction();
        try {
            $booking->update([
                'employee_id' => $availableEmployeeId,
                'scheduled_at' => $validatedData['scheduled_at'],
                'total_price' => $validatedData['total_price'],
                'payment_method' => $validatedData['payment_method'],
                'address' => $validatedData['address'],
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'notes' => $validatedData['notes'],
            ]);

            // Sincronizar servicios en la tabla pivote (más eficiente que detach/attach)
            $servicesToSync = [];
            foreach ($selectedServices as $service) {
                $servicesToSync[$service->id] = ['price_at_booking' => $service->price];
            }
            $booking->services()->sync($servicesToSync);
            
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            // Opcional: Loggear el error $e->getMessage() para depuración
            return back()->with('error', 'Ocurrió un error al actualizar tu reserva. Inténtalo de nuevo.');
        }

        return redirect()->route('client.bookings.index')->with('success', '¡Tu cita ha sido actualizada exitosamente!');
    }

    /**
     * Permite a un cliente actualizar las notas de una de sus reservas.
     */
    public function updateNotes(Request $request, Booking $booking)
    {
        // 1. Autorización: Asegurarnos de que el cliente solo puede editar sus propias reservas.
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'No autorizado para realizar esta acción.');
        }

        // 2. Validación: Validamos que las notas sean un texto (pueden ser nulas).
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        // 3. Actualización: Actualizamos el campo 'notes' de la reserva.
        $booking->update([
            'notes' => $request->input('notes'),
        ]);

        // 4. Redirección: Volvemos a la página anterior con un mensaje de éxito.
        return back()->with('success', 'Notas actualizadas correctamente.');
    }

    public function destroy(Booking $booking)
    {
        //
    }

    /**
     * Permite a un cliente cancelar una de sus reservas.
     */
    public function cancel(Request $request, Booking $booking)
    {
        // 1. Validación de la solicitud
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        // 2. Política de autorización: Asegurarnos que el cliente solo puede cancelar sus propias reservas.
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'No autorizado para realizar esta acción.');
        }

        // 3. Lógica de negocio: Solo se pueden cancelar citas pendientes o confirmadas.
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Esta reserva ya no puede ser cancelada.');
        }

        // 4. Actualizar el estado y guardar la razón
        $booking->status = 'cancelled';
        // Agregamos la razón de la cancelación para tener un registro.
        $booking->cancel_reason = $booking->notes . "\nCancelado por cliente: " . $request->reason;
        $booking->save();

        // 5. Redireccionar con un mensaje de éxito.
        return back()->with('success', 'Reserva cancelada exitosamente.');
    }

    /**
     * Devuelve los horarios disponibles para una fecha y duración dadas.
     * Esta es la lógica para calcular la disponibilidad.
     */
    public function getAvailableTimes(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'duration' => 'required|integer|min:1',
        ]);

        $date = Carbon::parse($request->date);
        $totalDuration = $request->duration; // Duración total de los servicios seleccionados

        // 1. Obtener todos los empleados activos
        $employees = User::where('role', 'Empleado')->where('is_active', true)->get();
        if ($employees->isEmpty()) {
            return response()->json([]); // Si no hay empleados, no hay horarios
        }

        // 2. Definir horario laboral (ej. 9am a 6pm) y día actual
        $workDayStart = $date->copy()->setTime(9, 0);
        $workDayEnd = $date->copy()->setTime(18, 0);
        $now = Carbon::now();

        // 3. Obtener todas las reservas para la fecha seleccionada que no estén canceladas/completadas
        $bookingsOnDate = Booking::with('services')->whereDate('scheduled_at', $date)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->get();

        $availableSlots = [];
        // Empezamos a buscar horarios desde la hora actual si es para hoy
        $slot = $date->isToday() && $now->gt($workDayStart) ? $now->roundMinute(30) : $workDayStart;

        // 4. Iterar a través del día en intervalos de 30 minutos
        while ($slot->copy()->addMinutes($totalDuration)->lessThanOrEqualTo($workDayEnd)) {
            $potentialSlotStart = $slot->copy();
            $potentialSlotEnd = $slot->copy()->addMinutes($totalDuration);
            $isSlotAvailable = false;

            // 5. Verificar si al menos un empleado está libre en este horario
            foreach ($employees as $employee) {
                $isEmployeeBusy = false;
                $employeeBookings = $bookingsOnDate->where('employee_id', $employee->id);

                foreach ($employeeBookings as $booking) {
                    $bookingStart = Carbon::parse($booking->scheduled_at);
                    // Sumamos la duración de los servicios de esa reserva para saber cuándo termina
                    $serviceDuration = $booking->services->sum('duration_minutes');
                    $bookingEnd = $bookingStart->copy()->addMinutes($serviceDuration);

                    // Comprobación de solapamiento (si el nuevo servicio choca con uno existente)
                    if ($potentialSlotStart->lt($bookingEnd) && $potentialSlotEnd->gt($bookingStart)) {
                        $isEmployeeBusy = true;
                        break; // El empleado está ocupado, pasamos al siguiente
                    }
                }

                if (!$isEmployeeBusy) {
                    $isSlotAvailable = true; // ¡Encontramos un empleado libre!
                    break;
                }
            }

            if ($isSlotAvailable) {
                $availableSlots[] = $potentialSlotStart->format('H:i');
            }

            // Avanzar al siguiente slot
            $slot->addMinutes(30);
        }

        return response()->json($availableSlots);
    }
}
