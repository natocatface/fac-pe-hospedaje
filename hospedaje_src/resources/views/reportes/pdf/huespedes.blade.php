<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #333; }
        .header { background: #1a2035; color: #fff; padding: 16px 20px; margin-bottom: 14px; }
        .header h1 { font-size: 16px; margin-bottom: 2px; }
        .header p  { font-size: 9px; color: #aab; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 14px; gap: 8px; }
        .meta-box { flex: 1; background: #f4f6f8; border-left: 3px solid #1a2035; padding: 8px 10px; }
        .meta-box label { font-size: 7.5px; text-transform: uppercase; color: #666; display: block; }
        .meta-box strong { font-size: 13px; color: #1a2035; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead th { background: #1a2035; color: #fff; padding: 5px 6px; text-align: left; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 3.5px 6px; border-bottom: 1px solid #eee; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; background: #e2e3e5; color: #383d41; }
        .footer { margin-top: 14px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Listado de Huéspedes</h1>
    <p>{{ $empresa }} &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="meta">
    <div class="meta-box">
        <label>Período filtrado</label>
        <strong>{{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}</strong>
    </div>
    <div class="meta-box">
        <label>Total huéspedes</label>
        <strong>{{ $huespedes->count() }}</strong>
    </div>
    <div class="meta-box">
        <label>Con reservas</label>
        <strong>{{ $huespedes->where('total_reservas', '>', 0)->count() }}</strong>
    </div>
    <div class="meta-box">
        <label>Fecha exportación</label>
        <strong>{{ now()->format('d/m/Y') }}</strong>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Apellidos, Nombre</th>
            <th>Doc.</th>
            <th>N° Documento</th>
            <th>Nacionalidad</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th class="text-center">Reservas</th>
        </tr>
    </thead>
    <tbody>
        @forelse($huespedes as $i => $h)
        <tr>
            <td style="color:#999">{{ $i + 1 }}</td>
            <td><strong>{{ $h->apellido }}</strong>, {{ $h->nombre }}</td>
            <td><span class="badge">{{ strtoupper($h->tipo_documento ?? 'DNI') }}</span></td>
            <td>{{ $h->num_documento }}</td>
            <td>{{ $h->nacionalidad ?? '—' }}</td>
            <td>{{ $h->telefono ?? '—' }}</td>
            <td style="font-size:8px">{{ $h->email ?? '—' }}</td>
            <td class="text-center">
                @if($h->total_reservas > 0)
                    <strong>{{ $h->total_reservas }}</strong>
                @else
                    <span style="color:#ccc">0</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:12px;color:#999">Sin registros</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    {{ $empresa }} &nbsp;|&nbsp; Listado de Huéspedes &nbsp;|&nbsp; Total: {{ $huespedes->count() }} registros
</div>

</body>
</html>
