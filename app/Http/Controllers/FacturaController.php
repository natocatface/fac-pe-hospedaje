<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Pago;
use App\Models\Reserva;
use App\Services\FacturacionElectronicaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $query = Factura::with(['huesped', 'reserva.habitacion']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('estado_sunat')) {
            $query->where('estado_sunat', $request->estado_sunat);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }
        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('numero', 'like', "%{$b}%")
                  ->orWhereHas('huesped', fn($qh) =>
                      $qh->where('apellido', 'like', "%{$b}%"));
            });
        }

        $facturas = $query->latest()->paginate(15);
        return view('facturas.index', compact('facturas'));
    }

    public function create(Request $request)
    {
        $reserva = null;
        if ($request->filled('reserva_id')) {
            $reserva = Reserva::with(['huesped', 'habitacion.tipoHabitacion', 'cargosAdicionales'])
                ->findOrFail($request->reserva_id);

            if ($reserva->factura) {
                return redirect()->route('facturas.show', $reserva->factura)
                    ->with('info', 'Esta reserva ya tiene una factura generada.');
            }
        }
        return view('facturas.create', compact('reserva'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reserva_id'      => 'required|exists:reservas,id',
            'tipo_comprobante'=> 'required|in:boleta,factura,recibo',
            'igv_aplicado'    => 'boolean',
            'ruc_cliente'     => 'nullable|string|max:11',
            'razon_social'    => 'nullable|string|max:150',
            'observaciones'   => 'nullable|string',
        ]);

        $reserva  = Reserva::with(['cargosAdicionales'])->findOrFail($data['reserva_id']);
        $subtotal = $reserva->total + $reserva->cargosAdicionales->sum('subtotal');
        $igv      = $request->boolean('igv_aplicado') ? round($subtotal * 0.18, 2) : 0;
        $total    = $subtotal + $igv;

        $factura = DB::transaction(function () use ($data, $reserva, $subtotal, $igv, $total) {
            $f = Factura::create([
                'numero'          => Factura::generarNumero(),
                'reserva_id'      => $reserva->id,
                'huesped_id'      => $reserva->huesped_id,
                'user_id'         => auth()->id(),
                'fecha_emision'   => now(),
                'subtotal'        => $subtotal,
                'igv'             => $igv,
                'descuento'       => $reserva->descuento,
                'total'           => $total,
                'estado'          => 'pendiente',
                'tipo_comprobante'=> $data['tipo_comprobante'],
                'ruc_cliente'     => $data['ruc_cliente'] ?? null,
                'razon_social'    => $data['razon_social'] ?? null,
                'observaciones'   => $data['observaciones'] ?? null,
            ]);

            // Asociar cargos adicionales a la factura
            $reserva->cargosAdicionales()
                ->whereNull('factura_id')
                ->update(['factura_id' => $f->id]);

            return $f;
        });

        return redirect()->route('facturas.show', $factura)
            ->with('success', "Factura {$factura->numero} generada correctamente.");
    }

    public function show(Factura $factura)
    {
        $factura->load(['huesped', 'reserva.habitacion.tipoHabitacion', 'pagos.usuario',
                        'reserva.cargosAdicionales']);

        $qrBase64 = null;
        if ($factura->qr_data && $factura->aceptado_sunat) {
            $qrBase64 = (new FacturacionElectronicaService())->generarQrBase64($factura->qr_data);
        }

        return view('facturas.show', compact('factura', 'qrBase64'));
    }

    public function registrarPago(Request $request, Factura $factura)
    {
        $data = $request->validate([
            'monto'       => 'required|numeric|min:0.01|max:' . $factura->saldo_pendiente,
            'metodo_pago' => 'required|in:efectivo,tarjeta_credito,tarjeta_debito,transferencia,yape,plin,otro',
            'referencia'  => 'nullable|string|max:100',
            'fecha_pago'  => 'required|date',
            'observaciones'=> 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $factura) {
            Pago::create(array_merge($data, [
                'factura_id' => $factura->id,
                'user_id'    => auth()->id(),
            ]));

            // Verificar si la factura queda saldada
            $factura->refresh();
            if ($factura->saldo_pendiente <= 0) {
                $factura->update(['estado' => 'pagada']);
            }
        });

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function anular(Factura $factura)
    {
        if ($factura->estado === 'pagada') {
            return back()->with('error', 'No se puede anular una factura pagada.');
        }
        $factura->update(['estado' => 'anulada']);
        return back()->with('success', 'Factura anulada.');
    }

    public function pdf(Factura $factura)
    {
        $factura->load(['huesped', 'reserva.habitacion.tipoHabitacion', 'pagos',
                        'reserva.cargosAdicionales']);

        $qrBase64 = null;
        if ($factura->qr_data) {
            $qrBase64 = (new FacturacionElectronicaService())->generarQrBase64($factura->qr_data);
        }

        $pdf = Pdf::loadView('facturas.pdf', compact('factura', 'qrBase64'))
                  ->setPaper('a4', 'portrait');
        return $pdf->download("Comprobante-{$factura->numero_sunat ?? $factura->numero}.pdf");
    }

    // ════════════════════════════════════════════════════════════════════
    //  FACTURACIÓN ELECTRÓNICA SUNAT
    // ════════════════════════════════════════════════════════════════════

    /**
     * Asigna serie/correlativo SUNAT sin enviar (pre-numeración).
     */
    public function prepararSunat(Factura $factura)
    {
        if (!$factura->aplica_electronica) {
            return back()->with('error', 'Solo boletas y facturas aplican a facturación electrónica.');
        }
        if ($factura->serie_sunat) {
            return back()->with('info', "Ya tiene número SUNAT: {$factura->numero_sunat}");
        }

        $svc = new FacturacionElectronicaService();
        $svc->prepararNumeroSunat($factura);

        return back()->with('success', "Número SUNAT asignado: {$factura->refresh()->numero_sunat}");
    }

    /**
     * Emite el comprobante electrónico a SUNAT.
     */
    public function emitirSunat(Factura $factura)
    {
        if (!$factura->aplica_electronica) {
            return back()->with('error', 'Solo boletas y facturas aplican a facturación electrónica.');
        }
        if ($factura->estado === 'anulada') {
            return back()->with('error', 'No se puede emitir una factura anulada.');
        }
        if ($factura->aceptado_sunat) {
            return back()->with('info', "Este comprobante ya fue aceptado por SUNAT ({$factura->numero_sunat}).");
        }

        $svc    = new FacturacionElectronicaService();
        $result = $svc->emitir($factura);

        if ($result['success']) {
            $msg = "✓ Aceptado por SUNAT — {$factura->refresh()->numero_sunat}";
            if (!empty($result['notas'])) {
                $msg .= ' (con observaciones)';
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', 'SUNAT: ' . ($result['mensaje'] ?? 'Error desconocido'));
    }

    /**
     * Descarga el XML firmado del comprobante.
     */
    public function descargarXml(Factura $factura)
    {
        $svc     = new FacturacionElectronicaService();
        $content = $svc->getXmlContent($factura);

        if (!$content) {
            return back()->with('error', 'XML no disponible para este comprobante.');
        }

        $filename = $factura->numero_sunat
            ? str_replace('-', '_', $factura->numero_sunat) . '.xml'
            : $factura->numero . '.xml';

        return response($content, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Descarga el CDR (Constancia de Recepción) de SUNAT.
     */
    public function descargarCdr(Factura $factura)
    {
        $svc     = new FacturacionElectronicaService();
        $content = $svc->getCdrContent($factura);

        if (!$content) {
            return back()->with('error', 'CDR no disponible. El comprobante debe ser enviado primero.');
        }

        $filename = 'CDR-' . ($factura->numero_sunat ?? $factura->numero) . '.zip';

        return response($content, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
