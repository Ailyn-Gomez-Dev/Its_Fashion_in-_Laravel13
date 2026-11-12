<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificacionApiController extends Controller
{
    /**
     * Listado de notificaciones no leídas del usuario autenticado.
     * GET /api/notificaciones
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notificaciones = $user->unreadNotifications;

        return response()->json([
            'success' => true,
            'count'   => $notificaciones->count(),
            'data'    => $notificaciones,
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas.
     * POST /api/notificaciones/leer-todas
     */
    public function leerTodas(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones han sido marcadas como leídas.',
        ]);
    }
}
