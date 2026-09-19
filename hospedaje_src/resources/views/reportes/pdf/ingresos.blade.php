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
        .summary { display: flex; justify-content: space-between; margin-bottom: 14px; gap: 8px; }
        .summary-box { flex: 1; background: #f4f6f8; border-left: 3px solid #1a2035; padding: 8px 10px; }
        .summary-box label { font-size: 7.5px; text-transform: uppercase; color: #666; display: block; }
        .summary-box strong { font-size: 13px; color: #1a2035; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead th { background: #1a2035; color: #fff; padding: 5px 6px; text-align: left; }
        thead th.text-right { text-align: right; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody td { padding: 3.5px 6px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; }
        .badge-success  { background: #d4edda; color: #155724; }
        .badge-danger   { background: #f8d7da; color: #721c24; }
        .badge-warning  { background: #fff3cd; color: #856404; }
        .badge-secondary{ background: #e2e3e5; color: #383d41; }
        tfoot td { background: #1a2035; color: #fff; padding: 5px 6px; font-weight: bold; }
        .footer { margin-top: 14px; font-size: 8px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Ingresos</h1>
    <p>{{ $empresa }} &nbsp;·&nbsp; Generado el {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="summary">
    <div class="summary-box">
        <label>Período</label>
        <strong>{{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}</strong>
    </div>
    <div class="summary-box">
        <label>Total facturas</label>
        <strong>{{ $facturas->count() }}</strong>
    </div>
    <div class="summary-box">
        <label>Total IGV</label>
        <strong>{{ $moneda }} {{ number_format($totalIGV, 2) }}</strong>
    </div>
    <div class="summary-box">
        <label>Total ingresos</label>
        <strong>{{ $moneda }} {{ number_format($totalIngresos, 2) }}</strong>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>N° Factura</th>
            <th>Fecha</th>
            <th>Huésped</th>
            <th>Habitación</th>
            <th class="text-right">Subtotal</th>
            <th class="text-right">IGV</th>
            <th class="text-right">Total</th>
            <th class="text-center">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($facturas as $f)
        @php
            $estadoBadge = match($f->estado ?? 'emitida') {
                'pagada'  => 'badge-success',
                'anulada' => 'badge-danger',
                'parcial' => 'badge-warning',
                default   => 'badge-secondary',
            };
        @endphp
        <tr>
            <td><strong>{{ $f->numero }}</strong></td>
            <td>{{ $f->fecha_emision->format('d/m/Y') }}</td>
            <td>{{ $f->huesped->nombre_completo ?? '—' }}</td>
            <td>Hab. {{ $f->reserva->habitacion->numero ?? '—' }}</td>
            <td class="text-right">{{ $moneda }} {{ number_format($f->subtotal, 2) }}</td>
            <td class="text-right">{{ $moneda }} {{ number_format($f->igv, 2) }}</td>
            <td class="text-right"><strong>{{ $moneda }} {{ number_format($f->total, 2) }}</strong></td>
            <td class="text-center">
                <span class="badge {{ $estadoBadge }}">{{ ucfirst($f->estado ?? 'emitida') }}</span>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:12px;color:#999">Sin registros en el período</td></tr>
        @endforelse
    </tbody>
    @if($facturas->count())
    <tfoot>
        <tr>
            <td colspan="4">TOTALES</td>
            <td class="text-right">{{ $moneda }} {{ number_format($facturas->sum('subtotal'), 2) }}</td>
            <td class="text-right">{{ $moneda }} {{ number_format($totalIGV, 2) }}</td>
            <td class="text-right">{{ $moneda }} {{ number_format($totalIngresos, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">
    {{ $empresa }} &nbsp;|&nbsp; Reporte de Ingresos &nbsp;|&nbsp; {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
</div>

</body>
</html>
