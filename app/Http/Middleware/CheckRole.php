<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|int  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        $userRoleId = (int) $user->rol_id;
        $userRoleDesc = strtolower($user->rol?->descripcion ?? '');

        // Mapa de roles compatibles con identificadores o descripciones
        $roleMap = [
            'admin'         => 1,
            'administrador' => 1,
            '1'             => 1,
            'vendedor'      => 2,
            'empleado'      => 2,
            '2'             => 2,
            'cliente'       => 3,
            '3'             => 3,
        ];

        $allowedRoleIds = [];
        foreach ($roles as $role) {
            $key = strtolower(trim((string)$role));
            if (isset($roleMap[$key])) {
                $allowedRoleIds[] = $roleMap[$key];
            } elseif (is_numeric($key)) {
                $allowedRoleIds[] = (int)$key;
            }
        }

        if (in_array($userRoleId, $allowedRoleIds, true) || in_array($userRoleDesc, array_map('strtolower', $roles), true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'No tienes permisos suficientes para realizar esta acción.'], 403);
        }

        abort(403, 'Acceso denegado: No cuentas con los permisos requeridos.');
    }
}
