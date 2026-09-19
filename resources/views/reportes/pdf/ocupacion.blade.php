<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        .header { background: #1a2035; color: #fff; padding: 16px 20px; margin-bottom: 16px; }
        .header h1 { font-size: 16px; margin-bottom: 2px; }
        .header p  { font-size: 9px; color: #aab; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 14px; padding: 0 2px; }
        .meta-box { background: #f4f6f8; border-left: 3px solid #1a2035; padding: 6px 10px; min-width: 120px; }
        .meta-box label { font-size: 8px; text-transform: uppercase; color: #666; display: block; }
        .meta-box strong { font-size: 13px; color: #1a2035; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead th { background: #1a2035; color: #fff; padding: 5px 7px; text-align: left; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 4px 7px; border-bottom: 1px solid #eee; }
        .bar-wrap { background: #e9ecef; border-radius: 3px; height: 8px; width: 100%; }
        .bar-fill { height: 8px; border-radius: 3px; }
        .footer { margin-top: 14px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger  { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Ocupación</h1>
    <p>{{ $empresa }} &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="meta">
    <div class="meta-box">
        <label>Período</label>
        <strong>{{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}</strong>
    </div>
    <div class="meta-box">
        <label>Total habitaciones</label>
        <strong>{{ $totalHabitaciones }}</strong>
    </div>
    <div class="meta-box">
        <label>Total reservas</label>
        <strong>{{ $totalReservas }}</strong>
    </div>
    <div class="meta-box">
        <label>Promedio ocupación</label>
        <strong>{{ number_format($promOcupacion, 1) }}%</strong>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Hab. Ocupadas</th>
            <th>Disponibles</th>
            <th>% Ocupación</th>
            <th style="width:120px">Barra</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ocupacionDiaria as $dia)
        @php
            $pct = $dia['porcentaje'];
            $color = $pct >= 80 ? '#28a745' : ($pct >= 50 ? '#ffc107' : '#dc3545');
            $disponibles = max(0, $totalHabitaciones - $dia['ocupadas']);
        @endphp
        <tr>
            <td>{{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m/Y') }}
                <small style="color:#888">({{ \Carbon\Carbon::parse($dia['fecha'])->isoFormat('ddd') }})</small>
            </td>
            <td>{{ $dia['ocupadas'] }}</td>
            <td>{{ $disponibles }}</td>
            <td>
                <span class="badge {{ $pct >= 80 ? 'badge-success' : ($pct >= 50 ? 'badge-warning' : 'badge-danger') }}">
                    {{ $pct }}%
                </span>
            </td>
            <td>
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:{{ $pct }}%; background:{{ $color }}"></div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    {{ $empresa }} &nbsp;|&nbsp; Reporte de Ocupación &nbsp;|&nbsp; {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
</div>

</body>
</html>
