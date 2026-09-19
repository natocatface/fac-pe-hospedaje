<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ strtoupper($factura->tipo_comprobante) }}: {{ $factura->numero_sunat ?? $factura->numero }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #007bff; color: white; padding: 6px 8px; text-align: left; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totales td { font-weight: bold; }
        .totales .total { font-size: 14px; background: #f8f9fa; }
        .footer { margin-top: 20px; text-align: center; color: #888; font-size: 10px; }
        /* ── Encabezado electrónico ── */
        .doc-header { width:100%; border-bottom:2px solid #007bff; padding-bottom:10px; margin-bottom:16px; }
        .doc-header td { border:none; padding:0; vertical-align:top; }
        .empresa-block { font-size:12px; }
        .comprobante-box { border:1px solid #007bff; border-radius:4px; padding:8px 12px; text-align:center; min-width:180px; }
        .comprobante-box .tipo { font-size:14px; font-weight:bold; color:#007bff; text-transform:uppercase; }
        .comprobante-box .serie { font-size:13px; font-weight:bold; }
        .comprobante-box .ruc-box { font-size:10px; color:#555; margin-top:4px; }
        /* ── Banda electrónica pie ── */
        .sunat-footer { border:1px solid #28a745; border-radius:4px; padding:10px;
                        background:#f6fff8; margin-top:12px; }
        .sunat-footer table { margin:0; }
        .sunat-footer td { border:none; padding:2px 4px; font-size:10px; }
        .hash-code { font-family:monospace; font-size:9px; word-break:break-all; color:#555; }
        .badge-green { background:#28a745; color:#fff; border-radius:3px;
                       padding:1px 5px; font-size:9px; font-weight:bold; }
    </style>
</head>
<body>

{{-- ══ ENCABEZADO ══════════════════════════════════════════════════════════ --}}
<table class="doc-header">
    <tr>
        {{-- Datos del emisor --}}
        <td class="empresa-block" style="width:60%">
            @php $cfg = \App\Models\Configuracion::pluck('valor','clave'); @endphp
            <strong style="font-size:15px">{{ $cfg['empresa_nombre'] ?? config('app.name') }}</strong><br>
            @if(!empty($cfg['empresa_razon_social'])) {{ $cfg['empresa_razon_social'] }}<br> @endif
            @if(!empty($cfg['empresa_ruc'])) <span style="color:#555">RUC: {{ $cfg['empresa_ruc'] }}</span><br> @endif
            @if(!empty($cfg['empresa_direccion'])) {{ $cfg['empresa_direccion'] }}<br> @endif
            @if(!empty($cfg['empresa_telefono'])) Tel: {{ $cfg['empresa_telefono'] }} @endif
            @if(!empty($cfg['empresa_email'])) &nbsp;·&nbsp; {{ $cfg['empresa_email'] }} @endif
        </td>
        {{-- Caja del comprobante --}}
        <td style="width:40%; text-align:right">
            <div class="comprobante-box" style="display:inline-block">
                <div class="tipo">
                    @if($factura->tipo_comprobante === 'factura') FACTURA ELECTRÓNICA
                    @elseif($factura->tipo_comprobante === 'boleta') BOLETA DE VENTA ELECTRÓNICA
                    @else RECIBO DE PAGO
                    @endif
                </div>
                @if($factura->numero_sunat)
                    <div class="serie" style="font-size:16px">{{ $factura->numero_sunat }}</div>
                @else
                    <div class="serie">{{ $factura->numero }}</div>
                @endif
                @if(!empty($cfg['empresa_ruc']))
                    <div class="ruc-box">RUC {{ $cfg['empresa_ruc'] }}</div>
                @endif
                <div style="font-size:10px; color:#888; margin-top:2px">
                    Fecha: {{ $factura->fecha_emision->format('d/m/Y') }}
                </div>
            </div>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td width="50%"><strong>Cliente:</strong> {{ $factura->huesped->nombre_completo }}</td>
        <td><strong>Documento:</strong> {{ $factura->huesped->tipo_documento }}: {{ $factura->huesped->num_documento }}</td>
    </tr>
    @if($factura->razon_social)
    <tr>
        <td><strong>Empresa:</strong> {{ $factura->razon_social }}</td>
        <td><strong>RUC:</strong> {{ $factura->ruc_cliente }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>Reserva:</strong> {{ $factura->reserva->codigo }}</td>
        <td><strong>Habitación:</strong> {{ $factura->reserva->habitacion->numero }} — {{ $factura->reserva->habitacion->tipoHabitacion->nombre }}</td>
    </tr>
    <tr>
        <td><strong>Check-in:</strong> {{ $factura->reserva->fecha_entrada->format('d/m/Y') }}</td>
        <td><strong>Check-out:</strong> {{ $factura->reserva->fecha_salida->format('d/m/Y') }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr><th>Descripción</th><th class="text-right">P. Unit.</th><th class="text-center">Cant.</th><th class="text-right">Subtotal</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Alojamiento ({{ $factura->reserva->num_noches }} noches)</td>
            <td class="text-right">S/ {{ number_format($factura->reserva->precio_noche, 2) }}</td>
            <td class="text-center">{{ $factura->reserva->num_noches }}</td>
            <td class="text-right">S/ {{ number_format($factura->reserva->subtotal, 2) }}</td>
        </tr>
        @foreach($factura->reserva->cargosAdicionales as $c)
        <tr>
            <td>{{ $c->concepto }}</td>
            <td class="text-right">S/ {{ number_format($c->precio_unitario, 2) }}</td>
            <td class="text-center">{{ $c->cantidad }}</td>
            <td class="text-right">S/ {{ number_format($c->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot class="totales">
        <tr><td colspan="3" class="text-right">Subtotal:</td><td class="text-right">S/ {{ number_format($factura->subtotal, 2) }}</td></tr>
        @if($factura->descuento > 0)
        <tr><td colspan="3" class="text-right">Descuento:</td><td class="text-right">- S/ {{ number_format($factura->descuento, 2) }}</td></tr>
        @endif
        @if($factura->igv > 0)
        <tr><td colspan="3" class="text-right">IGV (18%):</td><td class="text-right">S/ {{ number_format($factura->igv, 2) }}</td></tr>
        @endif
        <tr class="total"><td colspan="3" class="text-right" style="font-size:14px">TOTAL A PAGAR:</td>
            <td class="text-right" style="font-size:14px;color:#007bff">S/ {{ number_format($factura->total, 2) }}</td></tr>
    </tfoot>
</table>

@if($factura->pagos->count() > 0)
<strong>Pagos Recibidos:</strong>
<table>
    <thead><tr><th>Fecha</th><th>Método</th><th>Referencia</th><th class="text-right">Monto</th></tr></thead>
    <tbody>
        @foreach($factura->pagos as $p)
        <tr>
            <td>{{ $p->fecha_pago->format('d/m/Y') }}</td>
            <td>{{ ucfirst(str_replace('_',' ',$p->metodo_pago)) }}</td>
            <td>{{ $p->referencia ?? '—' }}</td>
            <td class="text-right">S/ {{ number_format($p->monto, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ══ PIE SUNAT (solo comprobantes electrónicos aceptados) ════════════════ --}}
@if($factura->aplica_electronica)
<div class="sunat-footer">
    <table>
        <tr>
            {{-- QR code --}}
            <td style="width:90px; text-align:center; vertical-align:middle">
                @if(!empty($qrBase64))
                    <img src="data:image/png;base64,{{ $qrBase64 }}" width="80" height="80" alt="QR">
                @else
                    <div style="width:80px;height:80px;border:1px dashed #ccc;display:flex;align-items:center;justify-content:center;font-size:9px;color:#aaa">
                        Sin QR
                    </div>
                @endif
                <div style="font-size:8px;color:#666;margin-top:2px">Verificar en SUNAT</div>
            </td>
            {{-- Datos electrónicos --}}
            <td style="vertical-align:top; padding-left:10px">
                <div style="margin-bottom:4px">
                    @if($factura->aceptado_sunat)
                        <span class="badge-green">✓ ACEPTADO POR SUNAT</span>
                    @elseif($factura->estado_sunat === 'aceptado_obs')
                        <span class="badge-green">✓ ACEPTADO CON OBSERVACIONES</span>
                    @elseif($factura->numero_sunat)
                        <span style="background:#ffc107;color:#333;border-radius:3px;padding:1px 5px;font-size:9px;font-weight:bold">PENDIENTE DE VALIDACIÓN</span>
                    @else
                        <span style="background:#6c757d;color:#fff;border-radius:3px;padding:1px 5px;font-size:9px;font-weight:bold">COMPROBANTE INTERNO</span>
                    @endif
                </div>
                @if($factura->numero_sunat)
                <div><strong style="font-size:10px">Número SUNAT:</strong> {{ $factura->numero_sunat }}</div>
                @endif
                @if($factura->fecha_envio_sunat)
                <div style="color:#555">Enviado: {{ $factura->fecha_envio_sunat->format('d/m/Y H:i') }}</div>
                @endif
                @if($factura->hash_cpe)
                <div style="margin-top:3px"><strong style="font-size:9px">Hash CPE:</strong><br>
                    <span class="hash-code">{{ $factura->hash_cpe }}</span>
                </div>
                @endif
                @if($factura->qr_data && !$factura->aceptado_sunat)
                <div style="margin-top:3px; font-size:9px; color:#555">
                    Representación impresa del comprobante electrónico
                </div>
                @endif
                @if(!empty($cfg['empresa_ruc']))
                <div style="font-size:9px; color:#555; margin-top:3px">
                    Autorizado mediante Resolución de Superintendencia N° 097-2012/SUNAT
                </div>
                @endif
            </td>
        </tr>
    </table>
</div>
@endif

@if(!empty($cfg['facturacion_pie_factura']))
<div style="margin-top:8px; text-align:center; font-size:10px; color:#666; font-style:italic">
    {{ $cfg['facturacion_pie_factura'] }}
</div>
@endif

<div class="footer">
    <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }} — {{ $cfg['empresa_nombre'] ?? config('app.name') }}</p>
    @if($factura->aplica_electronica && !$factura->aceptado_sunat)
    <p style="color:#dc3545; font-size:9px">* Este documento es una representación impresa del comprobante electrónico. La validez tributaria está sujeta a la aceptación por SUNAT.</p>
    @endif
</div>
</body>
</html>
