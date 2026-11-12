<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    // Nombre de la tabla en la base de datos
    protected $table = 'productos';

    // Atributos asignables masivamente (fillable)
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_venta',
        'precio_compra',
        'stock',
        'stock_minimo',
        'talla',
        'color',
        'estado',
        'categoria_id',
        'imagen',
    ];

    /**
     * Atributos dinámicos que se añaden a la representación en JSON / Array.
     */
    protected $appends = [
        'imagen_url',
    ];

    /**
     * Accesor para obtener una URL válida y segura de la imagen.
     */
    public function getImagenUrlAttribute(): string
    {
        if (empty($this->imagen)) {
            // Imagen por defecto estilizada de moda
            return 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=600&q=80';
        }

        // Si ya es una URL completa o una cadena en Base64
        if (str_starts_with($this->imagen, 'http') || str_starts_with($this->imagen, 'data:image')) {
            return $this->imagen;
        }

        // Si es una ruta almacenada en storage local
        return asset('storage/' . ltrim($this->imagen, '/'));
    }

    /**
     * Relación: Un producto pertenece a una categoría.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Relación: Un producto puede tener muchos movimientos de inventario.
     */
    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'producto_id');
    }

    /**
     * Relación: Un producto tiene detalles de ventas.
     */
    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    /**
     * Relación: Un producto tiene detalles de compras.
     */
    public function detallesCompra()
    {
        return $this->hasMany(DetalleCompra::class, 'producto_id');
    }
}