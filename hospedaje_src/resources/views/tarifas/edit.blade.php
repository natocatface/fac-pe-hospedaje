@extends('layouts.app')

@section('title', 'Editar Tarifa')
@section('page-title', 'Editar Tarifa por Temporada')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tarifas.index') }}">Tarifas</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>Editar: {{ $tarifa->nombre }}
                </h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $tarifa->estado_badge }} px-3">
                        {{ ucfirst($tarifa->estado) }}
                    </span>
                </div>
            </div>
            <form action="{{ route('tarifas.update', $tarifa) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">

                    <div class="form-group">
                        <label>Nombre de la tarifa <span class="text-danger">*</span></label>
                        <input type="text" name="nombre"
                               class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $tarifa->nombre) }}">
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de habitación</label>
                                <select name="tipo_habitacion_id"
                                        class="form-control select2 @error('tipo_habitacion_id') is-invalid @enderror">
                                    <option value="">— Aplica a todos los tipos —</option>
                                    @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}"
                                            {{ old('tipo_habitacion_id', $tarifa->tipo_habitacion_id) == $tipo->id ? 'selected' : '' }}>
                                        {{ $tipo->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('tipo_habitacion_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Prioridad <span class="text-muted">(0–10)</span></label>
                                <input type="number" name="prioridad"
                                       class="form-control @error('prioridad') is-invalid @enderror"
                                       value="{{ old('prioridad', $tarifa->prioridad) }}" min="0" max="10">
                                @error('prioridad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha inicio <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_inicio"
                                       class="form-control @error('fecha_inicio') is-invalid @enderror"
                                       value="{{ old('fecha_inicio', $tarifa->fecha_inicio->format('Y-m-d')) }}">
                                @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha fin <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_fin"
                                       class="form-control @error('fecha_fin') is-invalid @enderror"
                                       value="{{ old('fecha_fin', $tarifa->fecha_fin->format('Y-m-d')) }}">
                                @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de precio <span class="text-danger">*</span></label>
                                <select name="tipo_precio" id="tipoPrecio"
                                        class="form-control @error('tipo_precio') is-invalid @enderror">
                                    <option value="fijo"
                                            {{ old('tipo_precio', $tarifa->tipo_precio) === 'fijo' ? 'selected' : '' }}>
                                        Precio fijo (S/)
                                    </option>
                                    <option value="porcentaje"
                                            {{ old('tipo_precio', $tarifa->tipo_precio) === 'porcentaje' ? 'selected' : '' }}>
                                        Porcentaje sobre precio base (%)
                                    </option>
                                </select>
                                @error('tipo_precio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label id="precioLabel">
                                    {{ $tarifa->tipo_precio === 'porcentaje' ? 'Porcentaje de incremento' : 'Precio por noche' }}
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="precioPrefix">
                                            {{ $tarifa->tipo_precio === 'porcentaje' ? '%' : 'S/' }}
                                        </span>
                                    </div>
                                    <input type="number" name="precio_noche"
                                           class="form-control @error('precio_noche') is-invalid @enderror"
                                           value="{{ old('precio_noche', $tarifa->precio_noche) }}"
                                           min="0" step="0.01">
                                    @error('precio_noche')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción <small class="text-muted">(opcional)</small></label>
                        <textarea name="descripcion"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  rows="2">{{ old('descripcion', $tarifa->descripcion) }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="activaCheck"
                                   name="activa" value="1"
                                   {{ old('activa', $tarifa->activa) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="activaCheck">Tarifa activa</label>
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i> Actualizar tarifa
                    </button>
                    <a href="{{ route('tarifas.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.select2').select2({ theme: 'bootstrap4', width: '100%' });

document.getElementById('tipoPrecio').addEventListener('change', function() {
    const prefix = document.getElementById('precioPrefix');
    const label  = document.getElementById('precioLabel');
    if (this.value === 'porcentaje') {
        prefix.textContent = '%';
        label.innerHTML    = 'Porcentaje de incremento <span class="text-danger">*</span>';
    } else {
        prefix.textContent = 'S/';
        label.innerHTML    = 'Precio por noche <span class="text-danger">*</span>';
    }
});
</script>
@endpush
