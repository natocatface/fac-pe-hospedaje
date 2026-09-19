@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('page-title', 'Mi Perfil')

@section('breadcrumb')
    <li class="breadcrumb-item active">Perfil</li>
@endsection

@push('styles')
<style>
    .avatar-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
    }
    .avatar-wrapper img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #dee2e6;
    }
    .avatar-overlay {
        position: absolute;
        bottom: 0;
        right: 0;
        background: #007bff;
        border-radius: 50%;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 2px solid #fff;
    }
    .avatar-overlay:hover { background: #0056b3; }
    .avatar-overlay i { color: #fff; font-size: .85rem; }
    .profile-header {
        background: linear-gradient(135deg, #1a2035 0%, #2d3a5a 100%);
        border-radius: .5rem;
        padding: 2rem;
        color: #fff;
        margin-bottom: 1.5rem;
    }
    .nav-tabs .nav-link.active { font-weight: 600; }
    .password-strength { height: 4px; border-radius: 2px; transition: all .3s; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="row">
    {{-- Columna izquierda: tarjeta de perfil --}}
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body text-center py-4">

                {{-- Avatar --}}
                <div class="avatar-wrapper">
                    <img id="avatarPreview"
                         src="{{ auth()->user()->avatar_url }}"
                         alt="{{ auth()->user()->name }}">
                    <label for="avatarInput" class="avatar-overlay mb-0" title="Cambiar foto">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>

                <h4 class="mb-0">{{ auth()->user()->name }}</h4>
                <small class="text-muted">{{ auth()->user()->email }}</small>

                <div class="mt-2">
                    @php
                        $roleBadge = match(auth()->user()->role ?? 'recepcionista') {
                            'admin'          => ['danger',  'Administrador'],
                            'supervisor'     => ['warning', 'Supervisor'],
                            default          => ['info',    'Recepcionista'],
                        };
                    @endphp
                    <span class="badge badge-{{ $roleBadge[0] }} px-3 py-1" style="font-size:.85rem">
                        {{ $roleBadge[1] }}
                    </span>
                </div>

                <hr>

                <div class="text-left">
                    <small class="text-muted d-block mb-1">
                        <i class="fas fa-envelope mr-1"></i> {{ auth()->user()->email }}
                    </small>
                    @if(auth()->user()->telefono)
                    <small class="text-muted d-block mb-1">
                        <i class="fas fa-phone mr-1"></i> {{ auth()->user()->telefono }}
                    </small>
                    @endif
                    <small class="text-muted d-block">
                        <i class="fas fa-calendar mr-1"></i> Desde {{ auth()->user()->created_at->format('d/m/Y') }}
                    </small>
                </div>

                {{-- Formulario avatar oculto --}}
                <form id="avatarForm" action="{{ route('perfil.avatar') }}" method="POST"
                      enctype="multipart/form-data" class="d-none">
                    @csrf
                    <input type="file" id="avatarInput" name="avatar" accept="image/*">
                </form>

                @if(auth()->user()->avatar)
                <form action="{{ route('perfil.avatar.delete') }}" method="POST" class="mt-3">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit"
                            onclick="return confirm('¿Eliminar foto de perfil?')">
                        <i class="fas fa-trash-alt mr-1"></i> Quitar foto
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Columna derecha: pestañas --}}
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="perfilTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-info" data-toggle="pill"
                           href="#pane-info" role="tab">
                            <i class="fas fa-user mr-1"></i> Información
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-pass" data-toggle="pill"
                           href="#pane-pass" role="tab">
                            <i class="fas fa-lock mr-1"></i> Seguridad
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="perfilTabsContent">

                    {{-- Tab: Información personal --}}
                    <div class="tab-pane fade show active" id="pane-info" role="tabpanel">
                        <form action="{{ route('perfil.update') }}" method="POST">
                            @csrf @method('PUT')

                            <div class="form-group">
                                <label>Nombre completo <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', auth()->user()->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label>Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', auth()->user()->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label>Teléfono</label>
                                <input type="text" name="telefono" class="form-control @error('telefono') is-invalid @enderror"
                                       value="{{ old('telefono', auth()->user()->telefono) }}"
                                       placeholder="Ej: 999-888-777">
                                @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label>Rol</label>
                                <input type="text" class="form-control"
                                       value="{{ ucfirst(auth()->user()->role ?? 'recepcionista') }}"
                                       readonly disabled>
                                <small class="text-muted">El rol solo puede cambiarlo un administrador.</small>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Guardar cambios
                            </button>
                        </form>
                    </div>

                    {{-- Tab: Seguridad / Cambio de contraseña --}}
                    <div class="tab-pane fade" id="pane-pass" role="tabpanel">
                        <form action="{{ route('perfil.password') }}" method="POST" id="passwordForm">
                            @csrf @method('PUT')

                            <div class="form-group">
                                <label>Contraseña actual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password_actual" id="currentPassword"
                                           class="form-control @error('password_actual') is-invalid @enderror"
                                           required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass" type="button"
                                                data-target="#currentPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('password_actual')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nueva contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" id="newPassword"
                                           class="form-control @error('password') is-invalid @enderror"
                                           required minlength="8">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass" type="button"
                                                data-target="#newPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div id="strengthBar" class="password-strength bg-secondary mt-1" style="width:0"></div>
                                <small id="strengthText" class="text-muted"></small>
                            </div>

                            <div class="form-group">
                                <label>Confirmar nueva contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="confirmPassword"
                                           class="form-control" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary toggle-pass" type="button"
                                                data-target="#confirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small id="matchMsg" class="text-muted"></small>
                            </div>

                            <div class="callout callout-info">
                                <h6><i class="fas fa-info-circle mr-1"></i>Recomendaciones:</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Mínimo 8 caracteres</li>
                                    <li>Combine letras, números y símbolos</li>
                                    <li>No use datos personales obvios</li>
                                </ul>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key mr-1"></i> Cambiar contraseña
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Abrir pestaña de seguridad si hubo error de contraseña --}}
@if($errors->has('password_actual') || $errors->has('password'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('tab-pass').click();
    });
