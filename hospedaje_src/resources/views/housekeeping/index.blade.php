@extends('layouts.app')

@section('title', 'Housekeeping')
@section('page-title', 'Panel de Housekeeping')

@section('breadcrumb')
    <li class="breadcrumb-item active">Housekeeping</li>
@endsection

@push('styles')
<style>
    .hk-card {
        border-radius: .5rem;
        border: 2px solid #dee2e6;
        transition: box-shadow .2s, transform .2s;
        cursor: pointer;
    }
    .hk-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.15); transform: translateY(-2px); }
    .hk-card.limpia    { border-color: #28a745; }
    .hk-card.sucia     { border-color: #dc3545; }
    .hk-card.en_limpieza { border-color: #ffc107; }
    .hk-card.inspeccion  { border-color: #17a2b8; }
    .hk-estado-dot {
        width: 12px; height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 4px;
    }
    .dot-limpia      { background: #28a745; }
    .dot-sucia       { background: #dc3545; }
    .dot-en_limpieza { background: #ffc107; }
    .dot-inspeccion  { background: #17a2b8; }
    .floor-header {
        background: #1a2035;
        color: #fff;
        padding: .5rem 1rem;
        border-radius: .375rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    .hk-badge {
        font-size: .78rem;
        padding: .25em .65em;
        border-radius: .375rem;
    }
    .spinner-overlay {
        position: fixed; inset: 0;
        background: rgba(0,0,0,.35);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999;
    }
    .resumen-stat { border-radius: .5rem; padding: 1rem 1.5rem; }
</style>
@endpush

@section('content')

{{-- Resumen de estados --}}
<div class="row mb-3">
    <div class="col-6 col-sm-3 mb-2">
        <div class="resumen-stat bg-success text-white d-flex align-items-center justify-content-between">
            <div>
                <div class="h4 mb-0 font-weight-bold">{{ $resumen['limpia'] }}</div>
                <div class="small">Limpias</div>
            </div>
            <i class="fas fa-check-circle fa-2x opacity-50"></i>
        </div>
    </div>
    <div class="col-6 col-sm-3 mb-2">
        <div class="resumen-stat bg-danger text-white d-flex align-items-center justify-content-between">
            <div>
                <div class="h4 mb-0 font-weight-bold">{{ $resumen['sucia'] }}</div>
                <div class="small">Sucias</div>
            </div>
            <i class="fas fa-broom fa-2x opacity-50"></i>
        </div>
    </div>
    <div class="col-6 col-sm-3 mb-2">
        <div class="resumen-stat bg-warning text-white d-flex align-items-center justify-content-between">
            <div>
                <div class="h4 mb-0 font-weight-bold">{{ $resumen['en_limpieza'] }}</div>
                <div class="small">En limpieza</div>
            </div>
            <i class="fas fa-soap fa-2x opacity-50"></i>
        </div>
    </div>
    <div class="col-6 col-sm-3 mb-2">
        <div class="resumen-stat bg-info text-white d-flex align-items-center justify-content-between">
            <div>
                <div class="h4 mb-0 font-weight-bold">{{ $resumen['inspeccion'] }}</div>
                <div class="small">Inspección</div>
            </div>
            <i class="fas fa-search fa-2x opacity-50"></i>
        </div>
    </div>
</div>

{{-- Barra de acciones --}}
<div class="card card-outline card-secondary mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap gap-2">
                {{-- Filtro por estado --}}
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary filter-btn active" data-filter="all">
                        Todos
                    </button>
                    <button type="button" class="btn btn-outline-success filter-btn" data-filter="limpia">
                        Limpias
                    </button>
                    <button type="button" class="btn btn-outline-danger filter-btn" data-filter="sucia">
                        Sucias
                    </button>
                    <button type="button" class="btn btn-outline-warning filter-btn" data-filter="en_limpieza">
                        En limpieza
                    </button>
                    <button type="button" class="btn btn-outline-info filter-btn" data-filter="inspeccion">
                        Inspección
                    </button>
                </div>
            </div>
            <div>
                <form action="{{ route('housekeeping.marcar-sucias') }}" method="POST"
                      onsubmit="return confirm('¿Marcar todas las hab. disponibles como SUCIAS?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-broom mr-1"></i> Marcar todas sucias
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Grid por piso --}}
@forelse($habitaciones as $piso => $habs)
<div class="mb-4 floor-section">
    <div class="floor-header">
        <i class="fas fa-layer-group mr-2"></i>
        {{ $piso == 0 ? 'Planta Baja' : 'Piso ' . $piso }}
        <span class="badge badge-light text-dark ml-2">{{ $habs->count() }} habitaciones</span>
    </div>
    <div class="row">
        @foreach($habs as $hab)
        @php
            $estado = $hab->estado_limpieza ?? 'limpia';
            $labels = [
                'limpia'      => ['Limpia',      'success', 'fa-check-circle'],
                'sucia'       => ['Sucia',        'danger',  'fa-exclamation-circle'],
                'en_limpieza' => ['En limpieza',  'warning', 'fa-soap'],
                'inspeccion'  => ['Inspección',   'info',    'fa-search'],
            ];
            [$label, $color, $icon] = $labels[$estado] ?? ['Limpia', 'success', 'fa-check-circle'];
            $ocupada = $hab->reservas->isNotEmpty();
        @endphp
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3 hk-room-col" data-estado="{{ $estado }}">
            <div class="hk-card {{ $estado }} p-2 text-center"
                 onclick="openModal({{ $hab->id }}, '{{ $hab->numero }}', '{{ $estado }}')"
                 title="Habitación {{ $hab->numero }} — click para cambiar estado">
                <div class="font-weight-bold" style="font-size:1.1rem">{{ $hab->numero }}</div>
                <small class="text-muted d-block mb-1">{{ $hab->tipoHabitacion->nombre ?? '' }}</small>
                <span class="badge badge-{{ $color }} hk-badge">
                    <i class="fas {{ $icon }} mr-1"></i>{{ $label }}
                </span>
                @if($ocupada)
                <div class="mt-1">
                    <span class="badge badge-dark" style="font-size:.7rem">
                        <i class="fas fa-user mr-1"></i>Ocupada
                    </span>
                </div>
                @endif
                @if($hab->limpieza_actualizado)
                <div class="mt-1 text-muted" style="font-size:.68rem">
                    {{ \Carbon\Carbon::parse($hab->limpieza_actualizado)->format('H:i') }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@empty
<div class="alert alert-info">No hay habitaciones registradas.</div>
@endforelse

{{-- Modal para cambiar estado --}}
<div class="modal fade" id="hkModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-broom mr-2"></i>
                    Habitación <span id="modalNumero"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="hkForm" method="POST">
                @csrf @method('POST')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Estado de limpieza</label>
                        <select name="estado_limpieza" id="modalEstado" class="form-control">
                            <option value="limpia">✅ Limpia</option>
                            <option value="en_limpieza">🔄 En limpieza</option>
                            <option value="sucia">🔴 Sucia</option>
                            <option value="inspeccion">🔍 Inspección</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notas <small class="text-muted">(opcional)</small></label>
                        <textarea name="notas" class="form-control" rows="2"
                                  placeholder="Observaciones..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="spinner-overlay d-none" id="spinnerOverlay">
    <div class="spinner-border text-light" style="width:3rem;height:3rem" role="status"></div>
</div>
@endsection

@push('scripts')
<script>
const updateUrl = '/housekeeping/{id}/estado';

function openModal(id, numero, estado) {
    document.getElementById('modalNumero').textContent = numero;
    document.getElementById('modalEstado').value = estado;
    document.getElementById('hkForm').action = updateUrl.replace('{id}', id);
    document.querySelector('#hkForm textarea[name="notas"]').value = '';
    $('#hkModal').modal('show');
}

// Envío AJAX
document.getElementById('hkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form    = this;
    const spinner = document.getElementById('spinnerOverlay');
    spinner.classList.remove('d-none');

    const formData = new FormData(form);
    // Override method for Laravel
    formData.set('_method', 'POST');

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                   'Accept': 'application/json' },
        body: new URLSearchParams(formData),
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            $('#hkModal').modal('hide');
            location.reload();
        }
    })
    .catch(() => { form.submit(); })
    .finally(() => spinner.classList.add('d-none'));
});

// Filtro por estado
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.hk-room-col').forEach(col => {
            col.style.display = (filter === 'all' || col.dataset.estado === filter) ? '' : 'none';
        });
    });
});
</script>
@endpush
