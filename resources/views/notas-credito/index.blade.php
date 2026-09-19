@extends('layouts.app')
@section('title', 'Notas de Crédito')
@section('page-title', 'Notas de Crédito Electrónicas')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturación</a></li>
    <li class="breadcrumb-item active">Notas de Crédito</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-medical mr-2"></i>Notas de Crédito Electrónicas
        </h3>
    </div>

    <div class="card-body border-bottom pb-3">
        <form method="GET" class="form-inline flex-wrap">
            <select name="estado_sunat" class="form-control form-control-sm mr-2 mb-2">
                <option value="">SUNAT: todos</option>
                @foreach(['no_emitido'=>'No emitida','pendiente'=>'Pendiente','aceptado'=>'Aceptada','aceptado_obs'=>'Aceptada c/obs','rechazado'=>'Rechazada','excepcion'=>'Error'] as $val => $label)
                    <option value="{{ $val }}" {{ request('estado_sunat') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="codigo_motivo" class="form-control form-control-sm mr-2 mb-2">
                <option value="">Motivo: todos</option>
                @foreach(\App\Models\NotaCredito::MOTIVOS as $cod => $desc)
                    <option value="{{ $cod }}" {{ request('codigo_motivo') == $cod ? 'selected' : '' }}>{{ $cod }} — {{ $desc }}</option>
                @endforeach
            </select>
            <input type="date" name="fecha_desde" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_desde') }}">
            <input type="date" name="fecha_hasta" class="form-control form-control-sm mr-2 mb-2" value="{{ request('fecha_hasta') }}">
            <button type="submit" class="btn btn-secondary btn-sm mr-1 mb-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
            <a href="{{ route('notas-credito.index') }}" class="btn btn-outline-secondary btn-sm mb-2">Limpiar</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>N° Interno</th>
                    <th>N° SUNAT</th>
                    <th>Factura afectada</th>
                    <th>Motivo</th>
                    <th>Fecha</th>
                    <th class="text-right">Total</th>
                    <th>Estado SUNAT</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notas as $nc)
                <tr>
                    <td><a href="{{ route('notas-credito.show', $nc) }}" class="font-weight-bold">{{ $nc->numero_interno }}</a></td>
                    <td>
                        @if($nc->numero_sunat)
                            <span class="badge badge-light border">{{ $nc->numero_sunat }}</span>
                        @else
                            <small class="text-muted">—</small>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('facturas.show', $nc->factura) }}">{{ $nc->factura->numero_sunat ?? $nc->factura->numero }}</a>
                    </td>
                    <td>
                        <small><strong>{{ $nc->codigo_motivo }}</strong> — {{ $nc->motivo_descripcion_corto }}</small>
                    </td>
                    <td>{{ $nc->fecha_emision->format('d/m/Y') }}</td>
                    <td class="text-right font-weight-bold">S/ {{ number_format($nc->total, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $nc->estado_sunat_badge }}">{{ $nc->estado_sunat_label }}</span>
                    </td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('notas-credito.show', $nc) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                        @if(!$nc->aceptado_sunat)
                        <form action="{{ route('notas-credito.emitir', $nc) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Enviar la nota de crédito {{ $nc->numero_interno }} a SUNAT?')">
                            @csrf
                            <button class="btn btn-xs btn-success" title="Emitir a SUNAT"><i class="fas fa-paper-plane"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No hay notas de crédito registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $notas->withQueryString()->links() }}
    </div>
</div>
@endsection
