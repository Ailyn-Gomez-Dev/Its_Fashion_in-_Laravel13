@extends('layouts.sidebaradmin')

@section('titulo', 'Productos')

@push('estilos')
<style>
    /* Efecto hover en las filas de la tabla */
    .trow:hover {
        background: #f8fafc;
    }

    /* Estilo para etiquetas/insignias */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Tarjetas de estadísticas con efecto de elevación */
    .stat-card {
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        background: white;
    }

    /* Contenedor del Modal */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, .5);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }

    /* Caja del Modal */
    .modal-box {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 25px 60px rgba(0, 0, 0, .15);
        width: 100%;
        max-width: 520px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
    }

    /* Animación del modal */
    @keyframes scaleUp {
        from {
            opacity: 0;
            transform: scale(.97)
        }

        to {
            opacity: 1;
            transform: scale(1)
        }
    }

    .scale-up {
        animation: scaleUp .3s ease-out;
    }

    /* Gradiente acentuado */
    .gradient-accent {
        background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
    }

    /* Switch tipo Toggle */
    .toggle {
        width: 44px;
        height: 24px;
        border-radius: 12px;
        position: relative;
        cursor: pointer;
        transition: background .2s;
        display: inline-block;
        flex-shrink: 0;
    }

    .toggle::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: white;
        transition: transform .2s;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
    }

    .toggle.on {
        background: #2563eb;
    }

    .toggle.off {
        background: #cbd5e1;
    }

    .toggle.on::after {
        transform: translateX(20px);
    }
</style>
@endpush