</script>
@endif
@endsection

@push('scripts')
<script>
// Vista previa de avatar
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('La imagen no debe superar 2 MB.');
        return;
    }
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('avatarPreview').src = ev.target.result;
    };
    reader.readAsDataURL(file);
    document.getElementById('avatarForm').submit();
});

// Toggle mostrar/ocultar contraseña
document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', function() {
        const target = document.querySelector(this.dataset.target);
        const icon   = this.querySelector('i');
        if (target.type === 'password') {
            target.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            target.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

// Medidor de fortaleza de contraseña
document.getElementById('newPassword').addEventListener('input', function() {
    const val  = this.value;
    const bar  = document.getElementById('strengthBar');
    const txt  = document.getElementById('strengthText');
    let score  = 0;
    if (val.length >= 8)            score++;
    if (/[A-Z]/.test(val))          score++;
    if (/[0-9]/.test(val))          score++;
    if (/[^A-Za-z0-9]/.test(val))   score++;

    const levels = [
        { w: '25%',  cls: 'bg-danger',  label: 'Muy débil' },
        { w: '50%',  cls: 'bg-warning', label: 'Débil' },
        { w: '75%',  cls: 'bg-info',    label: 'Moderada' },
        { w: '100%', cls: 'bg-success', label: 'Fuerte' },
    ];
    if (val.length === 0) {
        bar.style.width = '0';
        bar.className = 'password-strength bg-secondary';
        txt.textContent = '';
        return;
    }
    const lvl = levels[Math.min(score - 1, 3)];
    bar.style.width = lvl.w;
    bar.className   = 'password-strength ' + lvl.cls;
    txt.textContent = lvl.label;
});

// Verificar coincidencia
['input'].forEach(evt => {
    ['newPassword','confirmPassword'].forEach(id => {
        document.getElementById(id).addEventListener(evt, checkMatch);
    });
});
function checkMatch() {
    const np = document.getElementById('newPassword').value;
    const cp = document.getElementById('confirmPassword').value;
    const msg = document.getElementById('matchMsg');
    if (!cp) { msg.textContent = ''; return; }
    if (np === cp) {
        msg.textContent = '✓ Las contraseñas coinciden';
        msg.className   = 'text-success';
    } else {
        msg.textContent = '✗ Las contraseñas no coinciden';
        msg.className   = 'text-danger';
    }
}
</script>
@endpush
