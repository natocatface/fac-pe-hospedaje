<?php

namespace App\Http\Controllers;

use App\Models\Habitacion;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    public function index(Request $request)
    {
        $habitaciones = Habitacion::with(['tipoHabitacion', 'reservas' => function ($q) {
            $q->whereIn('estado', ['checkin'])->latest();
        }])
        ->where('activa', true)
        ->orderBy('piso')
        ->orderBy('numero')
        ->get()
        ->groupBy('piso');

        $resumen = [
            'limpia'      => Habitacion::where('activa', true)->where('estado_limpieza', 'limpia')->count(),
            'sucia'       => Habitacion::where('activa', true)->where('estado_limpieza', 'sucia')->count(),
            'en_limpieza' => Habitacion::where('activa', true)->where('estado_limpieza', 'en_limpieza')->count(),
            'inspeccion'  => Habitacion::where('activa', true)->where('estado_limpieza', 'inspeccion')->count(),
            'total'       => Habitacion::where('activa', true)->count(),
        ];

        return view('housekeeping.index', compact('habitaciones', 'resumen'));
    }

    public function actualizarEstado(Request $request, Habitacion $habitacion)
    {
        $request->validate([
            'estado_limpieza' => 'required|in:limpia,en_limpieza,sucia,inspeccion',
            'notas'           => 'nullable|string|max:300',
        ]);

        $habitacion->update([
            'estado_limpieza'     => $request->estado_limpieza,
            'limpieza_actualizado'=> now(),
            'limpieza_notas'      => $request->notas,
            'limpieza_user_id'    => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'    => true,
                'estado'=> $request->estado_limpieza,
                'hora'  => now()->format('H:i'),
            ]);
        }

        return back()->with('success', "Hab. {$habitacion->numero} actualizada a: {$request->estado_limpieza}");
    }

    /** Marcar todas las habitaciones disponibles como 'sucias' al hacer checkout */
    public function marcarTodasSucias()
    {
        Habitacion::where('activa', true)
            ->where('estado', 'disponible')
            ->update([
                'estado_limpieza'     => 'sucia',
                'limpieza_actualizado'=> now(),
                'limpieza_user_id'    => auth()->id(),
            ]);

        return back()->with('success', 'Todas las habitaciones disponibles marcadas como sucias.');
    }
}
