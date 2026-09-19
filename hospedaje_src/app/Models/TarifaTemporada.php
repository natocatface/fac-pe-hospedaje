<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaTemporada extends Model
{
    protected $table = 'tarifas_temporada';

    protected $fillable = [
        'nombre', 'tipo_habitacion_id', 'fecha_inicio', 'fecha_fin',
        'precio_noche', 'tipo_precio', 'descripcion', 'activa', 'prioridad',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activa'       => 'boolean',
    ];

    public function tipoHabitacion(): BelongsTo
    {
        return $this->belongsTo(TipoHabitacion::class, 'tipo_habitacion_id');
    }

    /** Obtiene el precio aplicable para un tipo de habitación en un rango de fechas */
    public static function getPrecioAplicable(int $tipoHabitacionId, string $fechaEntrada, float $precioBase): float
    {
        $tarifa = static::where('activa', true)
            ->where(function ($q) use ($tipoHabitacionId) {
                $q->whereNull('tipo_habitacion_id')
                  ->orWhere('tipo_habitacion_id', $tipoHabitacionId);
            })
            ->where('fecha_inicio', '<=', $fechaEntrada)
            ->where('fecha_fin',    '>=', $fechaEntrada)
            ->orderByDesc('prioridad')
            ->orderBy('tipo_habitacion_id') // específico tiene prioridad
            ->first();

        if (!$tarifa) return $precioBase;

        return $tarifa->tipo_precio === 'porcentaje'
            ? round($precioBase * (1 + $tarifa->precio_noche / 100), 2)
            : $tarifa->precio_noche;
    }

    public function getEstadoAttribute(): string
    {
        $hoy = Carbon::today();
        if (!$this->activa)                   return 'inactiva';
        if ($this->fecha_fin->lt($hoy))       return 'vencida';
        if ($this->fecha_inicio->gt($hoy))    return 'proxima';
        return 'activa';
    }

    public function getEstadoBadgeAttribute(): string
    {
        return match($this->estado) {
            'activa'   => 'success',
            'proxima'  => 'info',
            'vencida'  => 'secondary',
            default    => 'light',
        };
    }
}
