@extends('layouts.sidebaradmin')

@section('titulo', 'Boutique Dashboard')

@section('content')
<div class="p-6 md:p-8 font-sans">

    <!-- Mensaje de bienvenida -->
    <div class="mb-6 md:mb-8 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h2 class="text-2xl md:text-3xl font-serif font-bold text-brand-dark mb-1">
                Bienvenido, {{ explode(' ', auth()->user()->nombre)[0] }}
            </h2>
            <p class="text-brand-muted font-light text-sm md:text-base">
                Aquí tienes un resumen de la actividad de tu boutique hoy.
            </p>
        </div>
        <div class="text-sm font-semibold text-slate-500 bg-slate-100 px-4 py-2 rounded-xl">
            <i class="far fa-calendar-alt mr-1.5"></i>{{ today()->format('d/m/Y') }}
        </div>
    </div>

    <!-- Banner Alerta de Devoluciones Pendientes -->
    @if ($devolucionesPendientes > 0)
        <a href="{{ route('devoluciones.index') }}" 
            class="flex items-center gap-4 mb-8 p-5 bg-amber-50 border border-amber-200 rounded-2xl hover:bg-amber-100/70 transition duration-300 shadow-sm group">
            <div class="w-12 h-12 bg-amber-500 text-white rounded-2xl flex items-center justify-center flex-shrink-0 shadow-md transition-transform group-hover:scale-105">
                <i class="fas fa-undo-alt text-lg"></i>
            </div>
            <div class="flex-1">
                <p class="text-base font-bold text-amber-800">
                    Atención requerida: {{ $devolucionesPendientes }} {{ $devolucionesPendientes == 1 ? 'devolución pendiente' : 'devoluciones pendientes' }}
                </p>
                <p class="text-sm text-amber-700 font-light mt-0.5">Haz clic aquí para revisar y resolver las solicitudes pendientes de reembolso.</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-white border border-amber-200 flex items-center justify-center text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition duration-300">
                <i class="fas fa-arrow-right text-xs"></i>
            </div>
        </a>
    @endif

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- Ventas Hoy -->
        <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600">Hoy</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 mb-1">Ventas de Hoy</p>
            <h3 class="text-2xl font-bold text-slate-800">${{ number_format($ventasHoy, 2) }}</h3>
            <p class="text-xs mt-2 text-slate-400 font-medium">
                <i class="fas fa-receipt mr-1"></i>{{ $ventasHoyCount }} transacciones
            </p>
        </div>

        <!-- Productos -->
        <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-500">
                    <i class="fas fa-box-open text-xl"></i>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-600">Catálogo</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 mb-1">Prendas en Catálogo</p>
            <h3 class="text-2xl font-bold text-slate-800">{{ $totalProductos }}</h3>
            <p class="text-xs mt-2 text-slate-400 font-medium">
                <i class="fas fa-check-circle text-emerald-500 mr-1"></i>{{ $productosActivos }} activos en tienda
            </p>
        </div>

        <!-- Ingresos -->
        <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300 relative overflow-hidden">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full opacity-50 z-0"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-500">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600">Mes</span>
                </div>
                <p class="text-sm font-semibold text-slate-500 mb-1">Ingresos del Mes</p>
                <h3 class="text-2xl font-bold text-slate-800">${{ number_format($ingresosMes, 2) }}</h3>
                <p class="text-xs mt-2 text-slate-400 font-medium">
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i>{{ $ingresosMesCount }} ventas concretadas
                </p>
            </div>
        </div>

        <!-- Clientes -->
        <div class="bg-white rounded-[1.5rem] p-6 border border-slate-100 shadow-sm hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-500">
                    <i class="fas fa-user-friends text-xl"></i>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-600">Comunidad</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 mb-1">Clientes Registrados</p>
            <h3 class="text-2xl font-bold text-slate-800">{{ $totalClientes }}</h3>
            <p class="text-xs mt-2 text-slate-400 font-medium">
                <i class="fas fa-users mr-1"></i>{{ $clientesActivos }} activos
            </p>
        </div>

    </div>

    @if ($totalProductos === 0 && $totalClientes === 0)
        <!-- Empty state -->
        <div class="bg-white rounded-[2rem] border border-slate-100 p-8 md:p-16 text-center shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-gray-100">
                <i class="fas fa-store-slash text-gray-300 text-3xl"></i>
            </div>
            <h3 class="text-xl font-serif font-bold text-brand-dark mb-2">Tu boutique está lista</h3>
            <p class="text-brand-muted font-light max-w-md mx-auto">
                Aún no hay datos registrados. Comienza agregando categorías y productos a tu inventario para ver las estadísticas aquí.
            </p>
            <div class="mt-8 flex justify-center gap-4">
                <a href="{{ route('categorias.index') }}" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-full hover:bg-blue-700 transition-colors shadow-md shadow-blue-600/20">
                    <i class="fas fa-plus mr-2"></i>Ver Categorías
                </a>
            </div>
        </div>
    @else
        <!-- SECCIÓN DE MÉTRICAS Y GRÁFICAS ANALÍTICAS -->
        <div class="space-y-6">

            <!-- Fila 1: Tendencia de Ventas (2/3) y Métodos de Pago (1/3) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Gráfico de Línea: Tendencia de Ventas Últimos 7 Días -->
                <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-100 p-6 md:p-7 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-600 ring-4 ring-blue-50"></span>
                                <h3 class="text-lg font-bold text-slate-800">Tendencia de Ventas</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Ingresos diarios registrados en los últimos 7 días</p>
                        </div>
                        <div class="text-right bg-blue-50/70 border border-blue-100 px-4 py-1.5 rounded-xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total 7 Días</span>
                            <span class="text-base font-bold text-blue-600">${{ number_format(array_sum($chartVentas), 2) }}</span>
                        </div>
                    </div>
                    <div class="relative w-full h-72">
                        <canvas id="chartTendenciaVentas"></canvas>
                    </div>
                </div>

                <!-- Gráfico Donut: Métodos de Pago -->
                <div class="bg-white rounded-[2rem] border border-slate-100 p-6 md:p-7 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></span>
                                <h3 class="text-lg font-bold text-slate-800">Métodos de Pago</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Distribución de ingresos por canal</p>
                        </div>
                    </div>
                    <div class="relative w-full h-48 flex items-center justify-center my-auto">
                        <canvas id="chartMetodosPago"></canvas>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-center">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100/70">
                            <div class="flex items-center justify-center gap-1.5 text-xs text-slate-500 mb-0.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Efectivo
                            </div>
                            <p class="text-sm font-bold text-slate-800">${{ number_format($chartMetodos['Efectivo'], 2) }}</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100/70">
                            <div class="flex items-center justify-center gap-1.5 text-xs text-slate-500 mb-0.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span> Transf.
                            </div>
                            <p class="text-sm font-bold text-slate-800">${{ number_format($chartMetodos['Transferencia'], 2) }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Fila 2: Distribución por Categorías y Salud de Inventario / Top Productos -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Gráfico de Barras: Stock por Categoría (2/3) -->
                <div class="lg:col-span-2 bg-white rounded-[2rem] border border-slate-100 p-6 md:p-7 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></span>
                                <h3 class="text-lg font-bold text-slate-800">Prendas por Categoría</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Volumen de existencias en almacén por línea de producto</p>
                        </div>
                    </div>
                    <div class="relative w-full h-72">
                        <canvas id="chartCategorias"></canvas>
                    </div>
                </div>

                <!-- Salud de Inventario & Top Prendas (1/3) -->
                <div class="bg-white rounded-[2rem] border border-slate-100 p-6 md:p-7 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-3 h-3 rounded-full bg-amber-500 ring-4 ring-amber-50"></span>
                            <h3 class="text-lg font-bold text-slate-800">Salud de Inventario</h3>
                        </div>
                        <p class="text-xs text-slate-400 mb-4">Estado operativo del catálogo activo</p>

                        <div class="space-y-2.5 mb-5">
                            <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50/70 border border-emerald-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-emerald-900">Stock Óptimo</span>
                                </div>
                                <span class="text-xs font-bold text-emerald-700 bg-white px-2 py-0.5 rounded-md border border-emerald-200">{{ $stockSalud['normal'] }} prendas</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50/70 border border-amber-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xs">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-amber-900">Stock Crítico</span>
                                </div>
                                <span class="text-xs font-bold text-amber-700 bg-white px-2 py-0.5 rounded-md border border-amber-200">{{ $stockSalud['critico'] }} prendas</span>
                            </div>

                            <div class="flex items-center justify-between p-3 rounded-xl bg-rose-50/70 border border-rose-100">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-rose-900">Agotados</span>
                                </div>
                                <span class="text-xs font-bold text-rose-700 bg-white px-2 py-0.5 rounded-md border border-rose-200">{{ $stockSalud['agotado'] }} prendas</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <i class="fas fa-fire text-amber-500"></i> Prendas Más Vendidas
                        </h4>
                        @if($topProductos->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($topProductos->take(3) as $tp)
                                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                        <div class="truncate max-w-[150px]">
                                            <p class="font-bold text-slate-800 truncate">{{ $tp->producto->nombre ?? 'Producto #' . $tp->producto_id }}</p>
                                            <p class="text-[10px] text-slate-400">{{ $tp->producto->talla ?? '' }} / {{ $tp->producto->color ?? '' }}</p>
                                        </div>
                                        <span class="font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                            {{ $tp->total_vendido }} u.
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic text-center py-2">Sin ventas registradas aún</p>
                        @endif
                    </div>
                </div>

            </div>

        </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // ── 1. GRÁFICA TENDENCIA DE VENTAS ──────────────────────────────────────────
    const ctxVentas = document.getElementById('chartTendenciaVentas');
    if (ctxVentas) {
        const labelsVentas = {!! json_encode($chartDias) !!};
        const dataVentas = {!! json_encode($chartVentas) !!};

        const gradient = ctxVentas.getContext('2d').createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

        new Chart(ctxVentas, {
            type: 'line',
            data: {
                labels: labelsVentas,
                datasets: [{
                    label: 'Ventas ($)',
                    data: dataVentas,
                    borderColor: '#2563eb',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#2563eb',
                    pointHoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Outfit', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Outfit', size: 12 },
                        padding: 10,
                        cornerRadius: 12,
                        callbacks: {
                            label: function(c) {
                                return ' Ventas: $' + Number(c.raw).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { family: 'Outfit', size: 11 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            color: '#64748b',
                            font: { family: 'Outfit', size: 11 },
                            callback: function(v) { return '$' + Number(v).toLocaleString(); }
                        }
                    }
                }
            }
        });
    }

    // ── 2. GRÁFICA MÉTODOS DE PAGO ──────────────────────────────────────────────
    const ctxMetodos = document.getElementById('chartMetodosPago');
    if (ctxMetodos) {
        const efectivo = {{ (float) ($chartMetodos['Efectivo'] ?? 0) }};
        const transferencia = {{ (float) ($chartMetodos['Transferencia'] ?? 0) }};
        const total = efectivo + transferencia;

        new Chart(ctxMetodos, {
            type: 'doughnut',
            data: {
                labels: ['Efectivo', 'Transferencia'],
                datasets: [{
                    data: total > 0 ? [efectivo, transferencia] : [1, 0],
                    backgroundColor: total > 0 ? ['#10b981', '#3b82f6'] : ['#e2e8f0', '#cbd5e1'],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Outfit', size: 12 },
                        bodyFont: { family: 'Outfit', size: 12 },
                        padding: 10,
                        cornerRadius: 12,
                        callbacks: {
                            label: function(c) {
                                if (total === 0) return ' Sin ventas';
                                const val = Number(c.raw);
                                const pct = ((val / total) * 100).toFixed(1);
                                return ` ${c.label}: $${val.toLocaleString()} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ── 3. GRÁFICA PRENDAS POR CATEGORÍA ────────────────────────────────────────
    const ctxCategorias = document.getElementById('chartCategorias');
    if (ctxCategorias) {
        const labelsCat = {!! json_encode($chartCategoriasLabels) !!};
        const dataCat = {!! json_encode($chartCategoriasStock) !!};

        new Chart(ctxCategorias, {
            type: 'bar',
            data: {
                labels: labelsCat,
                datasets: [{
                    label: 'Prendas',
                    data: dataCat,
                    backgroundColor: '#6366f1',
                    hoverBackgroundColor: '#4f46e5',
                    borderRadius: 8,
                    maxBarThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Outfit', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Outfit', size: 12 },
                        padding: 10,
                        cornerRadius: 12,
                        callbacks: {
                            label: function(c) {
                                return ` Stock disponible: ${c.raw} unidades`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { family: 'Outfit', size: 11 } }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            color: '#64748b',
                            font: { family: 'Outfit', size: 11 },
                            stepSize: 10
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush