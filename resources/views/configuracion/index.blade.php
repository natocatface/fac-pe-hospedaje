@extends('layouts.app')
@section('title', 'Configuración del Sistema')
@section('page-title', 'Configuración')
@section('breadcrumb')
    <li class="breadcrumb-item active">Configuración</li>
@endsection

@push('styles')
<style>
    /* ── Pestañas (forzando contraste sobre AdminLTE) ────────────────── */
    .config-tab-nav .nav-link,
    .nav-tabs.config-tab-nav .nav-link               { color: #6c757d !important; font-weight: 500;
                                                       padding: .65rem 1.1rem; border-radius: 8px 8px 0 0;
                                                       border: 1px solid transparent !important; }
    .config-tab-nav .nav-link:hover,
    .nav-tabs.config-tab-nav .nav-link:hover         { color: #1a2035 !important; background:#f1f3f9;
                                                       border-color: transparent !important; }
    .config-tab-nav .nav-link.active,
    .nav-tabs.config-tab-nav .nav-link.active,
    .nav-tabs.config-tab-nav .nav-item.show .nav-link{ color: #fff !important;
                                                       background: linear-gradient(135deg, #1a2035 0%, #2d3a5f 100%) !important;
                                                       border-color: #1a2035 !important;
                                                       box-shadow: 0 -2px 8px rgba(26,32,53,.2); }
    .config-tab-nav .nav-link.active i,
    .nav-tabs.config-tab-nav .nav-link.active i      { color: #fff !important; }
    .config-tab-nav .nav-link i                      { width: 20px; text-align: center; }

    .tab-section-title               { font-size: .72rem; font-weight: 700; text-transform: uppercase;
                                       letter-spacing: .08em; color: #6c757d; margin: 1.5rem 0 .75rem;
                                       padding-bottom: .35rem; border-bottom: 2px solid #e9ecef; }
    .logo-drop-area                  { border: 2px dashed #ced4da; border-radius: 12px;
                                       padding: 2.5rem; text-align: center; cursor: pointer;
                                       transition: all .25s; background: #f8f9fa; }
    .logo-drop-area:hover,
    .logo-drop-area.drag-over        { border-color: #007bff; background: #e8f0fe; }
    .logo-preview                    { max-height: 120px; max-width: 300px; object-fit: contain; }
    .color-swatch                    { display: inline-block; width: 32px; height: 32px;
                                       border-radius: 6px; border: 2px solid #dee2e6;
                                       cursor: pointer; vertical-align: middle; }
    .field-desc                      { font-size: .75rem; color: #6c757d; margin-top: .2rem; }
    .save-bar                        { position: sticky; bottom: 0; background: #fff;
                                       padding: 1rem 0; border-top: 1px solid #dee2e6;
                                       margin-top: 1.5rem; z-index: 10; }

    /* ────────────────────────────────────────────────────────────────
       Diseño Premium — Sección SUNAT
    ──────────────────────────────────────────────────────────────── */
    .sunat-hero {
        background: linear-gradient(135deg, #e3000f 0%, #a0000a 100%);
        color: #fff;
        border-radius: 12px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(224,0,15,.15);
    }
    .sunat-hero::after {
        content: '';
        position: absolute;
        top: -30%;
        right: -10%;
        width: 240px;
        height: 240px;
        background: rgba(255,255,255,.06);
        border-radius: 50%;
    }
    .sunat-hero .hero-icon {
        font-size: 2.6rem;
        opacity: .9;
    }
    .sunat-hero h4 {
        font-weight: 700;
        letter-spacing: -.02em;
        margin-bottom: .25rem;
    }
    .sunat-hero p { margin-bottom: 0; opacity: .92; }

    .sunat-progress {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 6px rgba(0,0,0,.03);
    }
    .sunat-progress .progress-bar { background: linear-gradient(90deg,#28a745,#20c997); }
    .sunat-progress .step-pill {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .35rem .75rem; border-radius: 999px;
        font-size: .78rem; font-weight: 600;
        background: #f8f9fa; color: #6c757d;
        border: 1px solid #e9ecef;
    }
    .sunat-progress .step-pill.done    { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .sunat-progress .step-pill.missing { background: #fff3cd; color: #856404; border-color: #ffeeba; }

    .sunat-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: box-shadow .2s, transform .2s;
        position: relative;
    }
    .sunat-card:hover { box-shadow: 0 6px 16px rgba(0,0,0,.06); }
    .sunat-card .sc-head {
        display: flex; align-items: center; gap: .75rem;
        padding-bottom: .85rem; margin-bottom: 1rem;
        border-bottom: 2px dashed #f1f3f5;
    }
    .sunat-card .sc-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }
    .sunat-card .sc-icon.bg-cred   { background: linear-gradient(135deg,#fd7e14,#ff9800); }
    .sunat-card .sc-icon.bg-cert   { background: linear-gradient(135deg,#0d6efd,#3b82f6); }
    .sunat-card .sc-icon.bg-ubigeo { background: linear-gradient(135deg,#dc3545,#ef4444); }
    .sunat-card .sc-icon.bg-status { background: linear-gradient(135deg,#198754,#22c55e); }
    .sunat-card .sc-title { font-size: 1rem; font-weight: 700; color:#2c3e50; margin: 0; line-height: 1.2; }
    .sunat-card .sc-subtitle { font-size: .78rem; color:#6c757d; margin-top: .15rem; }
    .sunat-card .sc-badge {
        margin-left: auto;
        font-size: .72rem; font-weight: 600;
        padding: .25rem .6rem; border-radius: 999px;
        background: #e9ecef; color:#6c757d;
    }
    .sunat-card .sc-badge.ok      { background:#d4edda; color:#155724; }
    .sunat-card .sc-badge.warning { background:#fff3cd; color:#856404; }
    .sunat-card .sc-badge.danger  { background:#f8d7da; color:#721c24; }

    .ambiente-card {
        border-radius: 10px; padding: 1rem; cursor: pointer;
        border: 2px solid #e9ecef; transition: all .2s;
        height: 100%;
    }
    .ambiente-card:hover { transform: translateY(-2px); }
    .ambiente-card input[type="radio"] { display: none; }
    .ambiente-card.selected.beta { border-color: #0d6efd; background:#e7f1ff; }
    .ambiente-card.selected.prod { border-color: #dc3545; background:#fff5f5; }
    .ambiente-card .am-icon { font-size: 1.7rem; margin-bottom: .4rem; }
    .ambiente-card .am-title { font-weight: 700; font-size: .95rem; margin-bottom: .15rem; }
    .ambiente-card .am-desc  { font-size: .75rem; color: #6c757d; margin: 0; }

    .check-table { width: 100%; }
    .check-table tr { border-bottom: 1px solid #f1f3f5; }
    .check-table tr:last-child { border: none; }
    .check-table td { padding: .75rem .25rem; vertical-align: middle; }
    .check-table .ck-icon {
        width: 36px; height: 36px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-size: .85rem;
    }
    .check-table .ck-icon.ok   { background: #28a745; }
    .check-table .ck-icon.warn { background: #ffc107; color:#2c3e50; }
    .check-table .ck-icon.bad  { background: #dc3545; }
    .check-table .ck-label   { font-weight: 600; color:#2c3e50; }
    .check-table .ck-desc    { font-size: .78rem; color:#6c757d; }
    .check-table .ck-action  { font-size: .76rem; }

    .cert-uploader {
        border: 2px dashed #ced4da; border-radius: 12px;
        padding: 1.5rem; text-align: center; cursor: pointer;
        background: #fafbfc; transition: all .25s;
    }
    .cert-uploader:hover { border-color: #0d6efd; background:#e7f1ff; }
    .cert-uploader.has-file { border-color: #28a745; background:#f0fdf4; border-style: solid; }
    .cert-uploader .upl-icon { font-size: 2.2rem; color:#adb5bd; margin-bottom: .35rem; }
    .cert-uploader.has-file .upl-icon { color:#28a745; }
</style>
@endpush

@section('content')

<form action="{{ route('configuracion.update') }}" method="POST" enctype="multipart/form-data" id="formConfig">
@csrf
@method('PUT')

<div class="card card-primary card-tabs">
    <div class="card-header p-0 pt-1">
        <ul class="nav nav-tabs config-tab-nav" id="configTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-empresa" role="tab">
                    <i class="fas fa-building"></i> Empresa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-logo" role="tab">
                    <i class="fas fa-image"></i> Logo & Apariencia
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-facturacion" role="tab">
                    <i class="fas fa-file-invoice-dollar"></i> Facturación
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-sunat" role="tab">
                    <i class="fas fa-stamp text-success"></i> SUNAT / Electrónica
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-sistema" role="tab">
                    <i class="fas fa-cog"></i> Sistema
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content pt-2">

            {{-- ══════════════════════════════════════════════════════
                 TAB 1 — EMPRESA
            ═══════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade show active" id="tab-empresa" role="tabpanel">

                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle mr-1 text-info"></i>
                    Esta información aparecerá en los comprobantes, reportes y documentos generados por el sistema.
                </p>

                <div class="tab-section-title"><i class="fas fa-id-card mr-1"></i> Identidad Legal</div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label><i class="fas fa-hotel text-primary mr-1"></i> Nombre del Hotel <span class="text-danger">*</span></label>
                        <input type="text" name="empresa_nombre" class="form-control"
                               value="{{ $config['empresa_nombre']->valor ?? '' }}"
                               placeholder="Ej: Hotel Los Girasoles" required>
                        <small class="field-desc">Nombre comercial que aparece en encabezados</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label><i class="fas fa-file-alt text-secondary mr-1"></i> Razón Social</label>
                        <input type="text" name="empresa_razon_social" class="form-control"
                               value="{{ $config['empresa_razon_social']->valor ?? '' }}"
                               placeholder="Ej: Inversiones Girasol S.A.C.">
                        <small class="field-desc">Nombre legal registrado ante SUNAT</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-hashtag text-warning mr-1"></i> RUC</label>
                        <input type="text" name="empresa_ruc" class="form-control"
                               value="{{ $config['empresa_ruc']->valor ?? '' }}"
                               placeholder="20XXXXXXXXX" maxlength="11" pattern="\d{11}">
                        <small class="field-desc">11 dígitos, solo para facturación</small>
                    </div>
                    <div class="col-md-8 form-group">
                        <label><i class="fas fa-quote-right text-info mr-1"></i> Eslogan</label>
                        <input type="text" name="empresa_eslogan" class="form-control"
                               value="{{ $config['empresa_eslogan']->valor ?? '' }}"
                               placeholder="Ej: Tu hogar lejos de casa">
                        <small class="field-desc">Frase que aparece bajo el nombre en documentos</small>
                    </div>
                </div>

                <div class="tab-section-title"><i class="fas fa-map-marker-alt mr-1"></i> Contacto y Ubicación</div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label><i class="fas fa-map-pin text-danger mr-1"></i> Dirección</label>
                        <textarea name="empresa_direccion" class="form-control" rows="2"
                                  placeholder="Av. Los Álamos 1234, Miraflores, Lima">{{ $config['empresa_direccion']->valor ?? '' }}</textarea>
                        <small class="field-desc">Dirección completa del establecimiento</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-phone text-success mr-1"></i> Teléfono</label>
                        <input type="text" name="empresa_telefono" class="form-control"
                               value="{{ $config['empresa_telefono']->valor ?? '' }}"
                               placeholder="+51 1 234-5678">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-envelope text-info mr-1"></i> Correo Electrónico</label>
                        <input type="email" name="empresa_email" class="form-control"
                               value="{{ $config['empresa_email']->valor ?? '' }}"
                               placeholder="reservas@hotel.com">
                    </div>
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-globe text-primary mr-1"></i> Sitio Web</label>
                        <input type="url" name="empresa_web" class="form-control"
                               value="{{ $config['empresa_web']->valor ?? '' }}"
                               placeholder="https://www.mihotel.com">
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 TAB 2 — LOGO & APARIENCIA
            ═══════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-logo" role="tabpanel">

                <div class="tab-section-title"><i class="fas fa-image mr-1"></i> Logotipo del Hotel</div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="logo-drop-area" id="logoDropArea" onclick="document.getElementById('inputLogo').click()">
                            @php $logoActual = $config['empresa_logo']->valor ?? null; @endphp
                            @if($logoActual)
                                <img src="{{ asset($logoActual) }}" class="logo-preview mb-2" id="logoPreview" alt="Logo actual">
                                <p class="text-muted mb-0" id="logoHint">Clic para cambiar el logo</p>
                            @else
                                <div id="logoPlaceholder">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                    <p class="mb-1 font-weight-bold">Arrastra tu logo aquí</p>
                                    <p class="text-muted small mb-0">o haz clic para seleccionar</p>
                                </div>
                                <img src="" class="logo-preview mb-2 d-none" id="logoPreview" alt="Preview">
                            @endif
                            <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle mr-1"></i>PNG, JPG o SVG · Máx. 2MB · Recomendado: fondo transparente</p>
                        </div>
                        <input type="file" name="empresa_logo" id="inputLogo" accept="image/*" class="d-none">

                        @if($logoActual)
                        <div class="mt-2 text-right">
                            <form action="{{ route('configuracion.logo.delete') }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar el logo actual?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash mr-1"></i>Eliminar logo
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-eye mr-1 text-primary"></i>Vista previa en Sidebar</h6>
                                <div style="background:#1a2035;padding:12px 16px;border-radius:8px;display:flex;align-items:center;gap:10px">
                                    <div style="width:36px;height:36px;border-radius:50%;background:#2c3e6b;display:flex;align-items:center;justify-content:center">
                                        @if($logoActual)
                                            <img src="{{ asset($logoActual) }}" style="width:32px;height:32px;object-fit:contain;border-radius:50%">
                                        @else
                                            <i class="fas fa-hotel text-white"></i>
                                        @endif
                                    </div>
                                    <span style="color:#fff;font-weight:700;font-size:.9rem">{{ $config['empresa_nombre']->valor ?? 'Mi Hotel' }}</span>
                                </div>
                                <small class="text-muted mt-2 d-block">Así aparecerá en la barra lateral del sistema</small>
                            </div>
                        </div>

                        <div class="card bg-light border-0 mt-2">
                            <div class="card-body">
                                <h6 class="font-weight-bold mb-1"><i class="fas fa-lightbulb mr-1 text-warning"></i>Recomendaciones</h6>
                                <ul class="small text-muted pl-3 mb-0">
                                    <li>Usa fondo transparente (PNG) para mejor resultado</li>
                                    <li>Dimensiones ideales: 200×80 px o ratio 5:2</li>
                                    <li>Resolución mínima: 72 DPI</li>
                                    <li>Tamaño máximo: 2 MB</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-section-title mt-3"><i class="fas fa-palette mr-1"></i> Colores del Sistema</div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Color del Sidebar</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text p-1">
                                    <input type="color" name="sistema_color_sidebar"
                                           id="colorSidebar"
                                           value="{{ $config['sistema_color_sidebar']->valor ?? '#1a2035' }}"
                                           style="width:32px;height:28px;border:none;cursor:pointer;padding:0">
                                </span>
                            </div>
                            <input type="text" class="form-control" id="hexSidebar"
                                   value="{{ $config['sistema_color_sidebar']->valor ?? '#1a2035' }}"
                                   placeholder="#1a2035" maxlength="7">
                        </div>
                        <small class="field-desc">Color de fondo del menú lateral</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Color de Marca (Brand)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text p-1">
                                    <input type="color" name="sistema_color_brand"
                                           id="colorBrand"
                                           value="{{ $config['sistema_color_brand']->valor ?? '#141d2e' }}"
                                           style="width:32px;height:28px;border:none;cursor:pointer;padding:0">
                                </span>
                            </div>
                            <input type="text" class="form-control" id="hexBrand"
                                   value="{{ $config['sistema_color_brand']->valor ?? '#141d2e' }}"
                                   placeholder="#141d2e" maxlength="7">
                        </div>
                        <small class="field-desc">Color del área del logo/marca</small>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <div class="w-100 mt-2">
                            <label class="d-block small text-muted mb-1">Vista Previa</label>
                            <div id="colorPreview" style="border-radius:8px;overflow:hidden;border:1px solid #dee2e6">
                                <div id="prevBrand" style="height:12px;background:{{ $config['sistema_color_brand']->valor ?? '#141d2e' }}"></div>
                                <div id="prevSidebar" style="height:40px;background:{{ $config['sistema_color_sidebar']->valor ?? '#1a2035' }};
                                     display:flex;align-items:center;padding:0 12px">
                                    <i class="fas fa-hotel text-white mr-2"></i>
                                    <span style="color:#fff;font-size:.8rem;font-weight:600">Sistema Hospedaje</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 TAB 3 — FACTURACIÓN
            ═══════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-facturacion" role="tabpanel">

                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle mr-1 text-info"></i>
                    Configura la moneda, impuestos y series de numeración para los comprobantes de pago.
                </p>

                <div class="tab-section-title"><i class="fas fa-coins mr-1"></i> Moneda</div>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Símbolo de Moneda <span class="text-danger">*</span></label>
                        <input type="text" name="facturacion_moneda_simbolo" class="form-control font-weight-bold"
                               value="{{ $config['facturacion_moneda_simbolo']->valor ?? 'S/' }}"
                               placeholder="S/" maxlength="5" required>
                        <small class="field-desc">Ej: S/, $, €, £</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Nombre de la Moneda</label>
                        <input type="text" name="facturacion_moneda_nombre" class="form-control"
                               value="{{ $config['facturacion_moneda_nombre']->valor ?? 'Soles' }}"
                               placeholder="Soles">
                        <small class="field-desc">Nombre completo (Soles, Dólares...)</small>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><i class="fas fa-percentage text-warning mr-1"></i> IGV / Impuesto (%)</label>
                        <div class="input-group">
                            <input type="number" name="facturacion_igv" class="form-control"
                                   value="{{ $config['facturacion_igv']->valor ?? '18' }}"
                                   min="0" max="100" step="0.01" required>
                            <div class="input-group-append"><span class="input-group-text">%</span></div>
                        </div>
                        <small class="field-desc">0 = sin impuesto</small>
                    </div>
                </div>

                <div class="tab-section-title"><i class="fas fa-receipt mr-1"></i> Series de Comprobantes</div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-file-alt mr-1 text-success"></i> Serie — Boleta</label>
                        <input type="text" name="facturacion_serie_boleta" class="form-control"
                               value="{{ $config['facturacion_serie_boleta']->valor ?? 'B001' }}"
                               placeholder="B001" maxlength="10">
                        <small class="field-desc">Prefijo para boletas de venta</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-file-invoice mr-1 text-primary"></i> Serie — Factura</label>
                        <input type="text" name="facturacion_serie_factura" class="form-control"
                               value="{{ $config['facturacion_serie_factura']->valor ?? 'F001' }}"
                               placeholder="F001" maxlength="10">
                        <small class="field-desc">Prefijo para facturas</small>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><i class="fas fa-file mr-1 text-secondary"></i> Serie — Recibo</label>
                        <input type="text" name="facturacion_serie_recibo" class="form-control"
                               value="{{ $config['facturacion_serie_recibo']->valor ?? 'R001' }}"
                               placeholder="R001" maxlength="10">
                        <small class="field-desc">Prefijo para recibos</small>
                    </div>
                </div>

                <div class="tab-section-title"><i class="fas fa-align-left mr-1"></i> Texto Adicional</div>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Pie de Comprobante</label>
                        <textarea name="facturacion_pie_factura" class="form-control" rows="2"
                                  placeholder="Ej: Gracias por su preferencia. Este documento no tiene validez tributaria.">{{ $config['facturacion_pie_factura']->valor ?? '' }}</textarea>
                        <small class="field-desc">Aparece al pie de todos los comprobantes generados en PDF</small>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 TAB 4 — SUNAT / FACTURACIÓN ELECTRÓNICA  (rediseño)
            ═══════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-sunat" role="tabpanel">

                @php
                    $ambienteActual = $config['sunat_ambiente']->valor ?? 'beta';
                    $greenterOk  = class_exists('Greenter\See');
                    $qrOk        = class_exists('BaconQrCode\Writer'); // viene con Greenter
                    $certOk      = !empty($config['sunat_certificado_path']->valor) &&
                                   \Illuminate\Support\Facades\Storage::disk('local')->exists($config['sunat_certificado_path']->valor ?? '');
                    $credOk      = !empty($config['sunat_sol_usuario']->valor) && !empty($config['sunat_sol_clave']->valor);
                    $rucOk       = !empty($config['empresa_ruc']->valor) && strlen($config['empresa_ruc']->valor) === 11;
                    $ubigeoOk    = !empty($config['sunat_ubigeo']->valor);

                    $checks = [
                        ['ok'=>$greenterOk, 'label'=>'Librería greenter/greenter', 'desc'=>'Motor de emisión y firma UBL 2.1'],
                        ['ok'=>$rucOk,      'label'=>'RUC del emisor',              'desc'=>'Configurado en la pestaña Empresa'],
                        ['ok'=>$credOk,     'label'=>'Credenciales SOL',           'desc'=>'Usuario y clave SOL secundarias'],
                        ['ok'=>$certOk,     'label'=>'Certificado digital',        'desc'=>'Archivo .p12 o .pem cargado'],
                        ['ok'=>$ubigeoOk,   'label'=>'Ubicación fiscal',           'desc'=>'Ubigeo INEI configurado'],
                    ];
                    $totalSteps = count($checks);
                    $okSteps    = collect($checks)->where('ok', true)->count();
                    $progressPct = round(($okSteps / $totalSteps) * 100);
                @endphp

                {{-- ════ HERO ════════════════════════════════════════════════ --}}
                <div class="sunat-hero d-flex align-items-center">
                    <div class="hero-icon mr-3">
                        <i class="fas fa-stamp"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4>Facturación Electrónica SUNAT</h4>
                        <p>
                            Emisión directa (Facturador Directo). Genera XML UBL 2.1, firma digital y envío al web service oficial.
                        </p>
                    </div>
                    <div class="text-right d-none d-md-block" style="position:relative; z-index:2">
                        <div style="font-size:.7rem; opacity:.85; letter-spacing:.1em">AMBIENTE ACTUAL</div>
                        <div style="font-size:1.1rem; font-weight:700">
                            @if($ambienteActual === 'produccion')
                                <i class="fas fa-rocket mr-1"></i>PRODUCCIÓN
                            @else
                                <i class="fas fa-flask mr-1"></i>BETA · PRUEBAS
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ════ PROGRESO DE CONFIGURACIÓN ════════════════════════════ --}}
                <div class="sunat-progress">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong style="font-size:.95rem">
                                <i class="fas fa-tasks mr-1 text-primary"></i>
                                Progreso de configuración
                            </strong>
                            <small class="text-muted ml-2">{{ $okSteps }} de {{ $totalSteps }} completados</small>
                        </div>
                        <strong style="font-size:1.15rem; color:{{ $progressPct === 100 ? '#28a745' : '#fd7e14' }}">
                            {{ $progressPct }}%
                        </strong>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 999px">
                        <div class="progress-bar" role="progressbar" style="width: {{ $progressPct }}%"></div>
                    </div>
                    <div class="mt-3 d-flex flex-wrap" style="gap:.4rem">
                        @foreach($checks as $c)
                            <span class="step-pill {{ $c['ok'] ? 'done' : 'missing' }}">
                                @if($c['ok']) <i class="fas fa-check-circle"></i>
                                @else <i class="far fa-circle"></i>
                                @endif
                                {{ $c['label'] }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    {{-- ════ CREDENCIALES SOL ════════════════════════════════ --}}
                    <div class="col-lg-7">
                        <div class="sunat-card">
                            <div class="sc-head">
                                <div class="sc-icon bg-cred"><i class="fas fa-key"></i></div>
                                <div>
                                    <div class="sc-title">Credenciales SOL</div>
                                    <div class="sc-subtitle">SUNAT Operaciones en Línea — Usuario secundario</div>
                                </div>
                                <span class="sc-badge {{ $credOk ? 'ok' : 'warning' }}">
                                    @if($credOk)<i class="fas fa-check mr-1"></i>Listo @else Pendiente @endif
                                </span>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-600">Usuario SOL <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-user text-warning"></i></span>
                                        </div>
                                        <input type="text" name="sunat_sol_usuario" class="form-control"
                                               value="{{ $config['sunat_sol_usuario']->valor ?? '' }}"
                                               placeholder="Ej: MODDATOS">
                                    </div>
                                    <small class="field-desc">Usuario secundario SOL (no tu clave web de DNI)</small>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-600">Clave SOL <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-lock text-warning"></i></span>
                                        </div>
                                        <input type="password" name="sunat_sol_clave" id="inputSolClave" class="form-control"
                                               value="{{ $config['sunat_sol_clave']->valor ?? '' }}"
                                               placeholder="Clave SOL secundaria"
                                               autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btnToggleSolClave">
                                                <i class="fas fa-eye" id="iconSolClave"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="field-desc">Se cifra al guardar en base de datos</small>
                                </div>
                            </div>

                            {{-- Ambiente cards --}}
                            <label class="font-weight-600 mt-2 mb-2 d-block">Ambiente de trabajo</label>
                            <div class="row" style="gap:0">
                                <div class="col-md-6 pr-md-2 mb-2">
                                    <label class="ambiente-card beta {{ $ambienteActual === 'beta' ? 'selected' : '' }} d-block mb-0" data-amb="beta">
                                        <input type="radio" name="sunat_ambiente" value="beta" {{ $ambienteActual === 'beta' ? 'checked' : '' }}>
                                        <div class="d-flex align-items-center">
                                            <div class="am-icon mr-2 text-primary"><i class="fas fa-flask"></i></div>
                                            <div>
                                                <div class="am-title">Beta · Pruebas</div>
                                                <p class="am-desc">Homologación SUNAT — sin validez tributaria</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6 pl-md-2 mb-2">
                                    <label class="ambiente-card prod {{ $ambienteActual === 'produccion' ? 'selected' : '' }} d-block mb-0" data-amb="produccion">
                                        <input type="radio" name="sunat_ambiente" value="produccion" {{ $ambienteActual === 'produccion' ? 'checked' : '' }}>
                                        <div class="d-flex align-items-center">
                                            <div class="am-icon mr-2 text-danger"><i class="fas fa-rocket"></i></div>
                                            <div>
                                                <div class="am-title">Producción</div>
                                                <p class="am-desc">Real SUNAT — comprobantes con validez fiscal</p>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- ════ CERTIFICADO DIGITAL ════════════════════════════ --}}
                        <div class="sunat-card">
                            <div class="sc-head">
                                <div class="sc-icon bg-cert"><i class="fas fa-certificate"></i></div>
                                <div>
                                    <div class="sc-title">Certificado Digital</div>
                                    <div class="sc-subtitle">Archivo .p12 o .pem usado para firmar los XML</div>
                                </div>
                                <span class="sc-badge {{ $certOk ? 'ok' : 'warning' }}">
                                    @if($certOk)<i class="fas fa-shield-alt mr-1"></i>Activo @else Sin certificado @endif
                                </span>
                            </div>

                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label for="inputCertificado" class="cert-uploader d-block {{ !empty($config['sunat_certificado_path']->valor) ? 'has-file' : '' }}" id="certDropZone">
                                        <input type="file" class="d-none" id="inputCertificado"
                                               name="sunat_certificado_archivo" accept=".p12,.pfx,.pem,.crt">
                                        <div class="upl-icon">
                                            @if(!empty($config['sunat_certificado_path']->valor))
                                                <i class="fas fa-shield-check"></i>
                                            @else
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            @endif
                                        </div>
                                        <div id="certFileName">
                                            @if(!empty($config['sunat_certificado_path']->valor))
                                                <strong class="text-success">{{ basename($config['sunat_certificado_path']->valor) }}</strong><br>
                                                <small class="text-muted">Clic o arrastra otro archivo para reemplazarlo</small>
                                            @else
                                                <strong>Arrastra tu certificado aquí</strong><br>
                                                <small class="text-muted">o haz clic para seleccionar (.p12 · .pfx · .pem · .crt)</small>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="font-weight-600">Clave del Certificado</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-key text-primary"></i></span>
                                        </div>
                                        <input type="password" name="sunat_certificado_clave" id="inputCertClave" class="form-control"
                                               value="{{ $config['sunat_certificado_clave']->valor ?? '' }}"
                                               placeholder="Contraseña .p12" autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btnToggleCertClave">
                                                <i class="fas fa-eye" id="iconCertClave"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="field-desc">Vacío si el .pem no tiene clave</small>

                                    <div class="alert alert-light border mt-2 mb-0 p-2" style="font-size:.78rem">
                                        <i class="fas fa-info-circle text-primary mr-1"></i>
                                        Para pruebas:
                                        <a href="https://cpe.sunat.gob.pe/sites/default/files/inline-files/SFS-DEMO.zip"
                                           target="_blank" class="font-weight-bold">
                                            <i class="fas fa-download mr-1"></i>Descargar SFS-DEMO.zip
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ════ UBICACIÓN FISCAL ════════════════════════════ --}}
                        <div class="sunat-card">
                            <div class="sc-head">
                                <div class="sc-icon bg-ubigeo"><i class="fas fa-map-marked-alt"></i></div>
                                <div>
                                    <div class="sc-title">Ubicación Fiscal</div>
                                    <div class="sc-subtitle">Ubigeo INEI del domicilio del emisor</div>
                                </div>
                                <span class="sc-badge {{ $ubigeoOk ? 'ok' : 'warning' }}">
                                    @if($ubigeoOk)<i class="fas fa-check mr-1"></i>Configurado @else Pendiente @endif
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group mb-2">
                                    <label class="font-weight-600">Ubigeo <span class="text-danger">*</span></label>
                                    <input type="text" name="sunat_ubigeo" class="form-control text-center font-weight-bold"
                                           value="{{ $config['sunat_ubigeo']->valor ?? '150101' }}"
                                           placeholder="150101" maxlength="6" pattern="\d{6}"
                                           style="letter-spacing:.15em">
                                    <small class="field-desc">6 dígitos: Dpto+Prov+Dist</small>
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="font-weight-600">Departamento</label>
                                    <input type="text" name="sunat_departamento" class="form-control"
                                           value="{{ $config['sunat_departamento']->valor ?? 'LIMA' }}"
                                           placeholder="LIMA">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="font-weight-600">Provincia</label>
                                    <input type="text" name="sunat_provincia" class="form-control"
                                           value="{{ $config['sunat_provincia']->valor ?? 'LIMA' }}"
                                           placeholder="LIMA">
                                </div>
                                <div class="col-md-3 form-group mb-2">
                                    <label class="font-weight-600">Distrito</label>
                                    <input type="text" name="sunat_distrito" class="form-control"
                                           value="{{ $config['sunat_distrito']->valor ?? 'LIMA' }}"
                                           placeholder="MIRAFLORES">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ════ COLUMNA DERECHA: ESTADO + AYUDA ════════════════════ --}}
                    <div class="col-lg-5">
                        {{-- Diagnóstico --}}
                        <div class="sunat-card">
                            <div class="sc-head">
                                <div class="sc-icon bg-status"><i class="fas fa-stethoscope"></i></div>
                                <div>
                                    <div class="sc-title">Diagnóstico del sistema</div>
                                    <div class="sc-subtitle">Estado de cada componente de la integración</div>
                                </div>
                            </div>

                            <table class="check-table">
                                <tr>
                                    <td width="50">
                                        <span class="ck-icon {{ $greenterOk ? 'ok' : 'bad' }}">
                                            <i class="fas {{ $greenterOk ? 'fa-check' : 'fa-times' }}"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ck-label">Librería Greenter</div>
                                        @if($greenterOk)
                                            <div class="ck-desc">v5.2 instalada · firmador UBL 2.1</div>
                                        @else
                                            <div class="ck-desc text-danger">No instalada</div>
                                            <div class="ck-action"><code>composer require greenter/greenter</code></div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="ck-icon {{ $qrOk ? 'ok' : 'warn' }}">
                                            <i class="fas {{ $qrOk ? 'fa-check' : 'fa-exclamation' }}"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ck-label">Generador de QR</div>
                                        @if($qrOk)
                                            <div class="ck-desc">bacon/bacon-qr-code instalado · genera SVG nativo</div>
                                        @else
                                            <div class="ck-desc text-warning">Usando fallback web (api.qrserver.com)</div>
                                            <div class="ck-action"><small>Se instala automáticamente con greenter</small></div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="ck-icon {{ $credOk ? 'ok' : 'warn' }}">
                                            <i class="fas {{ $credOk ? 'fa-check' : 'fa-exclamation' }}"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ck-label">Credenciales SOL</div>
                                        @if($credOk)
                                            <div class="ck-desc">Usuario: <strong>{{ $config['sunat_sol_usuario']->valor }}</strong></div>
                                        @else
                                            <div class="ck-desc text-warning">Aún sin configurar</div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="ck-icon {{ $certOk ? 'ok' : (!empty($config['sunat_certificado_path']->valor) ? 'bad' : 'warn') }}">
                                            <i class="fas {{ $certOk ? 'fa-check' : 'fa-times' }}"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ck-label">Certificado Digital</div>
                                        @if($certOk)
                                            <div class="ck-desc">Activo · {{ basename($config['sunat_certificado_path']->valor) }}</div>
                                        @elseif(!empty($config['sunat_certificado_path']->valor))
                                            <div class="ck-desc text-danger">Archivo no encontrado en storage</div>
                                        @else
                                            <div class="ck-desc text-warning">Carga tu .p12 o .pem para firmar</div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="ck-icon {{ $ambienteActual === 'produccion' ? 'bad' : 'ok' }}">
                                            <i class="fas {{ $ambienteActual === 'produccion' ? 'fa-rocket' : 'fa-flask' }}"></i>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ck-label">Ambiente activo</div>
                                        @if($ambienteActual === 'produccion')
                                            <div class="ck-desc text-danger"><strong>PRODUCCIÓN</strong> · e-factura.sunat.gob.pe</div>
                                        @else
                                            <div class="ck-desc text-primary"><strong>BETA</strong> · e-beta.sunat.gob.pe</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Ayuda contextual --}}
                        <div class="sunat-card" style="background:linear-gradient(135deg,#f8f9ff,#fff); border-color:#dde3ef">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-lightbulb text-warning mr-2 mt-1" style="font-size:1.3rem"></i>
                                <div style="font-size:.85rem">
                                    <strong class="d-block mb-1">Datos de prueba SUNAT</strong>
                                    <table class="table table-borderless table-sm mb-1" style="font-size:.8rem">
                                        <tr><td class="p-1 text-muted">RUC emisor</td><td class="p-1"><code>20000000001</code></td></tr>
                                        <tr><td class="p-1 text-muted">Usuario SOL</td><td class="p-1"><code>MODDATOS</code></td></tr>
                                        <tr><td class="p-1 text-muted">Clave SOL</td><td class="p-1"><code>moddatos</code></td></tr>
                                        <tr><td class="p-1 text-muted">Clave .p12</td><td class="p-1"><code>moddatos</code></td></tr>
                                    </table>
                                    <small class="text-muted">Úsalos junto al ambiente Beta para homologación.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Accesos rápidos --}}
                        <div class="sunat-card">
                            <div class="sc-head" style="border-bottom-style:solid; border-color:#f1f3f5">
                                <div class="sc-icon" style="background:linear-gradient(135deg,#6f42c1,#a855f7)"><i class="fas fa-rocket"></i></div>
                                <div>
                                    <div class="sc-title">Accesos rápidos</div>
                                    <div class="sc-subtitle">Módulos relacionados</div>
                                </div>
                            </div>
                            <a href="{{ route('facturas.index') }}" class="btn btn-outline-primary btn-block btn-sm mb-1 text-left">
                                <i class="fas fa-file-invoice-dollar mr-2"></i>Facturas y Boletas
                            </a>
                            <a href="{{ route('notas-credito.index') }}" class="btn btn-outline-warning btn-block btn-sm mb-1 text-left">
                                <i class="fas fa-file-medical mr-2"></i>Notas de Crédito
                            </a>
                            <a href="{{ route('comunicaciones-baja.index') }}" class="btn btn-outline-danger btn-block btn-sm mb-1 text-left">
                                <i class="fas fa-ban mr-2"></i>Comunicaciones de Baja
                            </a>
                            <a href="{{ route('resumen-boletas.index') }}" class="btn btn-outline-info btn-block btn-sm text-left">
                                <i class="fas fa-clipboard-list mr-2"></i>Resumen Diario Boletas
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 TAB 5 — SISTEMA
            ═══════════════════════════════════════════════════════════ --}}
            <div class="tab-pane fade" id="tab-sistema" role="tabpanel">

                <p class="text-muted mb-3">
                    <i class="fas fa-info-circle mr-1 text-info"></i>
                    Ajustes generales del sistema como zona horaria y formato de fechas.
                </p>

                <div class="tab-section-title"><i class="fas fa-clock mr-1"></i> Fechas y Hora</div>
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label>Zona Horaria</label>
                        <select name="sistema_zona_horaria" class="form-control select2">
                            @php
                                $zonas = [
                                    'America/Lima'       => 'América/Lima (GMT-5)',
                                    'America/Bogota'     => 'América/Bogotá (GMT-5)',
                                    'America/Santiago'   => 'América/Santiago (GMT-4)',
                                    'America/Guayaquil'  => 'América/Guayaquil (GMT-5)',
                                    'America/La_Paz'     => 'América/La Paz (GMT-4)',
                                    'America/Buenos_Aires'=> 'América/Buenos Aires (GMT-3)',
                                    'America/Mexico_City'=> 'América/Ciudad México (GMT-6)',
                                    'America/New_York'   => 'América/Nueva York (GMT-5)',
                                    'Europe/Madrid'      => 'Europa/Madrid (GMT+1)',
                                ];
                                $zonaActual = $config['sistema_zona_horaria']->valor ?? 'America/Lima';
                            @endphp
                            @foreach($zonas as $val => $label)
                                <option value="{{ $val }}" {{ $zonaActual === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Formato de Fecha</label>
                        <select name="sistema_formato_fecha" class="form-control select2">
                            @php
                                $formatos = [
                                    'd/m/Y'   => 'DD/MM/AAAA (31/12/2025)',
                                    'm/d/Y'   => 'MM/DD/AAAA (12/31/2025)',
                                    'Y-m-d'   => 'AAAA-MM-DD (2025-12-31)',
                                    'd-m-Y'   => 'DD-MM-AAAA (31-12-2025)',
                                    'd M Y'   => 'DD Mes AAAA (31 Dic 2025)',
                                ];
                                $fmtActual = $config['sistema_formato_fecha']->valor ?? 'd/m/Y';
                            @endphp
                            @foreach($formatos as $val => $label)
                                <option value="{{ $val }}" {{ $fmtActual === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end pb-3">
                        <div class="alert alert-light border mb-0 py-2 w-100">
                            <small class="text-muted">Hora actual del servidor:</small><br>
                            <strong>{{ now()->format('d/m/Y H:i:s') }}</strong>
                        </div>
                    </div>
                </div>

                <div class="tab-section-title"><i class="fas fa-info-circle mr-1"></i> Información del Sistema</div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr><th width="30%">Versión de PHP</th><td>{{ PHP_VERSION }}</td></tr>
                                    <tr><th>Versión de Laravel</th><td>{{ app()->version() }}</td></tr>
                                    <tr><th>Entorno</th><td><span class="badge badge-{{ config('app.env') === 'production' ? 'success' : 'warning' }}">{{ config('app.env') }}</span></td></tr>
                                    <tr><th>Base de Datos</th><td>{{ config('database.connections.mysql.database') }} (MySQL)</td></tr>
                                    <tr><th>Zona Horaria del Servidor</th><td>{{ config('app.timezone') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /tab-content --}}
    </div>

    {{-- ── Barra de guardado sticky ── --}}
    <div class="card-footer save-bar">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Los cambios se aplican inmediatamente al guardar.</span>
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-save mr-2"></i>Guardar Configuración
            </button>
        </div>
    </div>

</div>{{-- /card --}}
</form>

@endsection

@push('scripts')
<script>
// ── Previsualización de logo ──────────────────────────────────────────────
const inputLogo   = document.getElementById('inputLogo');
const logoPreview = document.getElementById('logoPreview');
const logoDrop    = document.getElementById('logoDropArea');
const logoPlaceh  = document.getElementById('logoPlaceholder');

inputLogo?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        logoPreview.src = e.target.result;
        logoPreview.classList.remove('d-none');
        if (logoPlaceh) logoPlaceh.classList.add('d-none');
    };
    reader.readAsDataURL(file);
});

['dragover', 'dragleave', 'drop'].forEach(evt => {
    logoDrop?.addEventListener(evt, e => {
        e.preventDefault();
        if (evt === 'dragover') logoDrop.classList.add('drag-over');
        else logoDrop.classList.remove('drag-over');
        if (evt === 'drop' && e.dataTransfer.files[0]) {
            inputLogo.files = e.dataTransfer.files;
            inputLogo.dispatchEvent(new Event('change'));
        }
    });
});

// ── Sincronizar color picker ↔ text input ────────────────────────────────
function syncColor(pickerId, hexId, prevId) {
    const picker = document.getElementById(pickerId);
    const hex    = document.getElementById(hexId);
    const prev   = document.getElementById(prevId);

    picker?.addEventListener('input', () => {
        hex.value = picker.value;
        if (prev) prev.style.background = picker.value;
    });
    hex?.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) {
            picker.value = hex.value;
            if (prev) prev.style.background = hex.value;
        }
    });
}

syncColor('colorSidebar', 'hexSidebar', 'prevSidebar');
syncColor('colorBrand',   'hexBrand',   'prevBrand');

// ── Inicializar Select2 ──────────────────────────────────────────────────
$(document).ready(function () {
    $('select.select2').select2({ theme: 'bootstrap4', width: '100%' });
});

// ── Toggle visibilidad contraseñas SUNAT ────────────────────────────────
function makeToggle(btnId, inputId, iconId) {
    const btn   = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    btn?.addEventListener('click', () => {
        const isPass   = input.type === 'password';
        input.type     = isPass ? 'text' : 'password';
        icon.className = isPass ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
}
makeToggle('btnToggleSolClave',  'inputSolClave',  'iconSolClave');
makeToggle('btnToggleCertClave', 'inputCertClave', 'iconCertClave');

// ── Selector visual de Ambiente SUNAT (tarjetas radio) ──────────────────
document.querySelectorAll('.ambiente-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.ambiente-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        card.querySelector('input[type="radio"]').checked = true;

        // Confirmación al cambiar a producción
        if (card.dataset.amb === 'produccion') {
            if (!confirm('⚠️ Estás cambiando a PRODUCCIÓN.\n\nLos comprobantes tendrán validez tributaria REAL ante SUNAT.\n\n¿Continuar?')) {
                // Volver a Beta
                const beta = document.querySelector('.ambiente-card.beta');
                document.querySelectorAll('.ambiente-card').forEach(c => c.classList.remove('selected'));
                beta.classList.add('selected');
                beta.querySelector('input[type="radio"]').checked = true;
            }
        }
    });
});

// ── Drag & drop + nombre del certificado ─────────────────────────────────
const certDrop  = document.getElementById('certDropZone');
const certInput = document.getElementById('inputCertificado');
const certName  = document.getElementById('certFileName');

function updateCertName(file) {
    if (!file) return;
    certDrop.classList.add('has-file');
    certDrop.querySelector('.upl-icon').innerHTML = '<i class="fas fa-shield-alt"></i>';
    certName.innerHTML = `<strong class="text-success">${file.name}</strong><br>
        <small class="text-muted">Archivo listo · ${(file.size/1024).toFixed(1)} KB</small>`;
}

certInput?.addEventListener('change', e => updateCertName(e.target.files[0]));

['dragover', 'dragleave', 'drop'].forEach(evt => {
    certDrop?.addEventListener(evt, e => {
        e.preventDefault();
        if (evt === 'dragover') certDrop.style.borderColor = '#0d6efd';
        else if (evt === 'dragleave') certDrop.style.borderColor = '';
        if (evt === 'drop' && e.dataTransfer.files[0]) {
            certInput.files = e.dataTransfer.files;
            updateCertName(e.dataTransfer.files[0]);
        }
    });
});

// ── Persistir hash en URL al cambiar tab ────────────────────────────────
$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
    history.replaceState(null, null, e.target.getAttribute('href'));
});
// Al cargar, abrir el tab indicado por hash si existe
(function () {
    const hash = window.location.hash;
    if (hash && document.querySelector(`a[href="${hash}"]`)) {
        $(`a[href="${hash}"]`).tab('show');
    }
})();
</script>
@endpush
