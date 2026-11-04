<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Administración de usuarios y roles (solo administrador).
 *
 * Optimización aplicada: el listado selecciona únicamente las columnas
 * visibles y filtra por rol usando el índice compuesto users(rol, activo).
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('q'));

        $usuarios = User::query()
            ->select(['id', 'name', 'email', 'rol', 'activo'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($sub) use ($busqueda) {
                    $sub->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%");
                });
            })
            ->when($request->filled('rol'), fn ($query) => $query->where('rol', (string) $request->input('rol')))
            ->when(! $request->boolean('inactivos'), fn ($query) => $query->activos())
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $roles = User::ROLES;

        return view('usuarios.index', compact('usuarios', 'roles', 'busqueda'));
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'usuario' => new User(['rol' => 'recepcion', 'activo' => true]),
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['activo'] = true;

        User::create($datos);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => User::ROLES,
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $this->validar($request, $usuario);
        $datos['activo'] = $request->boolean('activo');

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        // Protección: un administrador no puede quitarse su propio acceso.
        if ($usuario->is($request->user()) && ! $datos['activo']) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        $usuario->update(['activo' => false]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado correctamente.');
    }

    public function activar(User $usuario): RedirectResponse
    {
        $usuario->update(['activo' => true]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario reactivado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'rol' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$usuario === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'activo' => ['sometimes', 'boolean'],
        ], [
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ], [
            'name' => 'nombre',
            'email' => 'correo',
            'rol' => 'rol',
            'password' => 'contraseña',
        ]);
    }
}
