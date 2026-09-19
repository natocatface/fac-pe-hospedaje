@extends('layouts.app')
@section('title', 'Nueva Comunicación de Baja')
@section('page-title', 'Nueva Comunicación de Baja')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('comunicaciones-baja.index') }}">Comunicaciones de Baja</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('content')
<form action="{{ route('comunicaciones-baja.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-ban mr-2"></i>Datos de la baja</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Fecha de Comunicación <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_comunicacion" class="form-control"
                                   value="{{ old('fecha_comunicacion', now()->toDateString()) }}" required>
                            <small class="text-muted">Fecha en que se reporta la baja a SUNAT.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Motivo de la baja <span class="text-danger">*</span></label>
                        <textarea name="motivo" class="form-control" rows="2" maxlength="255" required
                                  placeholder="Ej.: Error en datos del cliente / Operación no realizada / etc.">{{ old('motivo') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Comprobantes a anular</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAll"></th>
                                <th>N° Interno</th>
                                <th>N° SUNAT</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($facturasElegibles as $f)
                            <tr>
                                <td><input type="checkbox" name="factura_ids[]" value="{{ $f->id }}" class="chk-factura"></td>
                                <td><a href="{{ route('facturas.show', $f) }}" target="_blank">{{ $f->numero }}</a></td>
                                <td><strong>{{ $f->numero_sunat }}</strong></td>
                                <td>{{ $f->huesped->nombre_completo }}</td>
                                <td>{{ $f->fecha_emision->format('d/m/Y') }}</td>
                                <td class="text-right">S/ {{ number_format($f->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">No hay facturas elegibles para baja.</td></tr>
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
                    <p>La <strong>Comunicación de Baja</strong> permite anular electrónicamente <strong>facturas</strong> ya aceptadas por SUNAT.</p>
                    <ul class="mb-2">
                        <li>Las <strong>boletas</strong> se anulan mediante el Resumen Diario.</li>
                        <li>Se reporta usando el formato <strong>RA-YYYYMMDD-####</strong>.</li>
                        <li>SUNAT devuelve un <strong>ticket</strong> que debe consultarse posteriormente para obtener el CDR.</li>
                        <li>Una vez aceptada, las facturas quedan en estado <strong>"baja"</strong>.</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning btn-block">
                        <i class="fas fa-save mr-1"></i>Crear Comunicación
                    </button>
                    <a href="{{ route('comunicaciones-baja.index') }}" class="btn btn-secondary btn-block">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.chk-factura').forEach(c => c.checked = this.checked);
});
</script>
@endpush
@endsection
