<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioApiController extends Controller
{
    /**
     * Listar usuarios internos (Administradores y Vendedores).
     * GET /api/usuarios
     */
    public function index(Request $request)
    {
        $query = User::with('rol')
            ->whereIn('rol_id', [1, 2]); // Admin y Vendedor

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%");
            });
        }

        $usuarios = $query->orderBy('nombre', 'asc')->get();

        return response()->json([
            'success' => true,
            'data'    => $usuarios,
            'total'   => $usuarios->count(),
        ]);
    }

    /**
     * Listar roles disponibles para asignar a empleados.
     * GET /api/roles
     */
    public function roles()
    {
        $roles = Rol::whereIn('id', [1, 2])
            ->orWhereIn('descripcion', ['admin', 'vendedor', 'Administrador', 'Empleado'])
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $roles,
        ]);
    }

    /**
     * Registrar un nuevo usuario del sistema (Admin o Vendedor).
     * POST /api/usuarios
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'            => ['required', 'string', 'max:100'],
            'apellido'          => ['required', 'string', 'max:100'],
            'documento'         => ['nullable', 'string', 'max:20', 'unique:users,documento'],
            'username'          => ['required', 'string', 'max:50', 'unique:users,username'],
            'email'             => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'telefono'          => ['required', 'string', 'max:15'],
            'password'          => ['required', Password::min(6)],
            'rol_id'            => ['required', Rule::in([1, 2])],
            'puede_operar_caja' => ['nullable', 'boolean'],
            'estado'            => ['nullable', Rule::in(['Activo', 'Inactivo'])],
        ]);

        $usuario = User::create([
            'nombre'            => trim($validated['nombre']),
            'apellido'          => trim($validated['apellido']),
            'documento'         => !empty($validated['documento']) ? trim($validated['documento']) : null,
            'username'          => trim($validated['username']),
            'email'             => trim($validated['email']),
            'telefono'          => trim($validated['telefono']),
            'password'          => Hash::make($validated['password']),
            'rol_id'            => $validated['rol_id'],
            'puede_operar_caja' => $request->boolean('puede_operar_caja', true),
            'estado'            => $validated['estado'] ?? 'Activo',
            'fecha_registro'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente.',
            'data'    => $usuario->load('rol'),
        ], 201);
    }

    /**
     * Ver detalle de un usuario.
     * GET /api/usuarios/{id}
     */
    public function show($id)
    {
        $usuario = User::with('rol')->find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $usuario,
        ]);
    }

    /**
     * Actualizar usuario.
     * PUT/PATCH /api/usuarios/{id}
     */
    public function update(Request $request, $id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre'            => ['required', 'string', 'max:100'],
            'apellido'          => ['required', 'string', 'max:100'],
            'documento'         => ['nullable', 'string', 'max:20', Rule::unique('users', 'documento')->ignore($usuario->id)],
            'username'          => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($usuario->id)],
            'email'             => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')->ignore($usuario->id)],
            'telefono'          => ['required', 'string', 'max:15'],
            'password'          => ['nullable', Password::min(6)],
            'rol_id'            => ['required', Rule::in([1, 2])],
            'puede_operar_caja' => ['nullable', 'boolean'],
            'estado'            => ['required', Rule::in(['Activo', 'Inactivo'])],
        ]);

        // Protección contra auto-bloqueo
        $esActual = Auth::id() === $usuario->id;
        if ($esActual && $validated['estado'] === 'Inactivo') {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propia cuenta en sesión.',
            ], 422);
        }

        $usuario->nombre = trim($validated['nombre']);
        $usuario->apellido = trim($validated['apellido']);
        $usuario->documento = !empty($validated['documento']) ? trim($validated['documento']) : null;
        $usuario->username = trim($validated['username']);
        $usuario->email = trim($validated['email']);
        $usuario->telefono = trim($validated['telefono']);
        $usuario->rol_id = $validated['rol_id'];
        $usuario->estado = $validated['estado'];
        $usuario->puede_operar_caja = $request->boolean('puede_operar_caja');

        if (!empty($validated['password'])) {
            $usuario->password = Hash::make($validated['password']);
        }

        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data'    => $usuario->load('rol'),
        ]);
    }

    /**
     * Alternar estado Activo / Inactivo de un usuario.
     * POST/PATCH /api/usuarios/{id}/toggle-estado
     */
    public function toggleEstado($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        if (Auth::id() === $usuario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar el estado de tu propia cuenta.',
            ], 422);
        }

        $nuevoEstado = $usuario->estado === 'Activo' ? 'Inactivo' : 'Activo';

        // Si se va a inactivar a un admin, asegurar que no sea el único
        if ($usuario->rol_id == 1 && $nuevoEstado === 'Inactivo') {
            $adminsActivos = User::where('rol_id', 1)->where('estado', 'Activo')->count();
            if ($adminsActivos <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'No es posible inactivar al único administrador activo del sistema.',
                ], 422);
            }
        }

        $usuario->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => "Estado del usuario actualizado a {$nuevoEstado}.",
            'estado'  => $nuevoEstado,
        ]);
    }
}
