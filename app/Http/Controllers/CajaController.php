<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CajaController extends Controller
{
    private function validarPermisoCaja(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user && (int)$user->rol_id === 2 && !$user->puede_operar_caja) {
            abort(403, 'No cuentas con autorización para operar la caja registradora.');
        }
    }

    /**
     * Muestra la caja activa, movimientos y saldo teórico.
     */
    public function index(): View
    {
        $this->validarPermisoCaja();

        $caja = Caja::where('estado', 'Abierta')
            ->with('usuario')
            ->first();

        $movimientos = [];
        $saldo_teorico = 0;
        $ventas_turno = collect();
        $total_ventas_efectivo = 0;
        $total_ventas_transferencia = 0;
        $total_facturado_turno = 0;

        if ($caja) {
            $movimientos = MovimientoCaja::where('caja_id', $caja->id)
                ->orderBy('fecha', 'desc')
                ->get();

            $saldo_teorico = (float)$caja->saldo_inicial + (float)($caja->total_ingresos ?? 0) - (float)($caja->total_egresos ?? 0);

            // Ventas realizadas durante este turno activo (desde fecha_apertura)
            $ventas_turno = Venta::where('fecha', '>=', $caja->fecha_apertura)
                ->where('estado', 'Completada')
                ->with(['cliente', 'usuario'])
                ->orderBy('fecha', 'desc')
                ->get();

            $total_ventas_efectivo = $ventas_turno->where('metodo_pago', 'Efectivo')->sum('total');
            $total_ventas_transferencia = $ventas_turno->where('metodo_pago', '!=', 'Efectivo')->sum('total');
            $total_facturado_turno = $ventas_turno->sum('total');
        }

        $viewData = compact(
            'caja',
            'movimientos',
            'saldo_teorico',
            'ventas_turno',
            'total_ventas_efectivo',
            'total_ventas_transferencia',
            'total_facturado_turno'
        );

        /** @var User $user */
        $user = Auth::user();
        if ($user->rol_id === 2) {
            return view('empleado.caja', $viewData);
        }

        return view('admin.caja', $viewData);
    }

    /**
     * Abre un nuevo turno de caja.
     */
    public function abrir(Request $request): JsonResponse
    {
        $this->validarPermisoCaja();
        $request->validate([
            'saldo_inicial' => ['required', 'numeric', 'min:0'],
        ], [
            'saldo_inicial.required' => 'El saldo inicial es obligatorio.',
            'saldo_inicial.numeric' => 'El saldo inicial debe ser un número válido.',
            'saldo_inicial.min' => 'El saldo inicial no puede ser negativo.',
        ]);

        // Verificar que no haya otra caja abierta
        $cajaAbiertaExists = Caja::where('estado', 'Abierta')->exists();
        if ($cajaAbiertaExists) {
            return response()->json(['ok' => false, 'msg' => 'Ya existe un turno de caja abierto.'], 422);
        }

        try {
            $caja = Caja::create([
                'saldo_inicial' => $request->saldo_inicial,
                'total_ingresos' => 0,
                'total_egresos' => 0,
                'fecha_apertura' => now(),
                'estado' => 'Abierta',
                'usuario_id' => Auth::id(),
            ]);

            return response()->json([
                'ok' => true,
                'msg' => 'Caja abierta correctamente.',
                'id_caja' => $caja->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => 'Error al abrir caja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cierra el arqueo de caja actual.
     */
    public function cerrar(Request $request): JsonResponse
    {
        $this->validarPermisoCaja();
        $request->validate([
            'id_caja' => ['required', 'exists:cajas,id'],
            'saldo_final' => ['required', 'numeric', 'min:0'],
            'justificacion' => ['nullable', 'string', 'max:500'],
        ]);

        $caja = Caja::find($request->id_caja);
        if (!$caja || $caja->estado !== 'Abierta') {
            return response()->json(['ok' => false, 'msg' => 'La caja seleccionada no está activa.'], 422);
        }

        $saldo_teorico = $caja->saldo_inicial + ($caja->total_ingresos ?? 0) - ($caja->total_egresos ?? 0);
        $diferencia = $request->saldo_final - $saldo_teorico;

        // Si hay faltante o sobrante, se requiere justificación
        if ($diferencia != 0 && empty(trim($request->justificacion))) {
            return response()->json([
                'ok' => false,
                'msg' => 'La justificación es obligatoria cuando existe diferencia en el arqueo.'
            ], 422);
        }

        try {
            $caja->update([
                'saldo_final' => $request->saldo_final,
                'diferencia' => $diferencia,
                'justificacion' => $diferencia != 0 ? trim($request->justificacion) : null,
                'fecha_cierre' => now(),
                'estado' => 'Cerrada',
            ]);

            return response()->json([
                'ok' => true,
                'msg' => 'Caja cerrada y arqueada correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => 'Error al cerrar caja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra un movimiento de efectivo manual (Ingreso / Egreso).
     */
    public function registrarMovimiento(Request $request): JsonResponse
    {
        $this->validarPermisoCaja();
        $request->validate([
            'id_caja' => ['required', 'exists:cajas,id'],
            'tipo' => ['required', 'in:Ingreso,Egreso'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'concepto' => ['required', 'string', 'max:255'],
        ]);

        $caja = Caja::find($request->id_caja);
        if (!$caja || $caja->estado !== 'Abierta') {
            return response()->json(['ok' => false, 'msg' => 'La caja seleccionada no está abierta.'], 422);
        }

        $saldoActual = (float) $caja->saldo_inicial + (float) ($caja->total_ingresos ?? 0) - (float) ($caja->total_egresos ?? 0);
        if ($request->tipo === 'Egreso' && (float)$request->monto > $saldoActual) {
            return response()->json([
                'ok' => false,
                'msg' => 'Fondos insuficientes en caja para registrar este egreso. Saldo disponible: $' . number_format($saldoActual, 2)
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $caja) {
                // Registrar en la tabla movimientos_caja
                MovimientoCaja::create([
                    'caja_id' => $caja->id,
                    'tipo' => $request->tipo,
                    'monto' => $request->monto,
                    'concepto' => trim($request->concepto),
                    'fecha' => now(),
                ]);

                // Actualizar totales en la caja activa
                if ($request->tipo === 'Ingreso') {
                    $caja->increment('total_ingresos', $request->monto);
                } else {
                    $caja->increment('total_egresos', $request->monto);
                }
            });

            return response()->json([
                'ok' => true,
                'msg' => 'Movimiento registrado correctamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => 'Error al registrar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }
}
