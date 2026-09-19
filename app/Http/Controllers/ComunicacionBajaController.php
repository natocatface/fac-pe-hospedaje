<?php

namespace App\Http\Controllers;

use App\Models\ComunicacionBaja;
use App\Models\Factura;
use App\Services\FacturacionElectronicaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComunicacionBajaController extends Controller
{
    public function index(Request $request)
    {
        $query = ComunicacionBaja::with('usuario')->withCount('facturas');

        if ($request->filled('estado_sunat')) {
            $query->where('estado_sunat', $request->estado_sunat);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_comunicacion', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_comunicacion', '<=', $request->fecha_hasta);
        }

        $comunicaciones = $query->latest()->paginate(15);
        return view('comunicaciones-baja.index', compact('comunicaciones'));
    }

    public function create()
    {
        // Facturas elegibles para baja: aceptadas por SUNAT, tipo FACTURA, sin baja previa
        $facturasElegibles = Factura::with(['huesped', 'reserva.habitacion'])
            ->where('tipo_comprobante', 'factura')
            ->whereIn('estado_sunat', ['aceptado', 'aceptado_obs'])
            ->whereDoesntHave('comunicacionesBaja')
            ->latest('fecha_emision')
            ->limit(100)
            ->get();

        return view('comunicaciones-baja.create', compact('facturasElegibles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_comunicacion' => 'required|date',
            'motivo'             => 'required|string|min:5|max:255',
            'factura_ids'        => 'required|array|min:1',
            'factura_ids.*'      => 'integer|exists:facturas,id',
        ]);

        $facturas = Factura::whereIn('id', $data['factura_ids'])
            ->whereIn('estado_sunat', ['aceptado', 'aceptado_obs'])
            ->get();

        if ($facturas->isEmpty()) {
            return back()->with('error', 'Ninguna factura seleccionada es válida para baja.');
        }

        // Fecha de generación = la mínima fecha de emisión de los comprobantes
        $fechaGen = $facturas->min('fecha_emision');

        $baja = DB::transaction(function () use ($data, $facturas, $fechaGen) {
            $fechaCom = \Carbon\Carbon::parse($data['fecha_comunicacion']);
            $cb = ComunicacionBaja::create([
                'correlativo'        => ComunicacionBaja::siguienteCorrelativo($fechaCom),
                'fecha_generacion'   => $fechaGen,
                'fecha_comunicacion' => $fechaCom,
                'motivo'             => $data['motivo'],
                'estado_sunat'       => 'no_emitido',
                'user_id'            => auth()->id(),
            ]);
            $cb->facturas()->sync($facturas->pluck('id'));
            return $cb;
        });

        return redirect()->route('comunicaciones-baja.show', $baja)
            ->with('success', "Comunicación de baja #{$baja->codigo_archivo} creada.");
    }

    public function show(ComunicacionBaja $comunicacionBaja)
    {
        $comunicacionBaja->load(['facturas.huesped', 'usuario']);
        return view('comunicaciones-baja.show', compact('comunicacionBaja'));
    }

    public function emitir(ComunicacionBaja $comunicacionBaja)
    {
        if ($comunicacionBaja->aceptado_sunat) {
            return back()->with('info', 'Esta comunicación ya fue aceptada por SUNAT.');
        }

        $svc    = new FacturacionElectronicaService();
        $result = $svc->emitirComunicacionBaja($comunicacionBaja);

        if ($result['success']) {
            // Marcar las facturas como dadas de baja
            $comunicacionBaja->facturas()->update(['estado_sunat' => 'baja']);
            return back()->with('success', "✓ Comunicación de baja aceptada por SUNAT.");
        }

        return back()->with('error', 'SUNAT: ' . ($result['mensaje'] ?? 'Error desconocido'));
    }

    public function consultarTicket(ComunicacionBaja $comunicacionBaja)
    {
        if (empty($comunicacionBaja->ticket_sunat)) {
            return back()->with('error', 'Sin ticket. Primero emita la comunicación a SUNAT.');
        }

        $svc    = new FacturacionElectronicaService();
        $result = $svc->consultarTicket($comunicacionBaja);

        if ($result['success']) {
            $comunicacionBaja->facturas()->update(['estado_sunat' => 'baja']);
            return back()->with('success', "Ticket consultado — Estado: {$result['estado']}");
        }
        return back()->with('error', $result['mensaje'] ?? 'Sin respuesta de SUNAT.');
    }

    public function descargarXml(ComunicacionBaja $comunicacionBaja)
    {
        $content = (new FacturacionElectronicaService())->getContenidoArchivo($comunicacionBaja->xml_path);
        if (!$content) return back()->with('error', 'XML no disponible.');
        return response($content, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename={$comunicacionBaja->codigo_archivo}.xml",
        ]);
    }

    public function descargarCdr(ComunicacionBaja $comunicacionBaja)
    {
        $content = (new FacturacionElectronicaService())->getContenidoArchivo($comunicacionBaja->cdr_path);
        if (!$content) return back()->with('error', 'CDR no disponible.');
        return response($content, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=CDR-{$comunicacionBaja->codigo_archivo}.zip",
        ]);
    }
}
