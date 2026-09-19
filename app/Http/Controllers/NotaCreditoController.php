<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\NotaCredito;
use App\Services\FacturacionElectronicaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotaCreditoController extends Controller
{
    public function index(Request $request)
    {
        $query = NotaCredito::with(['factura.huesped', 'usuario']);

        if ($request->filled('estado_sunat')) {
            $query->where('estado_sunat', $request->estado_sunat);
        }
        if ($request->filled('codigo_motivo')) {
            $query->where('codigo_motivo', $request->codigo_motivo);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        $notas = $query->latest()->paginate(15);
        return view('notas-credito.index', compact('notas'));
    }

    /**
     * Muestra el formulario para crear una nota de crédito asociada a una factura.
     */
    public function create(Request $request)
    {
        $factura = Factura::with('huesped')
            ->findOrFail($request->factura_id);

        if (!$factura->aceptado_sunat) {
            return redirect()->route('facturas.show', $factura)
                ->with('error', 'Solo se pueden emitir notas de crédito sobre comprobantes ya aceptados por SUNAT.');
        }

        return view('notas-credito.create', compact('factura'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'factura_id'         => 'required|exists:facturas,id',
            'codigo_motivo'      => 'required|in:' . implode(',', array_keys(NotaCredito::MOTIVOS)),
            'motivo_descripcion' => 'required|string|max:500',
            'monto_credito'      => 'required|numeric|min:0.01',
            'aplicar_total'      => 'nullable|boolean',
        ]);

        $factura = Factura::findOrFail($data['factura_id']);

        if (!$factura->aceptado_sunat) {
            return back()->with('error', 'Solo se pueden emitir notas de crédito sobre comprobantes aceptados por SUNAT.');
        }

        $igvPct = (float) \App\Models\Configuracion::get('facturacion_igv', '18');

        // Si aplica al total, usar el total de la factura; si no, usar el monto indicado
        $total    = $request->boolean('aplicar_total') ? (float) $factura->total : (float) $data['monto_credito'];
        $subtotal = round($total / (1 + $igvPct / 100), 2);
        $igv      = round($total - $subtotal, 2);

        $nc = DB::transaction(function () use ($data, $factura, $total, $subtotal, $igv) {
            return NotaCredito::create([
                'factura_id'         => $factura->id,
                'numero_interno'     => NotaCredito::generarNumeroInterno(),
                'tipo_doc_sunat'     => '07',
                'codigo_motivo'      => $data['codigo_motivo'],
                'motivo_descripcion' => $data['motivo_descripcion'],
                'subtotal'           => $subtotal,
                'igv'                => $igv,
                'total'              => $total,
                'estado_sunat'       => 'no_emitido',
                'fecha_emision'      => now()->toDateString(),
                'user_id'            => auth()->id(),
            ]);
        });

        return redirect()->route('notas-credito.show', $nc)
            ->with('success', "Nota de crédito {$nc->numero_interno} creada. Ya puede emitirla a SUNAT.");
    }

    public function show(NotaCredito $notaCredito)
    {
        $notaCredito->load(['factura.huesped', 'factura.reserva.habitacion', 'usuario']);
        return view('notas-credito.show', compact('notaCredito'));
    }

    public function emitir(NotaCredito $notaCredito)
    {
        if ($notaCredito->aceptado_sunat) {
            return back()->with('info', 'Esta nota de crédito ya fue aceptada por SUNAT.');
        }

        $svc    = new FacturacionElectronicaService();
        $result = $svc->emitirNotaCredito($notaCredito);

        if ($result['success']) {
            return back()->with('success', "✓ Aceptada por SUNAT — {$notaCredito->refresh()->numero_sunat}");
        }

        return back()->with('error', 'SUNAT: ' . ($result['mensaje'] ?? 'Error desconocido'));
    }

    public function descargarXml(NotaCredito $notaCredito)
    {
        $svc     = new FacturacionElectronicaService();
        $content = $svc->getContenidoArchivo($notaCredito->xml_path);

        if (!$content) return back()->with('error', 'XML no disponible.');

        $filename = ($notaCredito->numero_sunat ? str_replace('-', '_', $notaCredito->numero_sunat) : $notaCredito->numero_interno) . '.xml';

        return response($content, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function descargarCdr(NotaCredito $notaCredito)
    {
        $svc     = new FacturacionElectronicaService();
        $content = $svc->getContenidoArchivo($notaCredito->cdr_path);

        if (!$content) return back()->with('error', 'CDR no disponible.');

        $filename = 'CDR-' . ($notaCredito->numero_sunat ?? $notaCredito->numero_interno) . '.zip';

        return response($content, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
