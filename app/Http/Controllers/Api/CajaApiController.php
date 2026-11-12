<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaApiController extends Controller
{
    private function validarPermisoCaja(Request $request)
    {
        $user = $request->user();
        if ($user && (int)$user->rol_id === 2 && !$user->puede_operar_caja) {
            return response()->json([
                'success' => false,
                'message' => 'No cuentas con autorización para operar la caja registradora.',
            ], 403);
        }
        return null;
    }

    /**
     * Estado actual de la caja y sus movimientos.
     * GET /api/caja
     */
    public function index(Request $request)
    {
        if ($denied = $this->validarPermisoCaja($request)) {
            return $denied;
        }

        $caja = Caja::where('estado', 'Abierta')
            ->with('usuario')
            ->first();

        $movimientos = [];
        $saldoTeorico = 0;

        if ($caja) {
            $movimientos = MovimientoCaja::where('caja_id', $caja->id)
                ->orderBy('fecha', 'desc')
                ->take(30)
                ->get();

            $saldoTeorico = (float) $caja->saldo_inicial + (float) ($caja->total_ingresos ?? 0) - (float) ($caja->total_egresos ?? 0);
        }

        return response()->json([
            'success'       => true,
            'caja_abierta'  => $caja !== null,
            'caja'          => $caja,
            'saldo_teorico' => $saldoTeorico,
            'movimientos'   => $movimientos,
        ]);
    }

    /**
     * Apertura de turno de caja.
     * POST /api/caja/abrir
     */
    public function abrir(Request $request)
    {
        if ($denied = $this->validarPermisoCaja($request)) {
            return $denied;
        }

        $request->validate([
            'saldo_inicial' => ['required', 'numeric', 'min:0'],
        ], [
            'saldo_inicial.required' => 'El saldo inicial es obligatorio.',
            'saldo_inicial.min'      => 'El saldo inicial no puede ser negativo.',
        ]);

        $cajaAbiertaExists = Caja::where('estado', 'Abierta')->exists();
        if ($cajaAbiertaExists) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un turno de caja abierto en el sistema.',
            ], 422);
        }

        $caja = Caja::create([
            'fecha_apertura' => now(),
            'saldo_inicial'  => $request->saldo_inicial,
            'estado'         => 'Abierta',
            'usuario_id'     => $request->user()->id,
            'total_ingresos' => 0,
            'total_egresos'  => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Turno de caja abierto correctamente.',
            'caja'    => $caja->load('usuario'),
        ], 201);
    }

    /**
     * Cierre de turno de caja.
     * POST /api/caja/cerrar
     */
    public function cerrar(Request $request)
    {
        if ($denied = $this->validarPermisoCaja($request)) {
            return $denied;
        }

        $caja = Caja::where('estado', 'Abierta')->first();

        if (!$caja) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ningún turno de caja abierto para cerrar.',
            ], 422);
        }

        $request->validate([
            'saldo_final'   => ['required', 'numeric', 'min:0'],
            'justificacion' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $saldoTeorico = (float) $caja->saldo_inicial + (float) ($caja->total_ingresos ?? 0) - (float) ($caja->total_egresos ?? 0);
        $diferencia   = (float) $request->saldo_final - $saldoTeorico;
        $justificacion = trim($request->input('justificacion') ?? $request->input('observaciones') ?? '');

        if ($diferencia != 0 && empty($justificacion)) {
            return response()->json([
                'success' => false,
                'message' => 'La justificación es obligatoria cuando existe diferencia en el arqueo.',
            ], 422);
        }

        $caja->update([
            'fecha_cierre'  => now(),
            'saldo_final'   => $request->saldo_final,
            'diferencia'    => $diferencia,
            'justificacion' => $diferencia != 0 ? $justificacion : null,
            'estado'        => 'Cerrada',
        ]);

        return response()->json([
            'success'        => true,
            'message'        => 'Turno de caja cerrado correctamente.',
            'saldo_teorico'  => $saldoTeorico,
            'saldo_real'     => (float) $request->saldo_final,
            'diferencia'     => $diferencia,
            'caja'           => $caja,
        ]);
    }

    /**
     * Registrar movimiento manual (Ingreso / Egreso) en caja.
     * POST /api/caja/movimiento
     */
    public function registrarMovimiento(Request $request)
    {
        if ($denied = $this->validarPermisoCaja($request)) {
            return $denied;
        }

        $caja = Caja::where('estado', 'Abierta')->first();

        if (!$caja) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una caja abierta para registrar movimientos.',
            ], 422);
        }

        $request->validate([
            'tipo'     => ['required', 'in:Ingreso,Egreso'],
            'monto'    => ['required', 'numeric', 'gt:0'],
            'concepto' => ['required', 'string', 'max:255'],
        ], [
            'tipo.in'    => 'El tipo debe ser Ingreso o Egreso.',
            'monto.gt'   => 'El monto debe ser mayor a 0.',
        ]);

        $saldoActual = (float) $caja->saldo_inicial + (float) ($caja->total_ingresos ?? 0) - (float) ($caja->total_egresos ?? 0);
        if ($request->tipo === 'Egreso' && $request->monto > $saldoActual) {
            return response()->json([
                'success' => false,
                'message' => 'Fondos insuficientes en caja para registrar este egreso.',
            ], 422);
        }

        $movimiento = DB::transaction(function () use ($request, $caja) {
            $mov = MovimientoCaja::create([
                'caja_id'  => $caja->id,
                'tipo'     => $request->tipo,
                'monto'    => $request->monto,
                'concepto' => trim($request->concepto),
                'fecha'    => now(),
            ]);

            if ($request->tipo === 'Ingreso') {
                $caja->increment('total_ingresos', $request->monto);
            } else {
                $caja->increment('total_egresos', $request->monto);
            }

            return $mov;
        });

        return response()->json([
            'success'    => true,
            'message'    => 'Movimiento registrado correctamente en caja.',
            'movimiento' => $movimiento,
        ], 201);
    }
}
