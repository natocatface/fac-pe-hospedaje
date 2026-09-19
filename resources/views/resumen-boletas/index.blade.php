@extends('layouts.app')
@section('title', 'Resumen Diario de Boletas')
@section('page-title', 'Resúmenes Diarios de Boletas')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturación</a></li>
    <li class="breadcrumb-item active">Resumen de Boletas</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Resúmenes Diarios de Boletas (RC)</h3>
        <div class="card-tools">
            <a href="{{ route('resumen-boletas.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i>Nuevo Resumen
            </a>
        </div>
    </div>

    <div class="card-body border-bottom pb-3">
        <form method="GET" class="form-inline flex-wrap">
            <select name="estado_sunat" class="form-control form-control-sm mr-2 mb-2">
                <option value="">SUNAT: todos</option>
                @foreach(['no_emitido'=>'No emitido','pendiente'=>'Pendiente','aceptado'=>'Aceptado','aceptado_obs'=>'Acept. c/obs','rechazado'=>'Rechazado','excepcion'=>'Error'] as $val => $label)
                    <option value="{{ $val }}" {{ request('estado_sunat') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="fecha_desde" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_desde') }}">
            <input type="date" name="fecha_hasta" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_hasta') }}">
            <button type="submit" class="btn btn-secondary btn-sm mr-1 mb-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
            <a href="{{ route('resumen-boletas.index') }}" class="btn btn-outline-secondary btn-sm mb-2">Limpiar</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Código</th>
                    <th>Fecha Boletas</th>
                    <th>Fecha Resumen</th>
                    <th class="text-center">N° Boletas</th>
                    <th>Ticket</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resumenes as $r)
                <tr>
                    <td><a href="{{ route('resumen-boletas.show', $r) }}" class="font-weight-bold">{{ $r->codigo_archivo }}</a></td>
                    <td>{{ $r->fecha_generacion->format('d/m/Y') }}</td>
                    <td>{{ $r->fecha_resumen->format('d/m/Y') }}</td>
                    <td class="text-center"><span class="badge badge-info">{{ $r->facturas_count }}</span></td>
                    <td><code style="font-size:.75rem">{{ $r->ticket_sunat ?? '—' }}</code></td>
                    <td><span class="badge badge-{{ $r->estado_sunat_badge }}">{{ $r->estado_sunat_label }}</span></td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('resumen-boletas.show', $r) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                        @if(!$r->aceptado_sunat && empty($r->ticket_sunat))
                        <form action="{{ route('resumen-boletas.emitir', $r) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Enviar este resumen a SUNAT?')">
                            @csrf
                            <button class="btn btn-xs btn-primary"><i class="fas fa-paper-plane"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No hay resúmenes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $resumenes->withQueryString()->links() }}
    </div>
</div>
@endsection
