@extends('layouts.app')
@section('title', 'Comunicaciones de Baja')
@section('page-title', 'Comunicaciones de Baja SUNAT')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturación</a></li>
    <li class="breadcrumb-item active">Comunicaciones de Baja</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ban mr-2"></i>Comunicaciones de Baja</h3>
        <div class="card-tools">
            <a href="{{ route('comunicaciones-baja.create') }}" class="btn btn-sm btn-warning">
                <i class="fas fa-plus mr-1"></i>Nueva Comunicación
            </a>
        </div>
    </div>

    <div class="card-body border-bottom pb-3">
        <form method="GET" class="form-inline flex-wrap">
            <select name="estado_sunat" class="form-control form-control-sm mr-2 mb-2">
                <option value="">SUNAT: todos</option>
                @foreach(['no_emitido'=>'No emitida','pendiente'=>'Pendiente','aceptado'=>'Aceptada','aceptado_obs'=>'Acept. c/obs','rechazado'=>'Rechazada','excepcion'=>'Error'] as $val => $label)
                    <option value="{{ $val }}" {{ request('estado_sunat') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="fecha_desde" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_desde') }}">
            <input type="date" name="fecha_hasta" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_hasta') }}">
            <button type="submit" class="btn btn-secondary btn-sm mr-1 mb-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
            <a href="{{ route('comunicaciones-baja.index') }}" class="btn btn-outline-secondary btn-sm mb-2">Limpiar</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Código</th>
                    <th>Fecha Generación</th>
                    <th>Fecha Comunicación</th>
                    <th class="text-center">N° Facturas</th>
                    <th>Motivo</th>
                    <th>Ticket SUNAT</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comunicaciones as $cb)
                <tr>
                    <td><a href="{{ route('comunicaciones-baja.show', $cb) }}" class="font-weight-bold">{{ $cb->codigo_archivo }}</a></td>
                    <td>{{ $cb->fecha_generacion->format('d/m/Y') }}</td>
                    <td>{{ $cb->fecha_comunicacion->format('d/m/Y') }}</td>
                    <td class="text-center"><span class="badge badge-info">{{ $cb->facturas_count }}</span></td>
                    <td><small>{{ \Illuminate\Support\Str::limit($cb->motivo, 50) }}</small></td>
                    <td><code style="font-size:.75rem">{{ $cb->ticket_sunat ?? '—' }}</code></td>
                    <td><span class="badge badge-{{ $cb->estado_sunat_badge }}">{{ $cb->estado_sunat_label }}</span></td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('comunicaciones-baja.show', $cb) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                        @if(!$cb->aceptado_sunat)
                        <form action="{{ route('comunicaciones-baja.emitir', $cb) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Enviar esta comunicación a SUNAT?')">
                            @csrf
                            <button class="btn btn-xs btn-warning"><i class="fas fa-paper-plane"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No hay comunicaciones de baja registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $comunicaciones->withQueryString()->links() }}
    </div>
</div>
@endsection
