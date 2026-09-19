@extends('layouts.app')
@section('title', 'Facturación')
@section('page-title', 'Facturación y Pagos')
@section('breadcrumb')
    <li class="breadcrumb-item active">Facturas</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Facturas Emitidas</h3>
    </div>

    <div class="card-body border-bottom pb-3">
        <form method="GET" class="form-inline flex-wrap">
            <input type="text" name="buscar" class="form-control form-control-sm mr-2 mb-2"
                   placeholder="Nro. factura o huésped..." value="{{ request('buscar') }}" style="min-width:200px">
            <select name="estado" class="form-control form-control-sm mr-2 mb-2">
                <option value="">Estado pago: todos</option>
                @foreach(['pendiente','pagada','anulada'] as $est)
                    <option value="{{ $est }}" {{ request('estado') == $est ? 'selected' : '' }}>{{ ucfirst($est) }}</option>
                @endforeach
            </select>
            <select name="estado_sunat" class="form-control form-control-sm mr-2 mb-2">
                <option value="">SUNAT: todos</option>
                @foreach(['no_emitido'=>'No emitido','pendiente'=>'Pendiente','aceptado'=>'Aceptado','aceptado_obs'=>'Acept. c/obs','rechazado'=>'Rechazado','excepcion'=>'Error','baja'=>'Baja'] as $val => $label)
                    <option value="{{ $val }}" {{ request('estado_sunat') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="fecha_desde" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_desde') }}">
            <input type="date" name="fecha_hasta" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_hasta') }}">
            <button type="submit" class="btn btn-secondary btn-sm mr-1 mb-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
            <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary btn-sm mb-2">Limpiar</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Número</th>
                    <th>N° SUNAT</th>
                    <th>Huésped</th>
                    <th>Hab.</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th class="text-right">Total</th>
                    <th>Pago</th>
                    <th>SUNAT</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $f)
                <tr>
                    <td><a href="{{ route('facturas.show', $f) }}" class="font-weight-bold">{{ $f->numero }}</a></td>
                    <td>
                        @if($f->numero_sunat)
                            <span class="badge badge-light border" title="{{ $f->numero_sunat }}">{{ $f->numero_sunat }}</span>
                        @else
                            <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td>{{ $f->huesped->nombre_completo }}</td>
                    <td>{{ $f->reserva->habitacion->numero }}</td>
                    <td>{{ $f->fecha_emision->format('d/m/Y') }}</td>
                    <td><span class="badge badge-secondary">{{ ucfirst($f->tipo_comprobante) }}</span></td>
                    <td class="font-weight-bold text-right">S/ {{ number_format($f->total, 2) }}</td>
                    <td><span class="badge badge-{{ $f->estado_badge }}">{{ ucfirst($f->estado) }}</span></td>
                    <td>
                        @if($f->aplica_electronica)
                            <span class="badge badge-{{ $f->estado_sunat_badge }}" title="{{ $f->mensaje_sunat ?? '' }}">
                                @if($f->estado_sunat === 'aceptado') <i class="fas fa-check-circle mr-1"></i>
                                @elseif($f->estado_sunat === 'rechazado' || $f->estado_sunat === 'excepcion') <i class="fas fa-exclamation-triangle mr-1"></i>
                                @endif
                                {{ $f->estado_sunat_label }}
                            </span>
                        @else
                            <small class="text-muted">N/A</small>
                        @endif
                    </td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('facturas.show', $f) }}" class="btn btn-xs btn-info" title="Ver"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('facturas.pdf', $f) }}" class="btn btn-xs btn-secondary" title="Descargar PDF"><i class="fas fa-file-pdf"></i></a>
                        @if($f->aplica_electronica && !$f->aceptado_sunat && $f->estado !== 'anulada')
                        <form action="{{ route('facturas.sunat.emitir', $f) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Enviar {{ $f->numero }} a SUNAT?')">
                            @csrf
                            <button class="btn btn-xs btn-success" title="Emitir a SUNAT"><i class="fas fa-paper-plane"></i></button>
                        </form>
                        @endif
                        @if($f->estado !== 'anulada' && !$f->aceptado_sunat)
                        <form action="{{ route('facturas.anular', $f) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Anular factura {{ $f->numero }}?')">
                            @csrf
                            <button class="btn btn-xs btn-danger" title="Anular"><i class="fas fa-ban"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4 text-muted">No se encontraron facturas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $facturas->withQueryString()->links() }}
    </div>
</div>
@endsection
