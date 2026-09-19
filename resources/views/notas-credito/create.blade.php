@extends('layouts.app')
@section('title', 'Nueva Nota de Crédito')
@section('page-title', 'Emitir Nota de Crédito Electrónica')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('facturas.index') }}">Facturación</a></li>
    <li class="breadcrumb-item"><a href="{{ route('notas-credito.index') }}">Notas de Crédito</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-medical mr-2"></i>Datos de la Nota de Crédito</h3>
            </div>
            <form action="{{ route('notas-credito.store') }}" method="POST">
                @csrf
                <input type="hidden" name="factura_id" value="{{ $factura->id }}">

                <div class="card-body">
                    {{-- Comprobante afectado --}}
                    <div class="alert alert-info">
                        <strong>Afecta a:</strong>
                        {{ strtoupper($factura->tipo_comprobante) }}
                        <strong>{{ $factura->numero_sunat ?? $factura->numero }}</strong>
                        — Total: S/ {{ number_format($factura->total, 2) }}<br>
                        <small>Cliente: {{ $factura->huesped->nombre_completo }}</small>
                    </div>

                    {{-- Motivo --}}
                    <div class="form-group">
                        <label>Motivo (Catálogo SUNAT 09) <span class="text-danger">*</span></label>
                        <select name="codigo_motivo" class="form-control select2" required>
                            <option value="">— Selecciona un motivo —</option>
                            @foreach(\App\Models\NotaCredito::MOTIVOS as $cod => $desc)
                                <option value="{{ $cod }}" {{ old('codigo_motivo') == $cod ? 'selected' : '' }}>
                                    {{ $cod }} — {{ $desc }}
                                </option>
                            @endforeach
                        </select>
                        @error('codigo_motivo')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group">
                        <label>Descripción del motivo <span class="text-danger">*</span></label>
                        <textarea name="motivo_descripcion" class="form-control" rows="2" maxlength="500" required
                                  placeholder="Ejemplo: Devolución por servicio no prestado / Error en el RUC / etc.">{{ old('motivo_descripcion') }}</textarea>
                        @error('motivo_descripcion')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    {{-- Monto --}}
                    <div class="form-group">
                        <div class="form-check mb-2">
                            <input type="checkbox" id="aplicaTotal" name="aplicar_total" value="1" class="form-check-input" checked>
                            <label for="aplicaTotal" class="form-check-label">
                                <strong>Aplica al total del comprobante</strong> (S/ {{ number_format($factura->total, 2) }})
                            </label>
                        </div>
                        <div id="campoMonto" style="display:none">
                            <label>Monto a acreditar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                                <input type="number" step="0.01" min="0.01" max="{{ $factura->total }}"
                                       name="monto_credito" class="form-control"
                                       value="{{ old('monto_credito', $factura->total) }}">
                            </div>
                            <small class="text-muted">Debe ser menor o igual al total del comprobante afectado.</small>
                        </div>
                        <input type="hidden" name="monto_credito" value="{{ $factura->total }}" id="hiddenMontoTotal">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>Crear Nota de Crédito
                    </button>
                    <a href="{{ route('facturas.show', $factura) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-1"></i>Información</h3></div>
            <div class="card-body">
                <p class="small">
                    Una <strong>Nota de Crédito</strong> permite anular total o parcialmente un comprobante ya
                    aceptado por SUNAT. Una vez emitida y aceptada, queda registrada electrónicamente.
                </p>
                <ul class="small mb-0">
                    <li>Serie SUNAT: <strong>FC01</strong> (facturas) / <strong>BC01</strong> (boletas)</li>
                    <li>Tipo documento SUNAT: <strong>07</strong></li>
                    <li>Catálogo de motivos: <strong>09</strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('aplicaTotal').addEventListener('change', function() {
    const campo  = document.getElementById('campoMonto');
    const hidden = document.getElementById('hiddenMontoTotal');
    if (this.checked) {
        campo.style.display = 'none';
        hidden.disabled = false;
    } else {
        campo.style.display = 'block';
        hidden.disabled = true;
    }
});
</script>
@endpush
@endsection
