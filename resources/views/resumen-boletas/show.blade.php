@extends('layouts.app')
@section('title', "Resumen {$resumenBoletas->codigo_archivo}")
@section('page-title', "Resumen Diario: {$resumenBoletas->codigo_archivo}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('resumen-boletas.index') }}">Resúmenes</a></li>
    <li class="breadcrumb-item active">{{ $resumenBoletas->codigo_archivo }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>{{ $resumenBoletas->codigo_archivo }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Fecha de las Boletas</small>
                        <p><strong>{{ $resumenBoletas->fecha_generacion->format('d/m/Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Fecha de Envío</small>
                        <p><strong>{{ $resumenBoletas->fecha_resumen->format('d/m/Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Correlativo</small>
                        <p><strong>{{ str_pad($resumenBoletas->correlativo, 4, '0', STR_PAD_LEFT) }}</strong></p>
                    </div>
                </div>

                <h5 class="mt-2">Boletas incluidas ({{ $resumenBoletas->facturas->count() }})</h5>
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>N° SUNAT</th>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th class="text-right">IGV</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resumenBoletas->facturas as $f)
                        <tr>
                            <td><strong>{{ $f->numero_sunat }}</strong></td>
                            <td>{{ $f->huesped->nombre_completo }}</td>
                            <td><small>{{ $f->huesped->tipo_documento }}: {{ $f->huesped->num_documento }}</small></td>
                            <td class="text-right">S/ {{ number_format($f->igv, 2) }}</td>
                            <td class="text-right">S/ {{ number_format($f->total, 2) }}</td>
                            <td><span class="badge badge-{{ $f->estado_sunat_badge }}">{{ $f->estado_sunat_label }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-weight-bold bg-light">
                        <tr>
                            <td colspan="3" class="text-right">TOTALES:</td>
                            <td class="text-right">S/ {{ number_format($resumenBoletas->facturas->sum('igv'), 2) }}</td>
                            <td class="text-right">S/ {{ number_format($resumenBoletas->facturas->sum('total'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-{{ $resumenBoletas->estado_sunat_badge }}">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp mr-1"></i>Estado SUNAT</h3></div>
            <div class="card-body">
                <span class="badge badge-{{ $resumenBoletas->estado_sunat_badge }} px-3 py-2 mb-3" style="font-size:.9rem">
                    {{ $resumenBoletas->estado_sunat_label }}
                </span>

                @if($resumenBoletas->ticket_sunat)
                <small class="d-block text-muted mb-2">
                    <i class="fas fa-ticket-alt mr-1"></i>
                    Ticket: <code>{{ $resumenBoletas->ticket_sunat }}</code>
                </small>
                @endif

                @if($resumenBoletas->mensaje_sunat)
                <div class="callout callout-{{ $resumenBoletas->aceptado_sunat ? 'success' : 'danger' }} py-2 px-3 mb-2">
                    <small>
                        @if($resumenBoletas->codigo_sunat)
                            <strong>Código {{ $resumenBoletas->codigo_sunat }}:</strong>
                        @endif
                        {{ $resumenBoletas->mensaje_sunat }}
                    </small>
                </div>
                @endif

                <hr>

                <div class="d-grid gap-1">
                    @if(!$resumenBoletas->aceptado_sunat && empty($resumenBoletas->ticket_sunat))
                    <form action="{{ route('resumen-boletas.emitir', $resumenBoletas) }}" method="POST"
                          onsubmit="return confirm('¿Enviar este resumen a SUNAT?')">
                        @csrf
                        <button class="btn btn-primary btn-block btn-sm mb-1">
                            <i class="fas fa-paper-plane mr-1"></i>Enviar a SUNAT
                        </button>
                    </form>
                    @endif

                    @if(!empty($resumenBoletas->ticket_sunat) && !$resumenBoletas->aceptado_sunat)
                    <form action="{{ route('resumen-boletas.consultar', $resumenBoletas) }}" method="POST">
                        @csrf
                        <button class="btn btn-info btn-block btn-sm mb-1">
                            <i class="fas fa-sync mr-1"></i>Consultar ticket
                        </button>
                    </form>
                    @endif

                    @if($resumenBoletas->xml_path)
                    <a href="{{ route('resumen-boletas.xml', $resumenBoletas) }}" class="btn btn-outline-secondary btn-block btn-sm mb-1">
                        <i class="fas fa-code mr-1"></i>Descargar XML
                    </a>
                    @endif

                    @if($resumenBoletas->cdr_path)
                    <a href="{{ route('resumen-boletas.cdr', $resumenBoletas) }}" class="btn btn-outline-info btn-block btn-sm">
                        <i class="fas fa-certificate mr-1"></i>Descargar CDR
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
