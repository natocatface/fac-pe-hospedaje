@extends('layouts.app')
@section('title', 'Nuevo Resumen de Boletas')
@section('page-title', 'Generar Resumen Diario de Boletas')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('resumen-boletas.index') }}">Resúmenes</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
<form action="{{ route('resumen-boletas.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Datos del resumen</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Fecha de Emisión de las Boletas</label>
                            <input type="date" class="form-control" id="fechaEmision"
                                   value="{{ $fechaSugerida }}"
                                   onchange="window.location='{{ route('resumen-boletas.create') }}?fecha=' + this.value">
                            <small class="text-muted">Cambia esta fecha para listar boletas de otro día.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Fecha de Envío del Resumen <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_resumen" class="form-control"
                                   value="{{ old('fecha_resumen', now()->toDateString()) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Boletas emitidas el {{ \Carbon\Carbon::parse($fechaSugerida)->format('d/m/Y') }}</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAll" checked></th>
                                <th>Serie-N°</th>
                                <th>Cliente</th>
                                <th>Doc.</th>
                                <th class="text-right">IGV</th>
                                <th class="text-right">Total</th>
                                <th>Estado SUNAT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($boletasElegibles as $b)
                            <tr>
                                <td><input type="checkbox" name="factura_ids[]" value="{{ $b->id }}" class="chk-boleta" checked></td>
                                <td><strong>{{ $b->numero_sunat }}</strong></td>
                                <td>{{ $b->huesped->nombre_completo }}</td>
                                <td><small>{{ $b->huesped->tipo_documento }}: {{ $b->huesped->num_documento }}</small></td>
                                <td class="text-right">S/ {{ number_format($b->igv, 2) }}</td>
                                <td class="text-right">S/ {{ number_format($b->total, 2) }}</td>
                                <td><span class="badge badge-{{ $b->estado_sunat_badge }}">{{ $b->estado_sunat_label }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">
                                No hay boletas con serie/correlativo asignado para esta fecha.
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle"></i> Información</h3></div>
                <div class="card-body small">
                    <p>El <strong>Resumen Diario de Boletas (RC)</strong> reporta a SUNAT todas las boletas emitidas en una fecha.</p>
                    <ul class="mb-2">
                        <li>Es obligatorio reportar máximo al <strong>7° día calendario</strong> siguiente.</li>
                        <li>Formato del archivo: <strong>RC-YYYYMMDD-####</strong></li>
                        <li>Incluye las <strong>boletas activas</strong> y las <strong>dadas de baja</strong>.</li>
                        <li>SUNAT devuelve un <strong>ticket</strong> para consultar el resultado.</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i>Crear Resumen
                    </button>
                    <a href="{{ route('resumen-boletas.index') }}" class="btn btn-secondary btn-block">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.chk-boleta').forEach(c => c.checked = this.checked);
});
</script>
@endpush
@endsection
