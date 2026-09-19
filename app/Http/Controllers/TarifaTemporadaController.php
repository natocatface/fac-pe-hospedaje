<?php

namespace App\Http\Controllers;

use App\Models\TarifaTemporada;
use App\Models\TipoHabitacion;
use Illuminate\Http\Request;

class TarifaTemporadaController extends Controller
{
    public function index()
    {
        $tarifas = TarifaTemporada::with('tipoHabitacion')
            ->orderByDesc('activa')
            ->orderBy('fecha_inicio')
            ->get();

        $tipos = TipoHabitacion::where('activo', true)->get();

        return view('tarifas.index', compact('tarifas', 'tipos'));
    }

    public function create()
    {
        $tipos = TipoHabitacion::where('activo', true)->orderBy('nombre')->get();
        return view('tarifas.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:100',
            'tipo_habitacion_id'  => 'nullable|exists:tipo_habitaciones,id',
            'fecha_inicio'        => 'required|date',
            'fecha_fin'           => 'required|date|after_or_equal:fecha_inicio',
            'precio_noche'        => 'required|numeric|min:0',
            'tipo_precio'         => 'required|in:fijo,porcentaje',
            'descripcion'         => 'nullable|string|max:300',
            'prioridad'           => 'nullable|integer|min:0|max:10',
            'activa'              => 'boolean',
        ], [
            'nombre.required'      => 'El nombre de la tarifa es obligatorio.',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
            'precio_noche.min'     => 'El precio debe ser mayor o igual a 0.',
        ]);

        $data['activa']    = $request->boolean('activa', true);
        $data['prioridad'] = $data['prioridad'] ?? 0;

        TarifaTemporada::create($data);

        return redirect()->route('tarifas.index')
            ->with('success', "Tarifa «{$data['nombre']}» creada correctamente.");
    }

    public function edit(TarifaTemporada $tarifa)
    {
        $tipos = TipoHabitacion::where('activo', true)->orderBy('nombre')->get();
        return view('tarifas.edit', compact('tarifa', 'tipos'));
    }

    public function update(Request $request, TarifaTemporada $tarifa)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:100',
            'tipo_habitacion_id'  => 'nullable|exists:tipo_habitaciones,id',
            'fecha_inicio'        => 'required|date',
            'fecha_fin'           => 'required|date|after_or_equal:fecha_inicio',
            'precio_noche'        => 'required|numeric|min:0',
            'tipo_precio'         => 'required|in:fijo,porcentaje',
            'descripcion'         => 'nullable|string|max:300',
            'prioridad'           => 'nullable|integer|min:0|max:10',
        ]);

        $data['activa']    = $request->boolean('activa');
        $data['prioridad'] = $data['prioridad'] ?? 0;

        $tarifa->update($data);

        return redirect()->route('tarifas.index')
            ->with('success', "Tarifa «{$tarifa->nombre}» actualizada correctamente.");
    }

    public function destroy(TarifaTemporada $tarifa)
    {
        $nombre = $tarifa->nombre;
        $tarifa->delete();
        return back()->with('success', "Tarifa «{$nombre}» eliminada.");
    }

    public function toggle(TarifaTemporada $tarifa)
    {
        $tarifa->update(['activa' => !$tarifa->activa]);
        $estado = $tarifa->activa ? 'activada' : 'desactivada';
        return back()->with('success', "Tarifa «{$tarifa->nombre}» {$estado}.");
    }
}
