@extends('layouts.app')
@section('title', "Baja {$comunicacionBaja->codigo_archivo}")
@section('page-title', "Comunicación de Baja: {$comunicacionBaja->codigo_archivo}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('comunicaciones-baja.index') }}">Comunicaciones de Baja</a></li>
    <li class="breadcrumb-item active">{{ $comunicacionBaja->codigo_archivo }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ban mr-2"></i>{{ $comunicacionBaja->codigo_archivo }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Fecha Generación</small>
                        <p><strong>{{ $comunicacionBaja->fecha_generacion->format('d/m/Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Fecha Comunicación</small>
                        <p><strong>{{ $comunicacionBaja->fecha_comunicacion->format('d/m/Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Correlativo</small>
                        <p><strong>{{ str_pad($comunicacionBaja->correlativo, 4, '0', STR_PAD_LEFT) }}</strong></p>
                    </div>
                </div>

                <div class="callout callout-warning">
                    <strong>Motivo:</strong> {{ $comunicacionBaja->motivo }}
                </div>

                <h5 class="mt-3">Comprobantes incluidos ({{ $comunicacionBaja->facturas->count() }})</h5>
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>N° SUNAT</th>
                            <th>N° Interno</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comunicacionBaja->facturas as $f)
                        <tr>
                            <td><strong>{{ $f->numero_sunat }}</strong></td>
                            <td><a href="{{ route('facturas.show', $f) }}">{{ $f->numero }}</a></td>
                            <td>{{ $f->huesped->nombre_completo }}</td>
                            <td>{{ $f->fecha_emision->format('d/m/Y') }}</td>
                            <td class="text-right">S/ {{ number_format($f->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-{{ $comunicacionBaja->estado_sunat_badge }}">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp mr-1"></i>Estado SUNAT</h3></div>
            <div class="card-body">
                <span class="badge badge-{{ $comunicacionBaja->estado_sunat_badge }} px-3 py-2 mb-3" style="font-size:.9rem">
                    {{ $comunicacionBaja->estado_sunat_label }}
                </span>

                @if($comunicacionBaja->ticket_sunat)
                <small class="d-block text-muted mb-2">
                    <i class="fas fa-ticket-alt mr-1"></i>
                    Ticket: <code>{{ $comunicacionBaja->ticket_sunat }}</code>
                </small>
                @endif

                @if($comunicacionBaja->mensaje_sunat)
                <div class="callout callout-{{ $comunicacionBaja->aceptado_sunat ? 'success' : 'danger' }} py-2 px-3 mb-2">
                    <small>
                        @if($comunicacionBaja->codigo_sunat)
                            <strong>Código {{ $comunicacionBaja->codigo_sunat }}:</strong>
                        @endif
                        {{ $comunicacionBaja->mensaje_sunat }}
                    </small>
                </div>
                @endif

                <hr>

                <div class="d-grid gap-1">
                    @if(!$comunicacionBaja->aceptado_sunat && empty($comunicacionBaja->ticket_sunat))
                    <form action="{{ route('comunicaciones-baja.emitir', $comunicacionBaja) }}" method="POST"
                          onsubmit="return confirm('¿Enviar esta comunicación a SUNAT?')">
                        @csrf
                        <button class="btn btn-warning btn-block btn-sm mb-1">
                            <i class="fas fa-paper-plane mr-1"></i>Enviar a SUNAT
                        </button>
                    </form>
                    @endif

                    @if(!empty($comunicacionBaja->ticket_sunat) && !$comunicacionBaja->aceptado_sunat)
                    <form action="{{ route('comunicaciones-baja.consultar', $comunicacionBaja) }}" method="POST">
                        @csrf
                        <button class="btn btn-info btn-block btn-sm mb-1">
                            <i class="fas fa-sync mr-1"></i>Consultar estado del ticket
                        </button>
                    </form>
                    @endif

                    @if($comunicacionBaja->xml_path)
                    <a href="{{ route('comunicaciones-baja.xml', $comunicacionBaja) }}"
                       class="btn btn-outline-secondary btn-block btn-sm mb-1">
                        <i class="fas fa-code mr-1"></i>Descargar XML
                    </a>
                    @endif

                    @if($comunicacionBaja->cdr_path)
                    <a href="{{ route('comunicaciones-baja.cdr', $comunicacionBaja) }}"
                       class="btn btn-outline-info btn-block btn-sm">
                        <i class="fas fa-certificate mr-1"></i>Descargar CDR
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
