<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'documento',
        'username',
        'telefono',
        'email',
        'password',
        'rol_id',
        'puede_operar_caja',
        'estado',
        'fecha_registro',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'puede_operar_caja' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_registro' => 'datetime',
            'password' => 'hashed',
            'puede_operar_caja' => 'boolean',
        ];
    }

    /**
     * Relación con el rol del usuario.
     */
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /**
     * Relación: Un usuario/cliente tiene muchas ventas.
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    /**
     * Relación: Un usuario/cliente tiene muchas devoluciones a través de sus ventas.
     */
    public function devoluciones()
    {
        return $this->hasManyThrough(Devolucion::class, Venta::class, 'cliente_id', 'venta_id', 'id', 'id');
    }

    /**
     * Accesor para nombre completo compatible con interfaces estándar.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }
}
