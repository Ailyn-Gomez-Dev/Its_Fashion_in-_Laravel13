<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ClienteApiController extends Controller
{
    /**
     * Listado y búsqueda de clientes.
     * GET /api/clientes
     */
    public function index(Request $request)
    {
        $query = User::where('rol_id', 3)
            ->withCount(['ventas', 'devoluciones'])
            ->orderBy('nombre', 'asc');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        if ($request->has('estado') && !empty($request->estado)) {
            $query->where('estado', $request->estado);
        }

        $clientes = $query->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data'    => $clientes,
        ]);
    }

    /**
     * Registrar un nuevo cliente desde la app móvil.
     * POST /api/clientes
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'   => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'telefono' => ['required', 'string', 'max:15'],
            'email'    => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'username.unique' => 'Este documento o usuario ya existe.',
            'email.unique'    => 'Este correo electrónico ya está registrado.',
        ]);

        $password = !empty($validated['password']) 
            ? Hash::make($validated['password']) 
            : Hash::make($validated['username']); // Contraseña inicial por defecto su documento

        $cliente = User::create([
            'nombre'         => trim($validated['nombre']),
            'apellido'       => trim($validated['apellido']),
            'username'       => trim($validated['username']),
            'telefono'       => trim($validated['telefono']),
            'email'          => trim($validated['email']),
            'password'       => $password,
            'rol_id'         => 3,
            'estado'         => 'Activo',
            'fecha_registro' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente registrado exitosamente.',
            'cliente' => $cliente,
        ], 201);
    }

    /**
     * Detalle y compras de un cliente.
     * GET /api/clientes/{id}
     */
    public function show($id)
    {
        $cliente = User::where('rol_id', 3)
            ->withCount(['ventas', 'devoluciones'])
            ->findOrFail($id);

        $ultimasCompras = Venta::with('detalles.producto')
            ->where('cliente_id', $cliente->id)
            ->latest('fecha')
            ->take(10)
            ->get();

        return response()->json([
            'success'         => true,
            'cliente'         => $cliente,
            'ultimas_compras' => $ultimasCompras,
        ]);
    }

    /**
     * Cambiar estado activo/inactivo del cliente.
     * PATCH /api/clientes/{id}/toggle-estado
     */
    public function toggleEstado($id)
    {
        $cliente = User::where('rol_id', 3)->findOrFail($id);

        $nuevoEstado = $cliente->estado === 'Activo' ? 'Inactivo' : 'Activo';
        $cliente->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success'      => true,
            'message'      => "Estado del cliente actualizado a {$nuevoEstado}.",
            'nuevo_estado' => $nuevoEstado,
        ]);
    }

    /**
     * Historial de compras de un cliente específico.
     * GET /api/clientes/{id}/compras
     */
    public function compras($id)
    {
        $cliente = User::where('rol_id', 3)->findOrFail($id);

        $compras = Venta::with(['detalles.producto'])
            ->where('cliente_id', $cliente->id)
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'cliente' => $cliente->only(['id', 'nombre', 'apellido', 'username']),
            'data'    => $compras,
        ]);
    }

    /**
     * Historial de devoluciones de un cliente específico.
     * GET /api/clientes/{id}/devoluciones
     */
    public function devoluciones($id)
    {
        $cliente = User::where('rol_id', 3)->findOrFail($id);

        $devoluciones = \App\Models\Devolucion::with(['venta', 'detalles.producto'])
            ->where('cliente_id', $cliente->id)
            ->orderBy('fecha_solicitud', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'cliente' => $cliente->only(['id', 'nombre', 'apellido', 'username']),
            'data'    => $devoluciones,
        ]);
    }
}
