<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta #{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }} - It's Fashion</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace, system-ui;
            background-color: #f3f4f6;
            color: #111827;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .actions-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
        }
        .btn {
            padding: 8px 16px;
            font-family: sans-serif;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background-color: #059669;
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: #047857;
        }
        .btn-secondary {
            background-color: #4b5563;
            color: #ffffff;
        }
        .btn-secondary:hover {
            background-color: #374151;
        }
        .ticket {
            width: 320px;
            background: #ffffff;
            padding: 20px 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 4px;
            font-size: 12px;
            line-height: 1.4;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .brand-title {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-bottom: 2px;
            color: #059669;
        }
        .separator {
            border-top: 1px dashed #9ca3af;
            margin: 10px 0;
        }
        .double-separator {
            border-top: 2px double #4b5563;
            margin: 10px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }
        th {
            border-bottom: 1px dashed #6b7280;
            padding: 4px 0;
            text-align: left;
        }
        td {
            padding: 4px 0;
            vertical-align: top;
        }
        .total-box {
            font-size: 14px;
            font-weight: 800;
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
        }
        .footer-msg {
            margin-top: 12px;
            font-size: 11px;
            color: #4b5563;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .ticket {
                box-shadow: none;
                width: 100%;
                max-width: 320px;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="actions-bar no-print">
        <button onclick="window.print()" class="btn btn-primary">
            🖨️ Imprimir Ticket
        </button>
        <button onclick="window.close(); if(!window.closed) history.back();" class="btn btn-secondary">
            ✖ Cerrar
        </button>
    </div>

    <div class="ticket">
        <div class="text-center">
            <div class="brand-title">IT'S FASHION</div>
            <div>Boutique de Ropa & Accesorios</div>
            <div>NIT: 900.281.199-5</div>
            <div>Calle Comercial #10 - Popayán, Cauca</div>
            <div>Tel: +57 322 394 5112</div>
        </div>

        <div class="separator"></div>

        <div class="text-center font-bold">COMPROBANTE DE VENTA POS</div>
        <div class="text-center font-bold" style="font-size: 13px;">N°: #VEN-{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</div>

        <div class="separator"></div>

        <div class="info-row">
            <span>Fecha:</span>
            <span>{{ $venta->fecha ? $venta->fecha->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A') }}</span>
        </div>
        <div class="info-row">
            <span>Atendido por:</span>
            <span>{{ $venta->usuario ? $venta->usuario->name : 'Cajero General' }}</span>
        </div>
        <div class="info-row">
            <span>Cliente:</span>
            <span>{{ $venta->cliente ? $venta->cliente->name : 'Cliente de Mostrador' }}</span>
        </div>
        @if($venta->cliente?->documento)
        <div class="info-row">
            <span>Cédula/Doc:</span>
            <span>{{ $venta->cliente->documento }}</span>
        </div>
        @endif
        <div class="info-row">
            <span>Método de Pago:</span>
            <span class="font-bold">{{ $venta->metodo_pago }}</span>
        </div>
        <div class="info-row">
            <span>Estado:</span>
            <span>{{ $venta->estado }}</span>
        </div>

        <div class="double-separator"></div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Desc.</th>
                    <th class="text-center" style="width: 15%;">Cant.</th>
                    <th class="text-right" style="width: 35%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $detalle)
                <tr>
                    <td>
                        <div class="font-bold">{{ $detalle->producto->nombre ?? 'Prenda' }}</div>
                        <div style="font-size: 10px; color: #4b5563;">
                            Talla: {{ $detalle->producto->talla ?? '—' }} | Color: {{ $detalle->producto->color ?? '—' }}
                        </div>
                    </td>
                    <td class="text-center">{{ $detalle->cantidad }}</td>
                    <td class="text-right">${{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="double-separator"></div>

        <div class="total-box">
            <span>TOTAL:</span>
            <span>${{ number_format($venta->total, 2) }}</span>
        </div>

        <div class="separator"></div>

        <div class="text-center footer-msg">
            <div>¡Gracias por tu compra!</div>
            <div>Conservar este comprobante para cualquier cambio o solicitud de garantía.</div>
            <div style="margin-top: 6px; font-size: 10px; color: #9ca3af;">Sistema It's Fashion v2.0</div>
        </div>
    </div>

</body>
</html>
