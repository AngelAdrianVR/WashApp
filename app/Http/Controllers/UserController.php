<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $filters = $request->only('search');

        $users = User::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString() // <-- Añadir esto para que el filtro se mantenga en la paginación
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'profile_photo_url' => $user->profile_photo_url,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $filters, // <-- Pasar los filtros a la vista
        ]);
    }

    public function create()
    {
        // Renderiza el componente de Vue que acabamos de crear
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        // Validación de los datos del formulario
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', Rule::in(['Admin', 'Empleado', 'Cliente'])],
            'phone_number' => 'nullable|string|max:10',
            'is_active' => 'required|boolean',
        ]);

        // Creación del usuario en la base de datos
        // El modelo User se encargará de hashear la contraseña gracias al 'cast'
        User::create($validated);

        // Redirigir al listado de usuarios con un mensaje de éxito
        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user)
    {
        //
    }

    public function edit(User $user)
    {
        // Renderiza el componente de Vue y le pasa el usuario como prop
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Validación de los datos del formulario de edición
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // La regla 'unique' debe ignorar al usuario actual
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // La contraseña es opcional. Si se envía, debe tener al menos 8 caracteres.
            'password' => 'nullable|string|min:8',
            'role' => ['required', 'string', Rule::in(['Admin', 'Empleado', 'Cliente'])],
            'phone_number' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        // Si se proporcionó una nueva contraseña, la actualizamos.
        // Si no, la eliminamos del array para no sobreescribirla como null.
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Actualizamos el usuario con los datos validados
        $user->update($validated);

        // Redirigir al listado con un mensaje de éxito
        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        // Prevenir que un usuario se elimine a sí mismo
        if ($user->id === auth()->id()) {
            return back()->withErrors(['general' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    /**
     * Update the specified user's status.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleIsActive(User $user)
    {
        $user->is_active = !$user->is_active; // Cambiamos el booleano
        $user->save();

        return back()->with('success', 'Estado del usuario actualizado.');
    }
}
