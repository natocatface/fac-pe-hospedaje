@extends('layouts.app')
@section('title', "Nota de Crédito {$notaCredito->numero_interno}")
@section('page-title', "Nota de Crédito {$notaCredito->numero_interno}")
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('notas-credito.index') }}">Notas de Crédito</a></li>
    <li class="breadcrumb-item active">{{ $notaCredito->numero_interno }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-medical mr-2"></i>
                    NOTA DE CRÉDITO: {{ $notaCredito->numero_interno }}
                    @if($notaCredito->numero_sunat)
                        <span class="badge badge-dark ml-2">{{ $notaCredito->numero_sunat }}</span>
                    @endif
                </h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted">COMPROBANTE AFECTADO</h6>
                        <strong>{{ strtoupper($notaCredito->factura->tipo_comprobante) }}
                            {{ $notaCredito->factura->numero_sunat ?? $notaCredito->factura->numero }}</strong><br>
                        <small>Total original: S/ {{ number_format($notaCredito->factura->total, 2) }}</small><br>
                        <small>Fecha: {{ $notaCredito->factura->fecha_emision->format('d/m/Y') }}</small>
                    </div>
                    <div class="col-md-6 text-md-right">
                        <h6 class="text-muted">CLIENTE</h6>
                        <strong>{{ $notaCredito->factura->huesped->nombre_completo }}</strong><br>
                        <small>{{ $notaCredito->factura->huesped->tipo_documento }}:
                            {{ $notaCredito->factura->huesped->num_documento }}</small>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Fecha Emisión</small>
                        <p><strong>{{ $notaCredito->fecha_emision->format('d/m/Y') }}</strong></p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Motivo</small>
                        <p><strong>{{ $notaCredito->codigo_motivo }}</strong> — {{ $notaCredito->motivo_descripcion_corto }}</p>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Tipo SUNAT</small>
                        <p><strong>07 — Nota de Crédito</strong></p>
                    </div>
                </div>

                <div class="callout callout-warning">
                    <strong>Descripción del motivo:</strong><br>
                    {{ $notaCredito->motivo_descripcion }}
                </div>

                <hr>

                <table class="table table-sm">
                    <tr><td>Subtotal:</td><td class="text-right">S/ {{ number_format($notaCredito->subtotal, 2) }}</td></tr>
                    <tr><td>IGV:</td><td class="text-right">S/ {{ number_format($notaCredito->igv, 2) }}</td></tr>
                    <tr class="bg-light h5 font-weight-bold">
                        <td>TOTAL A ACREDITAR:</td>
                        <td class="text-right">S/ {{ number_format($notaCredito->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-{{ $notaCredito->estado_sunat_badge }}">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-stamp mr-1"></i>Estado SUNAT
                </h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge badge-{{ $notaCredito->estado_sunat_badge }} px-3 py-2 mr-2" style="font-size:.85rem">
                        {{ $notaCredito->estado_sunat_label }}
                    </span>
                </div>

                @if($notaCredito->mensaje_sunat)
                <div class="callout callout-{{ $notaCredito->aceptado_sunat ? 'success' : 'danger' }} py-2 px-3 mb-2">
                    <small>
                        @if($notaCredito->codigo_sunat)
                        <strong>Código {{ $notaCredito->codigo_sunat }}:</strong>
                        @endif
                        {{ $notaCredito->mensaje_sunat }}
                    </small>
                </div>
                @endif

                @if($notaCredito->fecha_envio_sunat)
                <small class="text-muted d-block mb-3">
                    <i class="fas fa-clock mr-1"></i>
                    Enviado: {{ $notaCredito->fecha_envio_sunat->format('d/m/Y H:i') }}
                </small>
                @endif

                <hr class="mt-1 mb-2">

                <div class="d-grid gap-1">
                    @if(!$notaCredito->aceptado_sunat)
                    <form action="{{ route('notas-credito.emitir', $notaCredito) }}" method="POST"
                          onsubmit="return confirm('¿Enviar esta nota de crédito a SUNAT?')">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-block btn-sm mb-1">
                            <i class="fas fa-paper-plane mr-1"></i>
                            {{ $notaCredito->estado_sunat === 'no_emitido' ? 'Emitir a SUNAT' : 'Reintentar envío' }}
                        </button>
                    </form>
                    @endif

                    @if($notaCredito->xml_path)
                    <a href="{{ route('notas-credito.xml', $notaCredito) }}"
                       class="btn btn-outline-secondary btn-block btn-sm mb-1">
                        <i class="fas fa-code mr-1"></i>Descargar XML
                    </a>
                    @endif

                    @if($notaCredito->cdr_path)
                    <a href="{{ route('notas-credito.cdr', $notaCredito) }}"
                       class="btn btn-outline-info btn-block btn-sm">
                        <i class="fas fa-certificate mr-1"></i>Descargar CDR
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-2">
                <a href="{{ route('facturas.show', $notaCredito->factura) }}" class="btn btn-sm btn-outline-primary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i>Volver a la factura afectada
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
