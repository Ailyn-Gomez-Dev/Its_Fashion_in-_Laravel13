<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProveedorApiController extends Controller
{
    /**
     * Listar todos los proveedores con opción de búsqueda.
     * GET /api/proveedores
     */
    public function index(Request $request)
    {
        $query = Proveedor::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('contacto', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        $proveedores = $query->orderBy('nombre', 'asc')->get();

        return response()->json([
            'success' => true,
            'data'    => $proveedores,
            'total'   => $proveedores->count(),
        ]);
    }

    /**
     * Crear un nuevo proveedor.
     * POST /api/proveedores
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'contacto'  => ['required', 'string', 'max:100'],
            'telefono'  => ['required', 'string', 'max:15'],
            'email'     => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'documento' => ['nullable', 'string', 'max:20', 'unique:proveedores,documento'],
        ], [
            'nombre.required'   => 'El nombre del proveedor es obligatorio.',
            'contacto.required' => 'El nombre de contacto es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'documento.unique'  => 'Ya existe un proveedor registrado con ese documento.',
            'email.email'       => 'Ingresa un correo electrónico válido.',
        ]);

        $proveedor = Proveedor::create([
            'nombre'    => trim($validated['nombre']),
            'contacto'  => trim($validated['contacto']),
            'telefono'  => trim($validated['telefono']),
            'email'     => !empty($validated['email']) ? trim($validated['email']) : null,
            'direccion' => !empty($validated['direccion']) ? trim($validated['direccion']) : null,
            'documento' => !empty($validated['documento']) ? trim($validated['documento']) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor registrado exitosamente.',
            'data'    => $proveedor,
        ], 201);
    }

    /**
     * Ver detalle de un proveedor.
     * GET /api/proveedores/{id}
     */
    public function show($id)
    {
        $proveedor = Proveedor::withCount('compras')->find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $proveedor,
        ]);
    }

    /**
     * Actualizar proveedor.
     * PUT/PATCH /api/proveedores/{id}
     */
    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'contacto'  => ['required', 'string', 'max:100'],
            'telefono'  => ['required', 'string', 'max:15'],
            'email'     => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'documento' => ['nullable', 'string', 'max:20', Rule::unique('proveedores', 'documento')->ignore($proveedor->id)],
        ], [
            'nombre.required'   => 'El nombre es obligatorio.',
            'contacto.required' => 'El contacto es obligatorio.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'documento.unique'  => 'Ya existe un proveedor registrado con ese documento.',
        ]);

        $proveedor->update([
            'nombre'    => trim($validated['nombre']),
            'contacto'  => trim($validated['contacto']),
            'telefono'  => trim($validated['telefono']),
            'email'     => !empty($validated['email']) ? trim($validated['email']) : null,
            'direccion' => !empty($validated['direccion']) ? trim($validated['direccion']) : null,
            'documento' => !empty($validated['documento']) ? trim($validated['documento']) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor actualizado correctamente.',
            'data'    => $proveedor,
        ]);
    }

    /**
     * Eliminar proveedor (solo si no tiene compras asociadas).
     * DELETE /api/proveedores/{id}
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado.',
            ], 404);
        }

        $tieneCompras = DB::table('compras')->where('proveedor_id', $proveedor->id)->exists();

        if ($tieneCompras) {
            return response()->json([
                'success' => false,
                'message' => 'No es posible eliminar el proveedor porque tiene compras de inventario asociadas.',
            ], 422);
        }

        $proveedor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Proveedor eliminado correctamente.',
        ]);
    }
}
