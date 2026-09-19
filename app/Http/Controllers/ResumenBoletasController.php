<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\ResumenBoletas;
use App\Services\FacturacionElectronicaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResumenBoletasController extends Controller
{
    public function index(Request $request)
    {
        $query = ResumenBoletas::with('usuario')->withCount('facturas');

        if ($request->filled('estado_sunat')) {
            $query->where('estado_sunat', $request->estado_sunat);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_resumen', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_resumen', '<=', $request->fecha_hasta);
        }

        $resumenes = $query->latest()->paginate(15);
        return view('resumen-boletas.index', compact('resumenes'));
    }

    public function create(Request $request)
    {
        // Fecha por defecto: ayer (lo más común para resumen diario)
        $fechaSugerida = $request->get('fecha', now()->subDay()->toDateString());

        // Boletas elegibles: tipo boleta, ya tienen serie/correlativo, aún no incluidas en otro resumen
        $boletasElegibles = Factura::with(['huesped', 'reserva.habitacion'])
            ->where('tipo_comprobante', 'boleta')
            ->whereNotNull('serie_sunat')
            ->whereNotNull('correlativo_sunat')
            ->whereDate('fecha_emision', $fechaSugerida)
            ->whereDoesntHave('resumenesBoletas')
            ->orderBy('correlativo_sunat')
            ->get();

        return view('resumen-boletas.create', compact('boletasElegibles', 'fechaSugerida'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha_resumen'  => 'required|date',
            'factura_ids'    => 'required|array|min:1',
            'factura_ids.*'  => 'integer|exists:facturas,id',
        ]);

        $facturas = Factura::whereIn('id', $data['factura_ids'])
            ->where('tipo_comprobante', 'boleta')
            ->whereNotNull('serie_sunat')
            ->get();

        if ($facturas->isEmpty()) {
            return back()->with('error', 'Ninguna boleta seleccionada es válida.');
        }

        $fechaGen = $facturas->min('fecha_emision');

        $resumen = DB::transaction(function () use ($data, $facturas, $fechaGen) {
            $fechaResumen = \Carbon\Carbon::parse($data['fecha_resumen']);
            $r = ResumenBoletas::create([
                'correlativo'      => ResumenBoletas::siguienteCorrelativo($fechaResumen),
                'fecha_generacion' => $fechaGen,
                'fecha_resumen'    => $fechaResumen,
                'estado_sunat'     => 'no_emitido',
                'user_id'          => auth()->id(),
            ]);
            $r->facturas()->sync($facturas->pluck('id'));
            return $r;
        });

        return redirect()->route('resumen-boletas.show', $resumen)
            ->with('success', "Resumen #{$resumen->codigo_archivo} creado con {$facturas->count()} boletas.");
    }

    public function show(ResumenBoletas $resumenBoletas)
    {
        $resumenBoletas->load(['facturas.huesped', 'usuario']);
        return view('resumen-boletas.show', compact('resumenBoletas'));
    }

    public function emitir(ResumenBoletas $resumenBoletas)
    {
        if ($resumenBoletas->aceptado_sunat) {
            return back()->with('info', 'Este resumen ya fue aceptado por SUNAT.');
        }

        $svc    = new FacturacionElectronicaService();
        $result = $svc->emitirResumenDiario($resumenBoletas);

        if ($result['success']) {
            return back()->with('success', "✓ Resumen aceptado por SUNAT.");
        }

        return back()->with('error', 'SUNAT: ' . ($result['mensaje'] ?? 'Error desconocido'));
    }

    public function consultarTicket(ResumenBoletas $resumenBoletas)
    {
        if (empty($resumenBoletas->ticket_sunat)) {
            return back()->with('error', 'Sin ticket. Primero emita el resumen a SUNAT.');
        }

        $svc    = new FacturacionElectronicaService();
        $result = $svc->consultarTicket($resumenBoletas);

        if ($result['success']) {
            return back()->with('success', "Ticket consultado — Estado: {$result['estado']}");
        }
        return back()->with('error', $result['mensaje'] ?? 'Sin respuesta de SUNAT.');
    }

    public function descargarXml(ResumenBoletas $resumenBoletas)
    {
        $content = (new FacturacionElectronicaService())->getContenidoArchivo($resumenBoletas->xml_path);
        if (!$content) return back()->with('error', 'XML no disponible.');
        return response($content, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename={$resumenBoletas->codigo_archivo}.xml",
        ]);
    }

    public function descargarCdr(ResumenBoletas $resumenBoletas)
    {
        $content = (new FacturacionElectronicaService())->getContenidoArchivo($resumenBoletas->cdr_path);
        if (!$content) return back()->with('error', 'CDR no disponible.');
        return response($content, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=CDR-{$resumenBoletas->codigo_archivo}.zip",
        ]);
    }
}
