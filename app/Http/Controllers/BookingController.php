<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingNotifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'Admin') {
            return $this->adminIndex($request);
        }

        if ($user->role === 'Empleado') {
            return $this->employeeIndex($request, $user);
        }

        $bookings = Booking::with('services')
            ->where('user_id', $user->id)
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Client/Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Listado de todos los servicios registrados/agendados (panel administrador).
     */
    private function adminIndex(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'on_way', 'in_progress', 'completed', 'cancelled'])],
            'employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Booking::query()
            ->with(['services', 'client', 'employee'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->where('scheduled_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->where('scheduled_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('guest_name', 'like', "%{$search}%")
                        ->orWhere('guest_phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            });

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $bookings = (clone $query)
            ->orderByDesc('scheduled_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Booking $booking) => $this->bookingListItem($booking));

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => [
                'status' => $validated['status'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'search' => $validated['search'] ?? null,
            ],
            'summary' => [
                'total' => Booking::count(),
                'open' => Booking::open()->count(),
                'today' => Booking::open()->whereBetween('scheduled_at', [$todayStart, $todayEnd])->count(),
                'unassigned' => Booking::open()->whereNull('employee_id')->count(),
                'completedMonth' => Booking::completedBetween($monthStart, $monthEnd)->count(),
                'revenueMonth' => round((float) Booking::completedBetween($monthStart, $monthEnd)->sum('total_price'), 2),
            ],
            'employees' => User::query()
                ->where('role', 'Empleado')
                ->orderBy('name')
                ->get(['id', 'name', 'is_active'])
                ->map(fn (User $employee) => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'is_active' => (bool) $employee->is_active,
                ]),
        ]);
    }

    /**
     * Servicios asignados al empleado autenticado (panel de empleados).
     */
    private function employeeIndex(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'on_way', 'in_progress', 'completed', 'cancelled'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Booking::query()
            ->with(['services', 'client'])
            ->where('employee_id', $user->id)
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->where('scheduled_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->where('scheduled_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('guest_name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone_number', 'like', "%{$search}%");
                        });
                });
            });

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $bookings = (clone $query)
            ->orderByDesc('scheduled_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Booking $booking) => $this->bookingListItem($booking));

        return Inertia::render('Employee/Bookings/Index', [
            'bookings' => $bookings,
            'filters' => [
                'status' => $validated['status'] ?? null,
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
                'search' => $validated['search'] ?? null,
            ],
            'summary' => [
                'assigned' => (clone $query)->count(),
                'open' => Booking::where('employee_id', $user->id)->open()->count(),
                'today' => Booking::where('employee_id', $user->id)->open()->whereBetween('scheduled_at', [$todayStart, $todayEnd])->count(),
                'completed' => Booking::where('employee_id', $user->id)->where('status', 'completed')->count(),
                'earnings' => round((float) Booking::where('employee_id', $user->id)->where('status', 'completed')->sum('total_price'), 2),
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva reserva.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'Admin') {
            return Inertia::render('Admin/Bookings/Create', [
                'services' => Service::query()
                    ->active()
                    ->orderBy('is_addon')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'price', 'duration_minutes', 'is_addon', 'type']),
                'clients' => User::query()
                    ->where('role', 'Cliente')
                    ->orderBy('name')
                    ->get(['id', 'name', 'phone_number', 'email']),
                'employees' => User::query()
                    ->where('role', 'Empleado')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'defaults' => [
                    'state' => 'Jalisco',
                    'employee_id' => $request->integer('employee_id') ?: null,
                ],
            ]);
        }

        // Obtenemos solo los servicios activos para mostrarlos al cliente
        $services = Service::where('is_active', true)->orderBy('name')->get();

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
        if (Auth::user()->role === 'Admin') {
            return $this->storeForAdmin($request);
        }

        // 1. Validación de los datos del formulario
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'street' => 'required|string|max:255',
            'street_number' => 'nullable|string|max:30',
            'neighborhood' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'state' => 'required|string|max:80',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'services' => 'required|array|min:1',
            'services.*' => 'exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i',
            'payment_method' => 'nullable|string|in:Efectivo,Tarjeta,Transferencia',
            'notes' => 'nullable|string|max:1000',
        ]);

        // 2. Calcular la duración total y obtener los servicios de la BD
        $selectedServices = Service::whereIn('id', $validatedData['services'])->get();
        $totalDuration = (int) $selectedServices->sum('duration_minutes');
        $requestedStartTime = Carbon::parse($validatedData['scheduled_at']);

        // 3. Lógica para encontrar un empleado disponible
        $availableEmployeeId = $this->findAvailableEmployee($requestedStartTime, $totalDuration);

        if (! $availableEmployeeId) {
            return back()
                ->withErrors(['scheduled_at' => 'El horario seleccionado ya no está disponible. Por favor, elige otro.'])
                ->withInput();
        }

        $address = trim(implode(', ', array_filter([
            trim(($validatedData['street'] ?? '') . ' ' . ($validatedData['street_number'] ?? '')),
            $validatedData['neighborhood'] ?? null,
            ! empty($validatedData['postal_code']) ? 'CP ' . $validatedData['postal_code'] : null,
            $validatedData['state'] ?? null,
        ])));

        // 4. Iniciar una transacción
        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'user_id' => Auth::id(),
                'employee_id' => $availableEmployeeId,
                'scheduled_at' => $requestedStartTime,
                'status' => 'pending',
                'total_price' => $selectedServices->sum(fn (Service $service) => (float) $service->price),
                'payment_method' => $validatedData['payment_method'] ?? 'Efectivo',
                'address' => $address,
                'street' => $validatedData['street'],
                'street_number' => $validatedData['street_number'] ?? null,
                'neighborhood' => $validatedData['neighborhood'],
                'postal_code' => $validatedData['postal_code'] ?? null,
                'state' => $validatedData['state'] ?? 'Jalisco',
                'latitude' => $validatedData['latitude'] ?? null,
                'longitude' => $validatedData['longitude'] ?? null,
                'notes' => $validatedData['notes'] ?? null,
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

        BookingNotifier::bookingCreated($booking, Auth::id());

        return redirect()->route('client.bookings.index')->with('success', '¡Tu cita ha sido agendada exitosamente!');
    }

    /**
     * Alta de una cita desde el panel administrador (cliente registrado o público general).
     */
    private function storeForAdmin(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'Cliente')],
            'guest_name' => ['nullable', 'string', 'max:255', 'required_without:user_id'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'Empleado')->where('is_active', true),
            ],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['integer', 'exists:services,id'],
            'scheduled_at' => ['required', 'date'],
            'street' => ['required', 'string', 'max:255'],
            'street_number' => ['nullable', 'string', 'max:30'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'state' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'payment_method' => ['nullable', Rule::in(['Efectivo', 'Tarjeta', 'Transferencia'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $services = Service::whereIn('id', $validated['services'])->get();
        $duration = (int) $services->sum('duration_minutes');
        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        if ($this->employeeHasConflict((int) $validated['employee_id'], $scheduledAt, $duration)) {
            return back()
                ->withErrors(['employee_id' => 'El empleado seleccionado ya tiene una cita en ese horario.'])
                ->withInput();
        }

        $booking = DB::transaction(function () use ($validated, $services, $scheduledAt) {
            $address = trim(implode(', ', array_filter([
                trim(($validated['street'] ?? '') . ' ' . ($validated['street_number'] ?? '')),
                $validated['neighborhood'] ?? null,
                ! empty($validated['postal_code']) ? 'CP ' . $validated['postal_code'] : null,
                $validated['state'] ?? null,
            ])));

            $booking = Booking::create([
                'user_id' => $validated['user_id'] ?? null,
                'employee_id' => $validated['employee_id'],
                'guest_name' => empty($validated['user_id']) ? ($validated['guest_name'] ?? null) : null,
                'guest_phone' => empty($validated['user_id']) ? ($validated['guest_phone'] ?? null) : null,
                'guest_email' => empty($validated['user_id']) ? ($validated['guest_email'] ?? null) : null,
                'address' => $address,
                'street' => $validated['street'],
                'street_number' => $validated['street_number'] ?? null,
                'neighborhood' => $validated['neighborhood'],
                'postal_code' => $validated['postal_code'] ?? null,
                'state' => $validated['state'] ?? 'Jalisco',
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'scheduled_at' => $scheduledAt,
                'status' => 'confirmed',
                'payment_method' => $validated['payment_method'] ?? null,
                'total_price' => $services->sum(fn (Service $service) => (float) $service->price),
                'notes' => $validated['notes'] ?? null,
            ]);

            $servicesToAttach = $services->mapWithKeys(fn (Service $service) => [
                $service->id => ['price_at_booking' => $service->price],
            ])->all();

            $booking->services()->attach($servicesToAttach);

            return $booking;
        });

        BookingNotifier::bookingCreated($booking, Auth::id());

        return redirect()->route('admin.bookings.index')->with('success', 'Servicio agendado correctamente.');
    }

    /**
     * Representación de una cita para las tablas del panel.
     *
     * @return array<string, mixed>
     */
    private function bookingListItem(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'client_name' => $booking->clientName(),
            'client_phone' => $booking->client?->phone_number ?? $booking->guest_phone,
            'client_email' => $booking->client?->email ?? $booking->guest_email,
            'address' => $booking->fullAddress(),
            'street' => $booking->street,
            'street_number' => $booking->street_number,
            'neighborhood' => $booking->neighborhood,
            'postal_code' => $booking->postal_code,
            'state' => $booking->state,
            'latitude' => $booking->latitude !== null ? (float) $booking->latitude : null,
            'longitude' => $booking->longitude !== null ? (float) $booking->longitude : null,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'ends_at' => $booking->endsAt()?->toIso8601String(),
            'is_today' => $booking->scheduled_at?->isToday() ?? false,
            'is_overdue' => $booking->scheduled_at?->lt(now()) ?? false,
            'status' => $booking->status,
            'payment_method' => $booking->payment_method,
            'total_price' => round((float) $booking->total_price, 2),
            'notes' => $booking->notes,
            'employee_id' => $booking->employee_id,
            'employee_name' => $booking->employee?->name,
            'employee_avatar' => $booking->employee?->avatar_url ?: $booking->employee?->profile_photo_url,
            'duration_minutes' => $booking->durationMinutes(),
            'services' => $booking->services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'duration_minutes' => (int) $service->duration_minutes,
                'price' => round((float) ($service->pivot->price_at_booking ?? $service->price), 2),
            ])->values()->all(),
        ];
    }

    public function show(Booking $booking)
    {
        $user = Auth::user();

        if ($user->role === 'Cliente' && $booking->user_id !== $user->id) {
            abort(403, 'No autorizado para ver esta página.');
        }

        if ($user->role === 'Empleado' && $booking->employee_id !== $user->id) {
            abort(403, 'No autorizado para ver esta página.');
        }

        $booking->load('services', 'employee', 'client');

        if ($user->role === 'Admin') {
            return Inertia::render('Admin/Bookings/Show', [
                'booking' => $this->bookingListItem($booking),
                'employees' => User::query()
                    ->where('role', 'Empleado')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ]);
        }

        if ($user->role === 'Empleado') {
            return Inertia::render('Employee/Bookings/Show', [
                'booking' => $this->bookingListItem($booking),
            ]);
        }

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

    /**
     * Asigna (o reasigna) un empleado a una reserva.
     */
    public function assignEmployee(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'Empleado')->where('is_active', true),
            ],
        ]);

        if (in_array($booking->status, ['completed', 'cancelled'], true)) {
            return back()->with('error', 'No puedes reasignar una reserva finalizada o cancelada.');
        }

        $booking->load('services');

        if ($this->employeeHasConflict(
            (int) $validated['employee_id'],
            $booking->scheduled_at,
            $booking->durationMinutes(),
            $booking->id
        )) {
            return back()->withErrors(['employee_id' => 'El empleado ya tiene otra cita en ese horario.']);
        }

        $booking->update(['employee_id' => $validated['employee_id']]);
        BookingNotifier::employeeAssigned($booking->refresh()->load('client'), Auth::id());

        return back()->with('success', 'Empleado asignado correctamente.');
    }

    /**
     * Actualiza el estado de una reserva y registra los tiempos reales.
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'on_way', 'in_progress', 'completed', 'cancelled'])],
        ]);

        $user = Auth::user();
        if ($user->role === 'Empleado' && $booking->employee_id !== $user->id) {
            abort(403, 'No autorizado para actualizar esta reserva.');
        }

        $status = $validated['status'];
        $attributes = ['status' => $status];

        if (in_array($status, ['on_way', 'in_progress'], true) && ! $booking->started_at) {
            $attributes['started_at'] = now();
        }

        if ($status === 'completed') {
            $attributes['started_at'] = $booking->started_at ?? now();
            $attributes['completed_at'] = $booking->completed_at ?? now();
        } elseif ($booking->completed_at) {
            // Si la cita vuelve a un estado abierto, deja de contar como ingreso.
            $attributes['completed_at'] = null;
        }

        $booking->update($attributes);
        BookingNotifier::statusChanged($booking->refresh(), $status, Auth::id());

        return back()->with('success', 'Estado de la reserva actualizado.');
    }

    /**
     * Comprueba si un empleado ya tiene una cita que se solape con el horario indicado.
     */
    private function employeeHasConflict(int $employeeId, Carbon $start, int $durationMinutes, ?int $ignoreBookingId = null): bool
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        $conflicts = Booking::with('services')
            ->where('employee_id', $employeeId)
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('scheduled_at', '<', $end)
            ->where('scheduled_at', '>=', $start->copy()->subDay())
            ->get();

        return $conflicts->contains(function (Booking $booking) use ($start, $end) {
            return $booking->scheduled_at->lt($end) && $booking->endsAt()->gt($start);
        });
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
        BookingNotifier::statusChanged($booking->refresh(), 'cancelled', Auth::id());

        // 5. Redireccionar con un mensaje de éxito.
        return back()->with('success', 'Reserva cancelada exitosamente.');
    }

    /**
     * Devuelve los horarios disponibles (intervalos de 1 hora) para una fecha y duración.
     */
    public function getAvailableTimes(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'duration' => 'required|integer|min:1',
        ]);

        return response()->json($this->hourlyAvailability(
            Carbon::parse($request->date),
            (int) $request->duration
        ));
    }

    /**
     * Disponibilidad pública usada por la página de agendado de la landing.
     */
    public function publicAvailableTimes(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'duration' => 'required|integer|min:1',
        ]);

        return response()->json($this->hourlyAvailability(
            Carbon::parse($request->date),
            (int) $request->duration
        ));
    }

    /**
     * Registra una reserva desde la página pública (cliente logueado o invitado).
     */
    public function storePublic(Request $request)
    {
        $user = Auth::user();
        $isGuest = ! $user;

        $validated = $request->validate([
            'name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['integer', 'exists:services,id'],
            'vehicle_category_id' => ['nullable', 'exists:vehicle_categories,id'],
            'vehicle_brand' => ['nullable', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
            'street' => ['required', 'string', 'max:255'],
            'street_number' => ['required', 'string', 'max:30'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'digits:5'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', Rule::in(['Efectivo', 'Tarjeta', 'Transferencia'])],
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_at']);

        if ($scheduledAt->lt(now())) {
            return back()
                ->withErrors(['scheduled_at' => 'Selecciona un horario que aún no haya pasado.'])
                ->withInput();
        }

        $selectedServices = Service::whereIn('id', $validated['services'])->with('vehicleCategories')->get();
        $categoryId = $validated['vehicle_category_id'] ?? null;
        $duration = (int) $selectedServices->sum(fn (Service $service) => $service->durationFor($categoryId));

        $employeeId = $this->findAvailableEmployee($scheduledAt, $duration);

        if (! $employeeId) {
            return back()
                ->withErrors(['scheduled_at' => 'Ese horario ya no está disponible. Elige otro.'])
                ->withInput();
        }

        $address = trim(implode(', ', array_filter([
            trim($validated['street'] . ' ' . $validated['street_number']),
            $validated['neighborhood'],
            ! empty($validated['postal_code']) ? 'CP ' . $validated['postal_code'] : null,
            $validated['city'] ?? null,
            $validated['state'] ?? null,
        ])));

        $booking = DB::transaction(function () use ($validated, $user, $scheduledAt, $employeeId, $selectedServices, $categoryId, $address) {
            $booking = Booking::create([
                'user_id' => $user?->id,
                'employee_id' => $employeeId,
                'guest_name' => $user ? null : ($validated['name'] ?? null),
                'guest_phone' => $user ? null : ($validated['phone'] ?? null),
                'guest_email' => $user ? null : ($validated['email'] ?? null),
                'address' => $address,
                'street' => $validated['street'],
                'street_number' => $validated['street_number'],
                'neighborhood' => $validated['neighborhood'],
                'postal_code' => $validated['postal_code'],
                'state' => $validated['state'] ?? 'Jalisco',
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'scheduled_at' => $scheduledAt,
                'status' => 'confirmed',
                'payment_method' => $validated['payment_method'] ?? 'Tarjeta',
                'total_price' => $selectedServices->sum(fn (Service $service) => $service->priceFor($categoryId)),
                'notes' => $validated['notes'] ?? null,
                'vehicle_brand' => $validated['vehicle_brand'] ?? null,
                'vehicle_model' => $validated['vehicle_model'] ?? null,
                'vehicle_category_id' => $categoryId,
            ]);

            $servicesToAttach = $selectedServices->mapWithKeys(fn (Service $service) => [
                $service->id => ['price_at_booking' => $service->priceFor($categoryId)],
            ])->all();

            $booking->services()->attach($servicesToAttach);

            return $booking;
        });

        BookingNotifier::bookingCreated($booking, $user?->id);

        $categoryName = $categoryId
            ? $selectedServices->first()?->vehicleCategories->firstWhere('id', $categoryId)?->name
            : null;

        return redirect()->route('landing.agendar')->with('booking', [
            'id' => $booking->id,
            'date_label' => $booking->scheduled_at->locale('es')->translatedFormat('l j \d\e F'),
            'time_label' => $booking->scheduled_at->format('H:i'),
            'address' => $booking->fullAddress(),
            'customer_name' => $booking->clientName(),
            'services' => $selectedServices->pluck('name')->all(),
            'vehicle' => trim(implode(' ', array_filter([
                $validated['vehicle_brand'] ?? null,
                $validated['vehicle_model'] ?? null,
                $categoryName ? "({$categoryName})" : null,
            ]))),
            'total' => round((float) $booking->total_price, 2),
            'duration_minutes' => $duration,
            'payment_method' => $booking->payment_method,
        ])->with('success', 'Tu reserva quedó registrada.');
    }

    /**
     * Busca el primer empleado activo sin choque de horario.
     */
    private function findAvailableEmployee(Carbon $start, int $durationMinutes, ?int $ignoreBookingId = null): ?int
    {
        return User::query()
            ->where('role', 'Empleado')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->first(fn (int $employeeId) => ! $this->employeeHasConflict(
                $employeeId,
                $start,
                max($durationMinutes, 1),
                $ignoreBookingId
            ));
    }

    /**
     * Horarios de 1 hora (9:00 a 18:00) con al menos un empleado libre.
     *
     * @return array<int, string>
     */
    private function hourlyAvailability(Carbon $date, int $durationMinutes): array
    {
        $durationMinutes = max($durationMinutes, 1);
        $employees = User::where('role', 'Empleado')->where('is_active', true)->get();

        if ($employees->isEmpty()) {
            return [];
        }

        $workDayStart = $date->copy()->setTime(9, 0);
        $workDayEnd = $date->copy()->setTime(18, 0);
        $now = now();

        if ($date->isToday() && $now->gte($workDayEnd)) {
            return [];
        }

        $bookingsOnDate = Booking::with('services')
            ->whereDate('scheduled_at', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->get();

        $slot = $workDayStart->copy();

        if ($date->isToday()) {
            $nextHour = $now->copy()->startOfHour()->addHour();
            if ($nextHour->gt($slot)) {
                $slot = $nextHour;
            }
        }

        $availableSlots = [];

        while ($slot->copy()->addMinutes($durationMinutes)->lte($workDayEnd)) {
            $slotStart = $slot->copy();
            $slotEnd = $slot->copy()->addMinutes($durationMinutes);

            if ($date->isToday() && $slotStart->lte($now)) {
                $slot->addHour();
                continue;
            }

            $isAvailable = false;

            foreach ($employees as $employee) {
                $isEmployeeBusy = false;

                foreach ($bookingsOnDate->where('employee_id', $employee->id) as $booking) {
                    $bookingStart = $booking->scheduled_at->copy();
                    $bookingEnd = $booking->endsAt();

                    if ($slotStart->lt($bookingEnd) && $slotEnd->gt($bookingStart)) {
                        $isEmployeeBusy = true;
                        break;
                    }
                }

                if (! $isEmployeeBusy) {
                    $isAvailable = true;
                    break;
                }
            }

            if ($isAvailable) {
                $availableSlots[] = $slotStart->format('H:i');
            }

            $slot->addHour();
        }

        return $availableSlots;
    }
}
