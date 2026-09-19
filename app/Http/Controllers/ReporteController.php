<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Factura;
use App\Models\Pago;
use App\Models\Habitacion;
use App\Models\Huesped;
use App\Models\Configuracion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index()
    {
        return view('reportes.index');
    }

    public function ocupacion(Request $request)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : Carbon::now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : Carbon::now()->endOfMonth();

        $totalHabitaciones = Habitacion::where('activa', true)->count();

        // Ocupación por día
        $ocupacionDiaria = [];
        $cursor = $desde->copy();
        while ($cursor <= $hasta) {
            $ocupadas = Reserva::where('estado', 'checkin')
                ->whereDate('fecha_checkin', '<=', $cursor)
                ->whereDate('fecha_checkout', '>', $cursor)
                ->count();
            $ocupacionDiaria[] = [
                'fecha'      => $cursor->format('Y-m-d'),
                'label'      => $cursor->format('d/m'),
                'ocupadas'   => $ocupadas,
                'porcentaje' => $totalHabitaciones > 0 ? round(($ocupadas / $totalHabitaciones) * 100, 1) : 0,
            ];
            $cursor->addDay();
        }

        // Resumen
        $totalReservas     = Reserva::whereBetween('fecha_entrada', [$desde, $hasta])->count();
        $reservasCanceladas= Reserva::where('estado', 'cancelada')
            ->whereBetween('fecha_entrada', [$desde, $hasta])->count();
        $promOcupacion     = collect($ocupacionDiaria)->avg('porcentaje');

        return view('reportes.ocupacion', compact(
            'desde', 'hasta', 'ocupacionDiaria', 'totalReservas',
            'reservasCanceladas', 'promOcupacion', 'totalHabitaciones'
        ));
    }

    public function ingresos(Request $request)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : Carbon::now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : Carbon::now()->endOfMonth();

        // Ingresos por método de pago
        $ingresosPorMetodo = Pago::whereBetween('fecha_pago', [$desde, $hasta])
            ->selectRaw('metodo_pago, SUM(monto) as total, COUNT(*) as cantidad')
            ->groupBy('metodo_pago')
            ->get();

        // Ingresos diarios
        $ingresosDiarios = Pago::whereBetween('fecha_pago', [$desde, $hasta])
            ->selectRaw('DATE(fecha_pago) as dia, SUM(monto) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        // Facturas del período
        $facturas = Factura::with(['huesped', 'reserva.habitacion'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->latest()
            ->get();

        $totalIngresos = $ingresosDiarios->sum('total');
        $totalIGV      = Factura::whereBetween('fecha_emision', [$desde, $hasta])->sum('igv');

        return view('reportes.ingresos', compact(
            'desde', 'hasta', 'ingresosPorMetodo', 'ingresosDiarios',
            'facturas', 'totalIngresos', 'totalIGV'
        ));
    }

    public function huespedes(Request $request)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : Carbon::now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : Carbon::now()->endOfMonth();

        // Huéspedes por nacionalidad
        $porNacionalidad = \App\Models\Huesped::select('nacionalidad')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('nacionalidad')
            ->groupBy('nacionalidad')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        // Huéspedes frecuentes
        $frecuentes = \App\Models\Huesped::withCount(['reservas as total_estancias' => function ($q) {
                $q->where('estado', 'checkout');
            }])
            ->having('total_estancias', '>', 1)
            ->orderByDesc('total_estancias')
            ->take(10)
            ->get();

        // Nuevos registros en el período
        $nuevosHuespedes = \App\Models\Huesped::whereBetween('created_at', [$desde, $hasta])->count();

        return view('reportes.huespedes', compact(
            'desde', 'hasta', 'porNacionalidad', 'frecuentes', 'nuevosHuespedes'
        ));
    }

    // ══════════════════════════════════════════════════════════════════
    //  EXPORTACIONES
    // ══════════════════════════════════════════════════════════════════

    public function exportarOcupacion(Request $request)
    {
        $desde = $request->filled('desde') ? Carbon::parse($request->desde) : Carbon::now()->startOfMonth();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->hasta) : Carbon::now()->endOfMonth();
        $formato = $request->get('formato', 'pdf');

        $totalHabitaciones = Habitacion::where('activa', true)->count();
        $ocupacionDiaria   = [];
        $cursor = $desde->copy();
        while ($cursor <= $hasta) {
            $ocupadas = Reserva::whereIn('estado', ['checkin', 'checkout'])
                ->whereDate('fecha_entrada', '<=', $cursor)
                ->whereDate('fecha_salida', '>', $cursor)
                ->count();
            $ocupacionDiaria[] = [
                'fecha'      => $cursor->format('d/m/Y'),
                'ocupadas'   => $ocupadas,
                'disponibles'=> max(0, $totalHabitaciones - $ocupadas),
                'porcentaje' => $totalHabitaciones > 0 ? round(($ocupadas / $totalHabitaciones) * 100, 1) : 0,
            ];
            $cursor->addDay();
        }
        $promOcupacion  = collect($ocupacionDiaria)->avg('porcentaje');
        $totalReservas  = Reserva::whereBetween('fecha_entrada', [$desde, $hasta])->count();
        $empresa        = Configuracion::get('empresa_nombre', 'Sistema Hospedaje');

        if ($formato === 'excel') {
            return $this->exportarExcel(
                headers: ['Fecha', 'Hab. Ocupadas', 'Hab. Disponibles', '% Ocupación'],
                rows: array_map(fn($r) => [$r['fecha'], $r['ocupadas'], $r['disponibles'], $r['porcentaje'] . '%'], $ocupacionDiaria),
                filename: 'reporte_ocupacion_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd')
            );
        }

        $pdf = Pdf::loadView('reportes.pdf.ocupacion', compact(
            'desde', 'hasta', 'ocupacionDiaria', 'promOcupacion',
            'totalReservas', 'totalHabitaciones', 'empresa'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('reporte_ocupacion_' . $desde->format('Ymd') . '.pdf');
    }

    public function exportarIngresos(Request $request)
    {
        $desde   = $request->filled('desde') ? Carbon::parse($request->desde) : Carbon::now()->startOfMonth();
        $hasta   = $request->filled('hasta') ? Carbon::parse($request->hasta) : Carbon::now()->endOfMonth();
        $formato = $request->get('formato', 'pdf');

        $facturas      = Factura::with(['huesped', 'reserva.habitacion'])
            ->whereBetween('fecha_emision', [$desde, $hasta])
            ->orderBy('fecha_emision')
            ->get();
        $totalIngresos = $facturas->sum('total');
        $totalIGV      = $facturas->sum('igv');
        $empresa       = Configuracion::get('empresa_nombre', 'Sistema Hospedaje');
        $moneda        = Configuracion::get('facturacion_moneda_simbolo', 'S/');

        if ($formato === 'excel') {
            return $this->exportarExcel(
                headers: ['N° Factura', 'Fecha', 'Huésped', 'Habitación', 'Subtotal', 'IGV', 'Total', 'Estado'],
                rows: $facturas->map(fn($f) => [
                    $f->numero,
                    $f->fecha_emision->format('d/m/Y'),
                    $f->huesped->nombre_completo ?? '-',
                    'Hab. ' . ($f->reserva->habitacion->numero ?? '-'),
                    $moneda . ' ' . number_format($f->subtotal, 2),
                    $moneda . ' ' . number_format($f->igv, 2),
                    $moneda . ' ' . number_format($f->total, 2),
                    ucfirst($f->estado),
                ])->toArray(),
                filename: 'reporte_ingresos_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd')
            );
        }

        $pdf = Pdf::loadView('reportes.pdf.ingresos', compact(
            'desde', 'hasta', 'facturas', 'totalIngresos', 'totalIGV', 'empresa', 'moneda'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('reporte_ingresos_' . $desde->format('Ymd') . '.pdf');
    }

    public function exportarHuespedes(Request $request)
    {
        $desde   = $request->filled('desde') ? Carbon::parse($request->desde) : Carbon::now()->startOfMonth();
        $hasta   = $request->filled('hasta') ? Carbon::parse($request->hasta) : Carbon::now()->endOfMonth();
        $formato = $request->get('formato', 'pdf');

        $huespedes = Huesped::withCount(['reservas as total_reservas'])
            ->orderBy('apellido')->get();
        $empresa   = Configuracion::get('empresa_nombre', 'Sistema Hospedaje');

        if ($formato === 'excel') {
            return $this->exportarExcel(
                headers: ['Apellidos', 'Nombre', 'Tipo Doc.', 'N° Documento', 'Nacionalidad', 'Teléfono', 'Email', 'Reservas'],
                rows: $huespedes->map(fn($h) => [
                    $h->apellido, $h->nombre, $h->tipo_documento,
                    $h->num_documento, $h->nacionalidad ?? '-',
                    $h->telefono ?? '-', $h->email ?? '-', $h->total_reservas,
                ])->toArray(),
                filename: 'reporte_huespedes_' . now()->format('Ymd')
            );
        }

        $pdf = Pdf::loadView('reportes.pdf.huespedes', compact(
            'desde', 'hasta', 'huespedes', 'empresa'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('reporte_huespedes_' . now()->format('Ymd') . '.pdf');
    }

    /** Genera descarga CSV compatible con Excel */
    private function exportarExcel(array $headers, array $rows, string $filename): \Illuminate\Http\Response
    {
        $bom  = "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
        $csv  = $bom;
        $csv .= implode(';', array_map(fn($h) => '"' . $h . '"', $headers)) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
        ]);
    }
}
