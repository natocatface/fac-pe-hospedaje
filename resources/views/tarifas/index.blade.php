@extends('layouts.app')

@section('title', 'Tarifas por Temporada')
@section('page-title', 'Tarifas por Temporada')

@section('breadcrumb')
    <li class="breadcrumb-item active">Tarifas</li>
@endsection

@push('styles')
<style>
    .tarifa-row-vencida { opacity: .55; }
    .priority-badge { font-size: .72rem; padding: .2em .55em; }
</style>
@endpush

@section('content')

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-tags mr-2"></i>
            Tarifas Registradas
        </h3>
        <div class="card-tools">
            <a href="{{ route('tarifas.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus mr-1"></i> Nueva Tarifa
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0" id="tablaTarifas">
            <thead class="thead-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tipo Habitación</th>
                    <th>Vigencia</th>
                    <th>Precio / %</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tarifas as $tarifa)
                <tr class="{{ $tarifa->estado === 'vencida' ? 'tarifa-row-vencida' : '' }}">
                    <td>
                        <strong>{{ $tarifa->nombre }}</strong>
                        @if($tarifa->descripcion)
                        <br><small class="text-muted">{{ Str::limit($tarifa->descripcion, 50) }}</small>
                        @endif
                    </td>
                    <td>
                        @if($tarifa->tipoHabitacion)
                            <span class="badge badge-secondary">{{ $tarifa->tipoHabitacion->nombre }}</span>
                        @else
                            <span class="text-muted"><em>Todos los tipos</em></span>
                        @endif
                    </td>
                    <td>
                        <i class="far fa-calendar-alt mr-1 text-muted"></i>
                        {{ $tarifa->fecha_inicio->format('d/m/Y') }}
                        <span class="text-muted mx-1">→</span>
                        {{ $tarifa->fecha_fin->format('d/m/Y') }}
                    </td>
                    <td>
                        @if($tarifa->tipo_precio === 'fijo')
                            <strong class="text-success">S/ {{ number_format($tarifa->precio_noche, 2) }}</strong>
                            <small class="text-muted">/noche</small>
                        @else
                            <strong class="text-info">+{{ number_format($tarifa->precio_noche, 1) }}%</strong>
                            <small class="text-muted">sobre precio base</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-dark priority-badge">
                            <i class="fas fa-sort-amount-up mr-1"></i>{{ $tarifa->prioridad }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $tarifa->estado_badge }}">
                            {{ ucfirst($tarifa->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('tarifas.edit', $tarifa) }}"
                               class="btn btn-outline-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('tarifas.toggle', $tarifa) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="btn btn-outline-{{ $tarifa->activa ? 'warning' : 'success' }}"
                                        title="{{ $tarifa->activa ? 'Desactivar' : 'Activar' }}">
                                    <i class="fas fa-{{ $tarifa->activa ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('tarifas.destroy', $tarifa) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('¿Eliminar tarifa «{{ $tarifa->nombre }}»?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Panel informativo --}}
<div class="row">
    <div class="col-md-6">
        <div class="callout callout-info">
            <h6><i class="fas fa-info-circle mr-1"></i> ¿Cómo funcionan las tarifas?</h6>
            <ul class="mb-0 pl-3 small">
                <li><strong>Precio fijo:</strong> reemplaza el precio base de la habitación.</li>
                <li><strong>Porcentaje:</strong> agrega un % al precio base (ej: +20% en feriados).</li>
                <li><strong>Prioridad:</strong> si se solapan tarifas, prevalece la de mayor número.</li>
                <li>Una tarifa sin tipo de habitación aplica a todos los tipos.</li>
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="callout callout-warning">
            <h6><i class="fas fa-layer-group mr-1"></i> Tipos de habitación disponibles</h6>
            @if($tipos->count())
            <div class="d-flex flex-wrap gap-1">
                @foreach($tipos as $tipo)
                <span class="badge badge-secondary mr-1 mb-1">{{ $tipo->nombre }}</span>
                @endforeach
            </div>
            @else
            <small class="text-muted">No hay tipos de habitación activos.</small>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var urlNueva = "{{ route('tarifas.create') }}";
    $('#tablaTarifas').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
            emptyTable: 'No hay tarifas registradas. <a href="' + urlNueva + '">Crear la primera</a>',
        },
        order: [[2, 'asc']],
        pageLength: 15,
        columnDefs: [
            { orderable: false, targets: [6] },
            { searchable: false, targets: [4, 5, 6] },
        ],
    });
});
</script>
@endpush