@section('content')
<div class="p-6 font-sans">

    <!-- Encabezado de página -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-brand-accent shadow-sm border border-blue-100">
                <i class="fas fa-box-open text-lg"></i>
            </div>
            <h1 class="text-xl md:text-2xl font-serif font-bold text-brand-dark">Gestión de Productos</h1>
        </div>
        @if (auth()->user()->rol_id === 1)
        <button onclick="openModal('create')"
            class="flex items-center gap-2 px-4 py-2.5 bg-brand-accent text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:-translate-y-0.5 transition-all"
            style="box-shadow: 0 4px 12px rgba(59,130,246,.25);">
            <i class="fas fa-plus text-xs"></i> <span>Nuevo producto</span>
        </button>
        @endif
    </div>



    <!-- KPIs de Productos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-blue-100 stat-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 gradient-accent rounded-xl flex items-center justify-center" style="box-shadow:0 4px 12px rgba(59,130,246,.3);">
                    <i class="fas fa-box-open text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $kpi['total'] }}</p>
                    <p class="text-xs text-slate-500">Total productos</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-emerald-100 stat-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-emerald-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $kpi['activos'] }}</p>
                    <p class="text-xs text-slate-500">Activos</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-amber-100 stat-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-amber-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $kpi['criticos'] }}</p>
                    <p class="text-xs text-slate-500">Stock crítico</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-red-100 stat-card">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-red-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-times-circle text-white"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-800">{{ $kpi['agotados'] }}</p>
                    <p class="text-xs text-slate-500">Agotados</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Catálogo de productos -->
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow:0 2px 16px rgba(0,0,0,.04);">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 flex-wrap gap-3">
            <div>
                <h3 class="font-bold text-slate-800">Catálogo de productos</h3>
                <p class="text-xs text-slate-500 mt-0.5">Prendas registradas en el sistema</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <!-- Buscador local -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                        <i class="fas fa-search text-sm"></i>
                    </span>
                    <input type="text" id="search-input" placeholder="Nombre, talla, color..." oninput="filterTable()"
                        class="pl-10 pr-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none transition w-64">
                </div>
                <!-- Filtro Categoría -->
                <select id="filter-cat" onchange="filterTable()"
                    class="py-2.5 px-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 cursor-pointer focus:outline-none transition">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                    @endforeach
                </select>
                <!-- Filtro Estado Stock -->
                <select id="filter-estado" onchange="filterTable()"
                    class="py-2.5 px-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 cursor-pointer focus:outline-none transition">
                    <option value="">Todos los estados</option>
                    <option value="Activo">Activos</option>
                    <option value="Inactivo">Inactivos</option>
                    <option value="critico">Stock crítico</option>
                    <option value="agotado">Agotados</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead>
                    <tr>
                        @foreach (['Producto', 'Categoría', 'Talla / Color', 'Precio Venta', 'Stock', 'Estado', ''] as $h)
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide bg-slate-50 border-b border-slate-100">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="table-body">
                    @forelse ($productos as $p)
                    @php
                    $activo = $p->estado === 'Activo';
                    $critico = $p->stock > 0 && $p->stock <= $p->stock_minimo;
                        $agotado = $p->stock == 0;
                        $stockStatus = $agotado ? 'agotado' : ($critico ? 'critico' : 'ok');
                        $esAdmin = auth()->user()->rol_id === 1;
                        @endphp
                        <tr class="trow"
                            id="row-{{ $p->id }}"
                            data-search="{{ strtolower($p->nombre . ' ' . $p->talla . ' ' . $p->color . ' ' . ($p->categoria->nombre ?? '')) }}"
                            data-cat="{{ $p->categoria_id }}"
                            data-estado="{{ $p->estado }}"
                            data-stock="{{ $stockStatus }}">

                            <td class="px-6 py-3.5 border-b border-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 flex-shrink-0 shadow-sm cursor-pointer hover:scale-105 transition"
                                        onclick="verDetalleProducto({{ $p->id }})" title="Ver detalle">
                                        <img src="{{ $p->imagen_url }}" alt="{{ $p->nombre }}" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=600&q=80'">
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 text-sm hover:text-blue-600 cursor-pointer transition" onclick="verDetalleProducto({{ $p->id }})">{{ $p->nombre }}</p>
                                        <p class="text-xs text-slate-400 truncate max-w-xs">{{ $p->descripcion ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-50">
                                <span class="badge bg-blue-100 text-blue-700">{{ $p->categoria->nombre ?? '—' }}</span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-50 text-sm text-slate-700">
                                <span class="font-medium">{{ $p->talla }}</span>
                                <span class="text-slate-400 mx-1">/</span>
                                {{ $p->color }}
                            </td>
                            <td class="px-6 py-4 border-b border-slate-50 text-sm font-semibold text-slate-800">
                                ${{ number_format($p->precio_venta, 2) }}
                            </td>
                            <td class="px-6 py-4 border-b border-slate-50">
                                @if ($agotado)
                                <span class="badge bg-red-100 text-red-700">
                                    <i class="fas fa-times-circle mr-1 text-xs"></i>Agotado
                                </span>
                                @elseif ($critico)
                                <span class="badge bg-amber-100 text-amber-700">
                                    <i class="fas fa-exclamation-triangle mr-1 text-xs"></i>{{ $p->stock }} uds
                                </span>
                                @else
                                <span class="badge bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-check mr-1 text-xs"></i>{{ $p->stock }} uds
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 border-b border-slate-50" onclick="event.stopPropagation()">
                                @if ($esAdmin)
                                <div class="flex items-center gap-2 cursor-pointer" onclick="toggleEstado(this, {{ $p->id }})">
                                    <div class="toggle {{ $activo ? 'on' : 'off' }}"></div>
                                    <span class="text-xs text-slate-600 estado-label">{{ $p->estado }}</span>
                                </div>
                                @else
                                <span class="badge {{ $activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $p->estado }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 border-b border-slate-50" onclick="event.stopPropagation()">
                                <div class="flex items-center gap-1.5">
                                    <button onclick="verDetalleProducto({{ $p->id }})"
                                        class="w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg flex items-center justify-center transition"
                                        title="Ver detalle del producto">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>
                                    @if ($esAdmin)
                                    <button onclick="editarProducto({{ $p->id }})"
                                        class="w-8 h-8 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center transition"
                                        title="Editar producto">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                                <i class="fas fa-box-open text-4xl mb-3 block opacity-20"></i>
                                <p class="text-sm">No hay productos registrados aún</p>
                            </td>
                        </tr>
                        @endforelse
                </tbody>
            </table>
            <!-- Paginador de Productos -->
            <div id="products-pagination" class="px-6 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2 bg-slate-50/50">
                <span class="text-xs text-slate-500 font-medium" id="products-page-info">Mostrando registros 1-10</span>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="prevProductsPage()" id="btn-products-prev" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 text-xs font-semibold hover:bg-slate-50 disabled:opacity-50 transition">Anterior</button>
                    <button type="button" onclick="nextProductsPage()" id="btn-products-next" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-600 text-xs font-semibold hover:bg-slate-50 disabled:opacity-50 transition">Siguiente</button>
                </div>
            </div>
        </div>
        <div id="empty-state" class="hidden py-16 text-center text-slate-400">
            <i class="fas fa-search text-4xl mb-3 block opacity-30"></i>
            <p class="text-sm font-medium">No se encontraron productos con ese criterio</p>
        </div>
    </div>
</div>

<!-- MODAL CREAR / EDITAR -->
@if (auth()->user()->rol_id === 1)
<div id="modal-prod" class="modal-overlay hidden">
    <div class="modal-box scale-up">
        <div class="flex items-center justify-between p-7 pb-5 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 gradient-accent rounded-xl flex items-center justify-center">
                    <i class="fas fa-box-open text-white text-sm"></i>
                </div>
                <h3 id="modal-title" class="text-lg font-bold text-slate-800">Nuevo producto</h3>
            </div>
            <button onclick="closeModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="form-prod" method="POST" action="" enctype="multipart/form-data" class="p-7 overflow-y-auto flex-1 space-y-4">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="">
            <input type="hidden" name="producto_id" id="prod-id" value="{{ old('producto_id') }}">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Nombre del producto *</label>
                    <input type="text" name="nombre" id="prod-nombre" required placeholder="Ej: Blusa floral manga larga"
                        value="{{ old('nombre') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('nombre') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                    @error('nombre')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-2">
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Descripción</label>
                    <textarea name="descripcion" id="prod-desc" rows="2" placeholder="Descripción opcional de la prenda..."
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 resize-none focus:outline-none transition">{{ old('descripcion') }}</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Categoría *</label>
                    <select name="categoria_id" id="prod-cat" required class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('categoria_id') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                        <option value="">Seleccionar...</option>
                        @foreach ($categorias as $c)
                        <option value="{{ $c->id }}" {{ old('categoria_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Estado</label>
                    <select name="estado" id="prod-estado" class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none transition">
                        <option value="Activo" {{ old('estado') == 'Activo' ? 'selected' : '' }}>Activo</option>
                        <option value="Inactivo" {{ old('estado') == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Talla *</label>
                    <input type="text" name="talla" id="prod-talla" required
                        placeholder="Ej: S, M, L, XL, 38, 4T, 2 años…"
                        value="{{ old('talla') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('talla') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                    <p class="text-[10px] text-slate-400 mt-1">Adultos, infantil o numérica.</p>
                    @error('talla')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Color *</label>
                    <input type="text" name="color" id="prod-color" required placeholder="Ej: Azul marino"
                        value="{{ old('color') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('color') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                    @error('color')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Precio Compra *</label>
                    <input type="number" name="precio_compra" id="prod-pc" required min="0" step="0.01" placeholder="0.00"
                        value="{{ old('precio_compra') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('precio_compra') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                    @error('precio_compra')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Precio Venta *</label>
                    <input type="number" name="precio_venta" id="prod-pv" required min="0" step="0.01" placeholder="0.00"
                        value="{{ old('precio_venta') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('precio_venta') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                    @error('precio_venta')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Stock Inicial *</label>
                    <input type="number" name="stock" id="prod-stock" required min="0" placeholder="0"
                        value="{{ old('stock') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 @error('stock') border-red-500 @else border-slate-200 @enderror rounded-xl text-sm text-slate-700 focus:outline-none transition">
                    @error('stock')
                        <p class="text-xs text-red-500 mt-1"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">Stock Mínimo</label>
                    <input type="number" name="stock_minimo" id="prod-smin" min="0" placeholder="0"
                        value="{{ old('stock_minimo') }}"
                        class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none transition">
                </div>

                <!-- SUBIDA DE FOTOGRAFÍA (DESDE PC O ENLACE) -->
                <div class="col-span-2 space-y-2 pt-1 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-700 flex items-center gap-2">
                            <i class="fas fa-camera text-brand-accent"></i> Fotografía de la prenda
                        </label>
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" onclick="switchImageMode('file')" id="btn-mode-file" class="font-bold text-blue-600">
                                <i class="fas fa-desktop mr-1"></i>Desde mi PC
                            </button>
                            <span class="text-slate-300">|</span>
                            <button type="button" onclick="switchImageMode('url')" id="btn-mode-url" class="text-slate-400 hover:text-slate-600">
                                <i class="fas fa-link mr-1"></i>Enlace Web
                            </button>
                        </div>
                    </div>

                    <!-- 1. Input para subir archivo desde el PC -->
                    <div id="image-file-container">
                        <input type="file" name="imagen_file" id="prod-imagen-file" accept="image/*" onchange="handleFileSelect(this)" class="hidden">
                        <div onclick="document.getElementById('prod-imagen-file').click()" 
                            class="group relative border-2 border-dashed border-slate-200 hover:border-blue-400 bg-slate-50/70 hover:bg-blue-50/40 rounded-2xl p-4 text-center cursor-pointer transition flex items-center justify-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden flex items-center justify-center flex-shrink-0" id="file-preview-box">
                                <img id="prod-file-preview-img" src="" class="w-full h-full object-cover hidden">
                                <i id="file-icon-placeholder" class="fas fa-cloud-upload-alt text-2xl text-blue-500 group-hover:scale-110 transition"></i>
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-bold text-slate-700" id="file-upload-title">Haz clic para buscar imagen en tu equipo</p>
                                <p class="text-[11px] text-slate-400" id="file-upload-subtitle">Formatos compatibles: JPG, PNG, WEBP o AVIF</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Input para pegar URL web -->
                    <div id="image-url-container" class="hidden">
                        <div class="flex items-center gap-3">
                            <input type="text" name="imagen" id="prod-imagen" placeholder="https://images.unsplash.com/... o data:image/..."
                                oninput="previewProdImage(this.value)"
                                class="flex-1 px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none transition">
                            <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0 shadow-sm">
                                <img id="prod-image-preview" src="" class="w-full h-full object-cover hidden">
                                <i id="prod-image-placeholder" class="fas fa-image text-slate-400 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="button" onclick="closeModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">Cancelar</button>
                <button type="submit" class="flex-1 py-3 gradient-accent text-white font-semibold rounded-xl transition text-sm hover:shadow-lg" style="box-shadow:0 4px 12px rgba(59,130,246,.25);">
                    <i class="fas fa-save mr-2 text-xs"></i><span id="btn-submit-text">Guardar producto</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- MODAL VER DETALLE DE PRODUCTO (DISEÑO BOUTIQUE PREMIUM) -->
<div id="modal-detalle-prod" class="modal-overlay hidden">
    <div class="modal-box scale-up max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden p-0">
        
        <!-- Cabecera de la ficha -->
        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-100/80 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="fas fa-sparkles text-xs"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Ficha de Producto</h3>
                    <p class="text-xs text-slate-400" id="detalle-sku">Catálogo It's Fashion</p>
                </div>
            </div>
            <button onclick="closeDetalleModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition hover:scale-105">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- Contenido en Dos Columnas -->
        <div class="p-7 overflow-y-auto max-h-[82vh]">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                
                <!-- Columna Izquierda: Fotografía de Estudio -->
                <div class="md:col-span-5 space-y-3">
                    <div class="relative w-full h-80 sm:h-96 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200/80 shadow-md group">
                        <img id="detalle-img" src="" alt="Prenda de vestir" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        
                        <!-- Badges Flotantes sobre la Foto -->
                        <div class="absolute top-3 left-3" id="detalle-badge-estado"></div>
                        <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between gap-2">
                            <span id="detalle-categoria" class="px-3 py-1.5 rounded-xl bg-black/60 backdrop-blur-md text-white text-xs font-semibold shadow-sm flex items-center gap-1.5">
                                <i class="fas fa-tag text-[10px] text-blue-300"></i> <span id="detalle-categoria-text"></span>
                            </span>
                            <span id="detalle-badge-stock" class="px-3 py-1.5 rounded-xl bg-white/90 backdrop-blur-md text-slate-800 text-xs font-bold shadow-sm"></span>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Especificaciones, Rentabilidad y Kardex -->
                <div class="md:col-span-7 space-y-6">
                    
                    <!-- Título y Descripción -->
                    <div>
                        <h2 id="detalle-nombre" class="text-2xl font-bold font-serif text-slate-900 tracking-tight leading-snug"></h2>
                        <p id="detalle-desc" class="text-sm text-slate-500 mt-2 leading-relaxed"></p>
                    </div>

                    <!-- Variantes: Talla y Color -->
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="flex items-center gap-2 px-3.5 py-2 bg-slate-50 border border-slate-200/80 rounded-xl">
                            <span class="text-xs text-slate-400 font-semibold uppercase">Talla:</span>
                            <span id="detalle-talla" class="text-xs font-bold text-slate-800 tracking-wide px-2 py-0.5 bg-white rounded-md border border-slate-200"></span>
                        </div>
                        <div class="flex items-center gap-2 px-3.5 py-2 bg-slate-50 border border-slate-200/80 rounded-xl">
                            <span class="text-xs text-slate-400 font-semibold uppercase">Color:</span>
                            <span id="detalle-color" class="text-xs font-bold text-slate-800"></span>
                        </div>
                    </div>

                    <!-- Tarjeta de Rentabilidad y Precios -->
                    <div class="p-5 rounded-2xl bg-gradient-to-br from-blue-50/70 via-indigo-50/40 to-slate-50 border border-blue-100/80 shadow-xs space-y-4">
                        <div class="flex items-baseline justify-between">
                            <div>
                                <span class="text-xs font-semibold text-slate-500 block uppercase tracking-wider">Precio de Venta</span>
                                <span id="detalle-pv" class="text-3xl font-black text-slate-900"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Precio de Compra</span>
                                <span id="detalle-pc" class="text-base font-bold text-slate-600"></span>
                            </div>
                        </div>
                        
                        <div class="pt-3 border-t border-blue-100/60 flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-medium">Margen estimado por prenda:</span>
                            <span id="detalle-margen" class="font-bold text-emerald-700 bg-emerald-100/80 px-2.5 py-1 rounded-lg"></span>
                        </div>
                    </div>

                    <!-- Nivel de Inventario y Barra de Stock -->
                    <div class="space-y-2 bg-slate-50/80 p-4 rounded-2xl border border-slate-100">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-600 flex items-center gap-1.5">
                                <i class="fas fa-boxes text-slate-400"></i> Existencias en almacén
                            </span>
                            <span id="detalle-stock-info" class="text-slate-800 font-bold"></span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden">
                            <div id="detalle-stock-bar" class="h-full rounded-full transition-all duration-500 bg-blue-600" style="width: 50%;"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                            <span>Mínimo requerido: <strong id="detalle-smin" class="text-slate-600"></strong></span>
                            <span id="detalle-stock-msg" class="font-medium"></span>
                        </div>
                    </div>

                    <!-- Acciones del pie -->
                    <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-100">
                        @if (auth()->user()->rol_id === 1)
                        <button type="button" id="btn-editar-desde-detalle" onclick=""
                            class="flex items-center gap-2 px-5 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold rounded-xl text-xs transition">
                            <i class="fas fa-edit"></i> Editar esta prenda
                        </button>
                        @else
                        <div></div>
                        @endif
                        <button type="button" onclick="closeDetalleModal()"
                            class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl text-xs transition shadow-sm">
                            Cerrar
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- SweetAlert2 para mensajes de alerta -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const STORE_URL = "{{ route('productos.store') }}";
    const BASE_URL = "{{ url('/productos') }}";

    let currentProductsPage = 1;
    const productsRecordsPerPage = 10;

    document.addEventListener('DOMContentLoaded', () => {
        applyProductsPagination();
    });

    function applyProductsPagination() {
        const rows = Array.from(document.querySelectorAll('#table-body tr')).filter(row => row.id && row.id.startsWith('row-') && row.style.display !== 'none');
        const infoSpan = document.getElementById('products-page-info');
        const prevBtn = document.getElementById('btn-products-prev');
        const nextBtn = document.getElementById('btn-products-next');

        const total = rows.length;
        const totalPages = Math.ceil(total / productsRecordsPerPage) || 1;

        if (currentProductsPage > totalPages) {
            currentProductsPage = totalPages;
        }

        const startIdx = (currentProductsPage - 1) * productsRecordsPerPage;
        const endIdx = startIdx + productsRecordsPerPage;

        const allRows = document.querySelectorAll('#table-body tr');
        let matchedIdx = 0;
        allRows.forEach(row => {
            if (row.id && row.id.startsWith('row-')) {
                if (row.getAttribute('data-filtered') === 'true') {
                    row.style.display = 'none';
                } else {
                    if (matchedIdx >= startIdx && matchedIdx < endIdx) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                    matchedIdx++;
                }
            }
        });

        if (total === 0) {
            infoSpan.textContent = "Mostrando registros 0 de 0";
            prevBtn.disabled = true;
            nextBtn.disabled = true;
        } else {
            infoSpan.textContent = `Mostrando registros ${startIdx + 1}-${Math.min(endIdx, total)} de ${total}`;
            prevBtn.disabled = currentProductsPage === 1;
            nextBtn.disabled = currentProductsPage === totalPages;
        }
    }

    function prevProductsPage() {
        if (currentProductsPage > 1) {
            currentProductsPage--;
            applyProductsPagination();
        }
    }

    function nextProductsPage() {
        currentProductsPage++;
        applyProductsPagination();
    }

    // ── Filtros en cliente ────────────────────────────────────────────────────────
    function filterTable() {
        const q = document.getElementById('search-input').value.toLowerCase();
        const cat = document.getElementById('filter-cat').value;
        const estado = document.getElementById('filter-estado').value;
        let visible = 0;

        document.querySelectorAll('#table-body tr[id^="row-"]').forEach(row => {
            const matchQ = !q || row.dataset.search.includes(q);
            const matchC = !cat || row.dataset.cat === cat;

            let matchE = true;
            if (estado === 'Activo' || estado === 'Inactivo') {
                matchE = row.dataset.estado === estado;
            } else if (estado === 'critico') {
                matchE = row.dataset.stock === 'critico';
            } else if (estado === 'agotado') {
                matchE = row.dataset.stock === 'agotado';
            }

            const show = matchQ && matchC && matchE;
            if (show) {
                row.removeAttribute('data-filtered');
                visible++;
            } else {
                row.setAttribute('data-filtered', 'true');
            }
        });

        document.getElementById('empty-state').classList.toggle('hidden', visible > 0);

        currentProductsPage = 1;
        applyProductsPagination();
    }

    // ── Alternar estado vía AJAX ───────────────────────────────────────────────────
    function toggleEstado(el, id) {
        const toggle = el.querySelector('.toggle');
        const label = el.querySelector('.estado-label');
        const isOn = toggle.classList.contains('on');
        const nuevo = isOn ? 'Inactivo' : 'Activo';

        fetch(`${BASE_URL}/${id}/toggle-estado`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    estado: nuevo
                })
            })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    toggle.classList.toggle('on', !isOn);
                    toggle.classList.toggle('off', isOn);
                    label.textContent = nuevo;
                    const row = document.getElementById('row-' + id);
                    if (row) row.dataset.estado = nuevo;
                    showToast('Estado actualizado', 'success');
                } else {
                    showToast(d.msg || 'Error al cambiar estado', 'error');
                }
            });
    }

    // ── Manejo de Imágenes (Subida desde PC vs Enlace Web) ───────────────────────
    function switchImageMode(mode) {
        const fileContainer = document.getElementById('image-file-container');
        const urlContainer = document.getElementById('image-url-container');
        const btnFile = document.getElementById('btn-mode-file');
        const btnUrl = document.getElementById('btn-mode-url');

        if (mode === 'file') {
            if (fileContainer) fileContainer.classList.remove('hidden');
            if (urlContainer) urlContainer.classList.add('hidden');
            if (btnFile) {
                btnFile.className = 'font-bold text-blue-600';
            }
            if (btnUrl) {
                btnUrl.className = 'text-slate-400 hover:text-slate-600';
            }
        } else {
            if (fileContainer) fileContainer.classList.add('hidden');
            if (urlContainer) urlContainer.classList.remove('hidden');
            if (btnFile) {
                btnFile.className = 'text-slate-400 hover:text-slate-600';
            }
            if (btnUrl) {
                btnUrl.className = 'font-bold text-blue-600';
            }
        }
    }

    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('prod-file-preview-img');
                const icon = document.getElementById('file-icon-placeholder');
                const title = document.getElementById('file-upload-title');
                const sub = document.getElementById('file-upload-subtitle');

                if (img) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
                if (icon) icon.classList.add('hidden');
                if (title) title.textContent = file.name;
                if (sub) sub.textContent = `${(file.size / 1024).toFixed(1)} KB — Listo para guardar`;
            };
            reader.readAsDataURL(file);
        }
    }

    function previewProdImage(url) {
        const img = document.getElementById('prod-image-preview');
        const icon = document.getElementById('prod-image-placeholder');
        if (url && url.trim().length > 5) {
            img.src = url;
            img.classList.remove('hidden');
            icon.classList.add('hidden');
            img.onerror = () => {
                img.classList.add('hidden');
                icon.classList.remove('hidden');
            };
        } else {
            img.classList.add('hidden');
            icon.classList.remove('hidden');
        }
    }

    // ── Ver Ficha de Producto (Modal Boutique de Alta Gama) ───────────────────────
    function verDetalleProducto(id) {
        fetch(`${BASE_URL}/${id}`)
            .then(r => r.json())
            .then(p => {
                if (!p) return;
                const modal = document.getElementById('modal-detalle-prod');

                // SKU y Títulos
                const skuEl = document.getElementById('detalle-sku');
                if (skuEl) skuEl.textContent = `Código SKU: #PRD-${String(p.id).padStart(4, '0')}`;
                
                document.getElementById('detalle-nombre').textContent = p.nombre;
                document.getElementById('detalle-desc').textContent = p.descripcion || 'Sin descripción adicional disponible.';
                
                // Categoría
                const catText = p.categoria ? p.categoria.nombre : 'Colección General';
                const catSpan = document.getElementById('detalle-categoria-text');
                if (catSpan) catSpan.textContent = catText;
                
                // Variantes Talla y Color
                document.getElementById('detalle-talla').textContent = p.talla || 'Única';
                document.getElementById('detalle-color').textContent = p.color || 'Estándar';

                // Precios y Rentabilidad
                const pv = parseFloat(p.precio_venta) || 0;
                const pc = parseFloat(p.precio_compra) || 0;
                const ganancia = pv - pc;
                const margenPct = pv > 0 ? ((ganancia / pv) * 100).toFixed(1) : 0;

                document.getElementById('detalle-pv').textContent = `$${pv.toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
                document.getElementById('detalle-pc').textContent = `$${pc.toLocaleString('es-CO', { minimumFractionDigits: 2 })}`;
                
                const margenEl = document.getElementById('detalle-margen');
                if (margenEl) {
                    margenEl.textContent = `+$${ganancia.toLocaleString('es-CO', { minimumFractionDigits: 2 })} (${margenPct}% margen)`;
                }

                // Control de Inventario & Barra de Progreso
                const stock = parseInt(p.stock) || 0;
                const smin = parseInt(p.stock_minimo) || 0;
                document.getElementById('detalle-stock-info').textContent = `${stock} unidades disponibles`;
                document.getElementById('detalle-smin').textContent = `${smin} uds`;

                const stockBar = document.getElementById('detalle-stock-bar');
                const stockMsg = document.getElementById('detalle-stock-msg');
                const stockBadge = document.getElementById('detalle-badge-stock');

                let pct = smin > 0 ? Math.min(100, Math.round((stock / (smin * 2.5)) * 100)) : 100;
                if (stock <= 0) pct = 0;

                if (stockBar) {
                    stockBar.style.width = `${Math.max(6, pct)}%`;
                    if (stock <= 0) {
                        stockBar.className = 'h-full rounded-full transition-all duration-500 bg-rose-500';
                        if (stockMsg) { stockMsg.className = 'font-bold text-rose-600'; stockMsg.textContent = 'Agotado'; }
                        if (stockBadge) stockBadge.innerHTML = '<span class="text-rose-600 font-bold"><i class="fas fa-exclamation-triangle mr-1"></i>Agotado</span>';
                    } else if (stock <= smin) {
                        stockBar.className = 'h-full rounded-full transition-all duration-500 bg-amber-500';
                        if (stockMsg) { stockMsg.className = 'font-bold text-amber-600'; stockMsg.textContent = 'Stock bajo (reponer)'; }
                        if (stockBadge) stockBadge.innerHTML = `<span class="text-amber-600 font-bold"><i class="fas fa-exclamation-circle mr-1"></i>${stock} uds</span>`;
                    } else {
                        stockBar.className = 'h-full rounded-full transition-all duration-500 bg-emerald-500';
                        if (stockMsg) { stockMsg.className = 'font-bold text-emerald-600'; stockMsg.textContent = 'Inventario saludable'; }
                        if (stockBadge) stockBadge.innerHTML = `<span class="text-emerald-700 font-bold"><i class="fas fa-box-check mr-1"></i>${stock} uds</span>`;
                    }
                }

                // Fotografía de Estudio
                const img = document.getElementById('detalle-img');
                const defaultImg = 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=600&q=80';
                img.src = p.imagen_url || p.imagen || defaultImg;
                img.onerror = () => { img.src = defaultImg; };

                // Badge de Estado
                const badgeEstado = document.getElementById('detalle-badge-estado');
                if (p.estado === 'Activo') {
                    badgeEstado.innerHTML = '<span class="px-3 py-1 rounded-xl bg-emerald-500/90 backdrop-blur-md text-white text-xs font-bold shadow-sm flex items-center gap-1.5"><i class="fas fa-check-circle text-xs"></i> Activo en Catálogo</span>';
                } else {
                    badgeEstado.innerHTML = '<span class="px-3 py-1 rounded-xl bg-slate-600/90 backdrop-blur-md text-white text-xs font-bold shadow-sm flex items-center gap-1.5"><i class="fas fa-eye-slash text-xs"></i> Inactivo</span>';
                }

                // Botón Editar desde el detalle
                const btnEdit = document.getElementById('btn-editar-desde-detalle');
                if (btnEdit) {
                    btnEdit.setAttribute('onclick', `closeDetalleModal(); editarProducto(${p.id});`);
                }

                modal.classList.remove('hidden');
            });
    }

    function closeDetalleModal() {
        document.getElementById('modal-detalle-prod').classList.add('hidden');
    }

    // ── Modal Apertura / Cierre ──────────────────────────────────────────────────
    @if(auth()->user()->rol_id === 1)

    function openModal(mode, data = null) {
        const modal = document.getElementById('modal-prod');
        const form = document.getElementById('form-prod');
        const methodInput = document.getElementById('form-method');
        const prodIdInput = document.getElementById('prod-id');
        const title = document.getElementById('modal-title');
        const btnText = document.getElementById('btn-submit-text');

        form.reset();
        previewProdImage('');
        document.querySelectorAll('#form-prod input, #form-prod select, #form-prod textarea').forEach(el => {
            el.classList.remove('border-red-500');
            el.classList.add('border-slate-200');
        });

        // Resetear preview de archivo local
        const fileInput = document.getElementById('prod-imagen-file');
        if (fileInput) fileInput.value = '';
        const fileImg = document.getElementById('prod-file-preview-img');
        const fileIcon = document.getElementById('file-icon-placeholder');
        if (fileImg) { fileImg.src = ''; fileImg.classList.add('hidden'); }
        if (fileIcon) fileIcon.classList.remove('hidden');
        const fileTitle = document.getElementById('file-upload-title');
        const fileSub = document.getElementById('file-upload-subtitle');
        if (fileTitle) fileTitle.textContent = 'Haz clic para buscar imagen en tu equipo';
        if (fileSub) fileSub.textContent = 'Formatos compatibles: JPG, PNG, WEBP o AVIF';

        switchImageMode('file');

        if (mode === 'create') {
            form.action = STORE_URL;
            methodInput.value = 'POST';
            if (prodIdInput) prodIdInput.value = '';
            title.textContent = 'Nuevo producto';
            btnText.textContent = 'Guardar producto';
            modal.classList.remove('hidden');
            setTimeout(() => document.getElementById('prod-nombre').focus(), 50);
        }
    }

    function closeModal() {
        document.getElementById('modal-prod').classList.add('hidden');
    }

    // Editar producto vía consulta AJAX (para poblar los campos del modal)
    function editarProducto(id) {
        const modal = document.getElementById('modal-prod');
        const form = document.getElementById('form-prod');
        const methodInput = document.getElementById('form-method');
        const prodIdInput = document.getElementById('prod-id');
        const title = document.getElementById('modal-title');
        const btnText = document.getElementById('btn-submit-text');

        fetch(`${BASE_URL}/${id}`)
            .then(r => r.json())
            .then(p => {
                if (!p) return;

                form.action = `${BASE_URL}/${id}`;
                methodInput.value = 'PUT';
                if (prodIdInput) prodIdInput.value = id;

                document.getElementById('prod-nombre').value = p.nombre;
                document.getElementById('prod-desc').value = p.descripcion || '';
                document.getElementById('prod-cat').value = p.categoria_id;
                document.getElementById('prod-estado').value = p.estado;
                document.getElementById('prod-talla').value = p.talla || '';
                document.getElementById('prod-color').value = p.color || '';
                document.getElementById('prod-pc').value = p.precio_compra;
                document.getElementById('prod-pv').value = p.precio_venta;
                document.getElementById('prod-stock').value = p.stock;
                document.getElementById('prod-smin').value = p.stock_minimo;
                
                // Manejo de la imagen existente (PC o URL)
                const fileInput = document.getElementById('prod-imagen-file');
                if (fileInput) fileInput.value = '';
                const fileImg = document.getElementById('prod-file-preview-img');
                const fileIcon = document.getElementById('file-icon-placeholder');
                const fileTitle = document.getElementById('file-upload-title');
                const fileSub = document.getElementById('file-upload-subtitle');

                const currentImg = p.imagen_url || p.imagen || '';

                if (p.imagen && (p.imagen.startsWith('http://') || p.imagen.startsWith('https://') || p.imagen.startsWith('data:image/'))) {
                    document.getElementById('prod-imagen').value = p.imagen;
                    previewProdImage(p.imagen);
                    switchImageMode('url');
                } else if (currentImg) {
                    if (fileImg) {
                        fileImg.src = currentImg;
                        fileImg.classList.remove('hidden');
                    }
                    if (fileIcon) fileIcon.classList.add('hidden');
                    if (fileTitle) fileTitle.textContent = 'Fotografía actual cargada';
                    if (fileSub) fileSub.textContent = 'Haz clic si deseas reemplazarla por otra de tu equipo';
                    document.getElementById('prod-imagen').value = '';
                    switchImageMode('file');
                } else {
                    document.getElementById('prod-imagen').value = '';
                    previewProdImage('');
                    if (fileImg) { fileImg.src = ''; fileImg.classList.add('hidden'); }
                    if (fileIcon) fileIcon.classList.remove('hidden');
                    if (fileTitle) fileTitle.textContent = 'Haz clic para buscar imagen en tu equipo';
                    if (fileSub) fileSub.textContent = 'Formatos compatibles: JPG, PNG, WEBP o AVIF';
                    switchImageMode('file');
                }

                title.textContent = 'Editar producto';
                btnText.textContent = 'Actualizar producto';
                modal.classList.remove('hidden');
                setTimeout(() => document.getElementById('prod-nombre').focus(), 50);
            });
    }

    // Escuchar el evento Escape para cerrar modales
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal();
            closeDetalleModal();
        }
    });

    // Control de foco visual en inputs
    document.querySelectorAll('#form-prod input, #form-prod select, #form-prod textarea').forEach(el => {
        el.addEventListener('focus', () => {
            el.style.borderColor = '#3b82f6';
            el.style.boxShadow = '0 0 0 3px rgba(59,130,246,.15)';
        });
        el.addEventListener('blur', () => {
            el.style.boxShadow = '';
        });
    });
    @endif

    // Redefinido para usar SweetAlert2 en modo Toast
    function showToast(msg, type = 'success') {
        Swal.fire({
            icon: type,
            title: msg,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    // Mostrar Toast si existe sesión de redirección con "toast" de Laravel
    @if(session('toast'))
    document.addEventListener('DOMContentLoaded', () => {
        showToast(@json(session('toast')['text']), @json(session('toast')['type']));
    });
    @endif

    @if(session('error'))
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json(session('error')),
            confirmButtonColor: '#2563eb'
        });
    });
    @endif

    // Mostrar errores de validación con SweetAlert2 y reabrir modal con datos retenidos
    @if($errors->any())
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'error',
            title: 'No se pudo guardar el producto',
            html: @json(implode('<br>', array_map('htmlspecialchars', $errors->all()))),
            confirmButtonColor: '#2563eb',
            confirmButtonText: 'Corregir'
        });

        const modal = document.getElementById('modal-prod');
        const form = document.getElementById('form-prod');
        const methodInput = document.getElementById('form-method');
        const prodIdInput = document.getElementById('prod-id');
        const title = document.getElementById('modal-title');
        const btnText = document.getElementById('btn-submit-text');

        @if(old('_method') === 'PUT' && old('producto_id'))
            form.action = `${BASE_URL}/{{ old('producto_id') }}`;
            methodInput.value = 'PUT';
            if (prodIdInput) prodIdInput.value = '{{ old('producto_id') }}';
            title.textContent = 'Editar producto';
            btnText.textContent = 'Actualizar producto';
        @else
            form.action = STORE_URL;
            methodInput.value = 'POST';
            if (prodIdInput) prodIdInput.value = '';
            title.textContent = 'Nuevo producto';
            btnText.textContent = 'Guardar producto';
        @endif

        document.getElementById('prod-nombre').value = @json(old('nombre', ''));
        document.getElementById('prod-desc').value = @json(old('descripcion', ''));
        document.getElementById('prod-cat').value = @json(old('categoria_id', ''));
        document.getElementById('prod-estado').value = @json(old('estado', 'Activo'));
        document.getElementById('prod-talla').value = @json(old('talla', ''));
        document.getElementById('prod-color').value = @json(old('color', ''));
        document.getElementById('prod-pc').value = @json(old('precio_compra', ''));
        document.getElementById('prod-pv').value = @json(old('precio_venta', ''));
        document.getElementById('prod-stock').value = @json(old('stock', ''));
        document.getElementById('prod-smin').value = @json(old('stock_minimo', ''));

        modal.classList.remove('hidden');
    });
    @endif
</script>
@endpush