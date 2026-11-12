<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de nuevo cliente desde la aplicación móvil Flutter.
     * POST /api/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'apellido'  => ['required', 'string', 'max:100'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username'],
            'telefono'  => ['required', 'string', 'max:15'],
            'email'     => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nombre.required'    => 'El nombre es obligatorio.',
            'apellido.required'  => 'El apellido es obligatorio.',
            'username.required'  => 'El nombre de usuario es obligatorio.',
            'username.unique'    => 'Este nombre de usuario ya está registrado.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.unique'       => 'Este correo electrónico ya está registrado.',
            'telefono.required'  => 'El teléfono es obligatorio.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Rol 3 es 'cliente' en el seeder de Its Fashion
        $user = User::create([
            'nombre'         => trim($validated['nombre']),
            'apellido'       => trim($validated['apellido']),
            'username'       => trim($validated['username']),
            'telefono'       => trim($validated['telefono']),
            'email'          => trim($validated['email']),
            'password'       => Hash::make($validated['password']),
            'rol_id'         => 3,
            'estado'         => 'Activo',
            'fecha_registro' => now(),
        ]);

        $user->load('rol');
        $token = $user->createToken('its_fashion_flutter')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada exitosamente en It\'s Fashion.',
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nombre'    => $user->nombre,
                'apellido'  => $user->apellido,
                'username'  => $user->username,
                'email'     => $user->email,
                'telefono'  => $user->telefono,
                'rol_id'    => $user->rol_id,
                'rol'       => $user->rol?->descripcion ?? 'cliente',
                'estado'    => $user->estado,
            ],
        ], 201);
    }

    /**
     * Inicio de sesión para Administrador, Vendedor o Cliente (con email o username).
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required'    => 'Ingresa tu correo electrónico o nombre de usuario.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $login = trim($request->login);
        $throttleKey = \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower($login) . '|' . $request->ip());

        // Bloqueo tras 5 intentos fallidos
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message'     => "Demasiados intentos fallidos. Tu acceso ha sido pausado temporalmente por seguridad. Intenta nuevamente en {$seconds} segundos.",
                'retry_after' => $seconds,
            ], 429);
        }

        // Buscar por email o username
        $user = User::with('rol')
            ->where(function ($q) use ($login) {
                $q->where('email', $login)
                  ->orWhere('username', $login);
            })
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Las credenciales proporcionadas no son válidas.',
                'errors'  => [
                    'login' => ['El usuario o contraseña son incorrectos.'],
                ],
            ], 422);
        }

        if ($user->estado !== 'Activo') {
            return response()->json([
                'message' => 'Tu cuenta se encuentra inactiva. Contacta al administrador.',
            ], 403);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        $token = $user->createToken('its_fashion_flutter')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token'   => $token,
            'user'    => [
                'id'        => $user->id,
                'nombre'    => $user->nombre,
                'apellido'  => $user->apellido,
                'username'  => $user->username,
                'email'     => $user->email,
                'telefono'  => $user->telefono,
                'rol_id'    => $user->rol_id,
                'rol'       => $user->rol?->descripcion ?? 'cliente',
                'estado'    => $user->estado,
            ],
        ], 200);
    }

    /**
     * Perfil de usuario autenticado.
     * GET /api/user
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('rol');
        return response()->json([
            'user' => [
                'id'        => $user->id,
                'nombre'    => $user->nombre,
                'apellido'  => $user->apellido,
                'username'  => $user->username,
                'email'     => $user->email,
                'telefono'  => $user->telefono,
                'rol_id'    => $user->rol_id,
                'rol'       => $user->rol?->descripcion ?? 'cliente',
                'estado'    => $user->estado,
            ],
        ]);
    }

    /**
     * Cierre de sesión y revocación del token Sanctum.
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
