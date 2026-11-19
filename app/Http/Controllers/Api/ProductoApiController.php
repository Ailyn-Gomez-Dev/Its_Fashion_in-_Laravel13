<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoApiController extends Controller
{
    /**
     * Catálogo de productos y KPIs para la app móvil Flutter.
     * GET /api/products
     */
    public function index(Request $request)
    {
        $query = Producto::with('categoria')->orderBy('id', 'desc');

        // Filtro por estado (opcional: Activo / Inactivo)
        if ($request->has('estado') && !empty($request->estado)) {
            $query->where('estado', $request->estado);
        }

        // Filtro por categoría (ID o nombre)
        if ($request->has('categoria_id') && !empty($request->categoria_id)) {
            $query->where('categoria_id', $request->categoria_id);
        } elseif ($request->has('categoria') && $request->categoria !== 'Todos') {
            $cat = $request->categoria;
            $query->whereHas('categoria', function ($q) use ($cat) {
                $q->where('nombre', $cat);
            });
        }

        // Búsqueda por término (nombre, talla, color, descripción)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('talla', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        // KPIs de negocio
        $kpis = [
            'total'            => Producto::count(),
            'activos'          => Producto::where('estado', 'Activo')->count(),
            'agotados'         => Producto::where('stock', 0)->count(),
            'criticos'         => Producto::whereRaw('stock > 0 AND stock <= stock_minimo')->count(),
            'valor_inventario' => (float) (Producto::selectRaw('SUM(precio_venta * stock) as total')->value('total') ?? 0),
        ];

        return response()->json([
            'status'   => 'success',
            'kpis'     => $kpis,
            'products' => $products,
        ]);
    }

    /**
     * Listado de categorías disponibles.
     * GET /api/categories
     */
    public function categories()
    {
        $categories = Categoria::withCount('productos')->orderBy('nombre', 'asc')->get();
        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * Crear una nueva categoría desde el móvil.
     * POST /api/categories
     */
    public function storeCategoria(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:100', 'unique:categorias,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique'   => 'Ya existe una categoría con este nombre.',
        ]);

        $categoria = Categoria::create([
            'nombre'      => trim($validated['nombre']),
            'descripcion' => trim($validated['descripcion'] ?? ''),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Categoría creada exitosamente.',
            'categoria' => $categoria,
        ], 201);
    }

    /**
     * Actualizar una categoría.
     * PUT/PATCH /api/categories/{id}
     */
    public function updateCategoria(Request $request, $id)
    {
        $categoria = Categoria::find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada.'], 404);
        }

        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('categorias', 'nombre')->ignore($categoria->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $categoria->update([
            'nombre'      => trim($validated['nombre']),
            'descripcion' => trim($validated['descripcion'] ?? ''),
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Categoría actualizada exitosamente.',
            'categoria' => $categoria,
        ]);
    }

    /**
     * Eliminar una categoría (si no tiene productos asociados).
     * DELETE /api/categories/{id}
     */
    public function destroyCategoria($id)
    {
        $categoria = Categoria::withCount('productos')->find($id);
        if (!$categoria) {
            return response()->json(['success' => false, 'message' => 'Categoría no encontrada.'], 404);
        }

        if ($categoria->productos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene productos asignados.',
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente.',
        ]);
    }

    /**
     * Registro de nuevo producto desde el móvil (Administrador/Vendedor).
     * POST /api/products
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'        => ['required', 'string', 'max:100'],
            'descripcion'   => ['nullable', 'string'],
            'precio_venta'  => ['required', 'numeric', 'gt:0'],
            'precio_compra' => ['required', 'numeric', 'gt:0'],
            'stock'         => ['required', 'integer', 'min:0'],
            'stock_minimo'  => ['nullable', 'integer', 'min:0'],
            'talla'         => ['required', 'string', 'max:10'],
            'color'         => ['required', 'string', 'max:30'],
            'estado'        => ['nullable', Rule::in(['Activo', 'Inactivo'])],
            'categoria_id'  => ['required', 'exists:categorias,id'],
            'imagen'        => ['nullable', 'string', 'max:500'],
            'imagen_file'   => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url'     => ['nullable', 'url', 'max:2048'],
        ], [
            'nombre.required'       => 'El nombre del producto es obligatorio.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
            'talla.required'        => 'La talla es obligatoria.',
            'color.required'        => 'El color es obligatorio.',
            'precio_compra.gt'      => 'El precio de compra debe ser mayor a 0.',
            'precio_venta.gt'       => 'El precio de venta debe ser mayor a 0.',
            'stock.min'             => 'El stock no puede ser negativo.',
            'imagen_file.image'     => 'El archivo debe ser una imagen válida.',
            'imagen_file.mimes'     => 'La imagen debe ser de tipo: jpeg, png, jpg, webp.',
            'imagen_file.max'       => 'La imagen no debe superar los 5MB.',
        ]);

        if ((float)$request->precio_venta <= (float)$request->precio_compra) {
            return response()->json([
                'success' => false,
                'message' => 'El precio de venta debe ser mayor al precio de compra.',
                'errors'  => [
                    'precio_venta' => ['El precio de venta debe ser mayor al precio de compra.'],
                ],
            ], 422);
        }

        $product = DB::transaction(function () use ($request) {
            // Determinar imagen (acepta archivo Multipart, URL o Base64)
            $imagen = null;
            if ($request->hasFile('imagen_file')) {
                $imagen = $request->file('imagen_file')->store('productos', 'public');
            } elseif ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen')->store('productos', 'public');
            } elseif ($request->filled('image_url')) {
                $imagen = trim($request->image_url);
            } elseif ($request->filled('imagen')) {
                $imagen = trim($request->imagen);
            }

            $nuevo = Producto::create([
                'nombre'        => trim($request->nombre),
                'descripcion'   => trim($request->descripcion ?? ''),
                'precio_venta'  => $request->precio_venta,
                'precio_compra' => $request->precio_compra,
                'stock'         => $request->stock,
                'stock_minimo'  => $request->stock_minimo ?? 5,
                'talla'         => strtoupper(trim($request->talla)),
                'color'         => trim($request->color),
                'estado'        => $request->estado ?? 'Activo',
                'categoria_id'  => $request->categoria_id,
                'imagen'        => $imagen,
            ]);

            if ($nuevo->stock > 0) {
                Inventario::registrarMovimiento(
                    $nuevo->id,
                    'Entrada',
                    $nuevo->stock,
                    $nuevo->stock
                );
            }

            return $nuevo;
        });

        $product->load('categoria');

        return response()->json([
            'success' => true,
            'message' => 'Producto registrado exitosamente en el inventario.',
            'data'    => $product,
        ], 201);
    }

    /**
     * Detalle de un producto.
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $product = Producto::with('categoria', 'inventarios')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    /**
     * Actualizar producto existente.
     * PUT /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $product = Producto::findOrFail($id);

        $request->validate([
            'nombre'        => ['required', 'string', 'max:100'],
            'descripcion'   => ['nullable', 'string'],
            'precio_venta'  => ['required', 'numeric', 'gt:0'],
            'precio_compra' => ['required', 'numeric', 'gt:0'],
            'stock'         => ['required', 'integer', 'min:0'],
            'stock_minimo'  => ['nullable', 'integer', 'min:0'],
            'talla'         => ['required', 'string', 'max:10'],
            'color'         => ['required', 'string', 'max:30'],
            'estado'        => ['nullable', Rule::in(['Activo', 'Inactivo'])],
            'categoria_id'  => ['required', 'exists:categorias,id'],
            'imagen'        => ['nullable', 'string', 'max:500'],
            'imagen_file'   => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_url'     => ['nullable', 'url', 'max:2048'],
        ], [
            'imagen_file.image' => 'El archivo debe ser una imagen válida.',
            'imagen_file.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, webp.',
            'imagen_file.max'   => 'La imagen no debe superar los 5MB.',
        ]);

        if ((float)$request->precio_venta <= (float)$request->precio_compra) {
            return response()->json([
                'success' => false,
                'message' => 'El precio de venta debe ser mayor al precio de compra.',
            ], 422);
        }

        DB::transaction(function () use ($request, $product) {
            $diferenciaStock = (int)$request->stock - (int)$product->stock;

            $imagen = $product->imagen;
            if ($request->hasFile('imagen_file')) {
                $imagen = $request->file('imagen_file')->store('productos', 'public');
            } elseif ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen')->store('productos', 'public');
            } elseif ($request->filled('image_url')) {
                $imagen = trim($request->image_url);
            } elseif ($request->filled('imagen')) {
                $imagen = trim($request->imagen);
            }

            $product->update([
                'nombre'        => trim($request->nombre),
                'descripcion'   => trim($request->descripcion ?? ''),
                'precio_venta'  => $request->precio_venta,
                'precio_compra' => $request->precio_compra,
                'stock'         => $request->stock,
                'stock_minimo'  => $request->stock_minimo ?? 5,
                'talla'         => strtoupper(trim($request->talla)),
                'color'         => trim($request->color),
                'estado'        => $request->estado ?? $product->estado,
                'categoria_id'  => $request->categoria_id,
                'imagen'        => $imagen,
            ]);

            // Registrar en Kardex si hubo variación directa de stock
            if ($diferenciaStock !== 0) {
                $tipo = $diferenciaStock > 0 ? 'Entrada' : 'Salida';
                Inventario::registrarMovimiento(
                    $product->id,
                    $tipo,
                    (int)$request->stock,
                    abs($diferenciaStock)
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data'    => $product->fresh('categoria'),
        ]);
    }

    /**
     * Alternar estado activo / inactivo de un producto.
     * POST o PATCH /api/products/{id}/toggle-estado
     */
    public function toggleEstado($id)
    {
        $product = Producto::findOrFail($id);
        $nuevoEstado = $product->estado === 'Activo' ? 'Inactivo' : 'Activo';
        $product->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success'      => true,
            'message'      => "Producto marcado como {$nuevoEstado}.",
            'nuevo_estado' => $nuevoEstado,
            'estado'       => $nuevoEstado,
            'data'         => $product->load('categoria'),
        ]);
    }

    /**
     * Eliminar un producto.
     * DELETE /api/products/{id}
     */
    public function destroy($id)
    {
        $product = Producto::findOrFail($id);
        $tieneVentas = $product->detallesVenta()->exists();
        if ($tieneVentas) {
            $product->update(['estado' => 'Inactivo']);
            return response()->json([
                'success' => true,
                'message' => 'El producto tiene ventas históricas asociadas; ha sido inactivado.',
            ]);
        }

        $product->delete();
        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    /**
     * Venta rápida / ajuste de stock desde la app móvil.
     * POST /api/products/{id}/adjust-stock
     */
    public function adjustStock(Request $request, $id)
    {
        $product = Producto::findOrFail($id);

        $request->validate([
            'cantidad' => ['required', 'integer'],
        ]);

        $cantidad = (int)$request->cantidad;
        $nuevoStock = $product->stock + $cantidad;

        if ($nuevoStock < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente para descontar.',
            ], 422);
        }

        $tipo = $cantidad < 0 ? 'Salida' : 'Entrada';
        $product->update(['stock' => $nuevoStock]);

        // Registrar en Kardex
        Inventario::registrarMovimiento(
            $product->id,
            $tipo,
            $nuevoStock,
            abs($cantidad)
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock actualizado correctamente.',
            'stock'   => $nuevoStock,
            'data'    => $product->load('categoria'),
        ]);
    }
}
