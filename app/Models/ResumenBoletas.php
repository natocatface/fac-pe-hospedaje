<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResumenBoletas extends Model
{
    protected $table = 'resumen_boletas';

    protected $fillable = [
        'correlativo', 'fecha_generacion', 'fecha_resumen',
        'estado_sunat', 'ticket_sunat', 'codigo_sunat', 'mensaje_sunat',
        'xml_path', 'cdr_path', 'fecha_envio_sunat', 'user_id',
    ];

    protected $casts = [
        'fecha_generacion'   => 'date',
        'fecha_resumen'      => 'date',
        'fecha_envio_sunat'  => 'datetime',
    ];

    /* ── Relaciones ───────────────────────────────────────────────── */

    public function facturas(): BelongsToMany
    {
        return $this->belongsToMany(Factura::class, 'resumen_boletas_factura')->withTimestamps();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function getCodigoArchivoAttribute(): string
    {
        return 'RC-' . $this->fecha_resumen->format('Ymd') . '-' . str_pad((string) $this->correlativo, 4, '0', STR_PAD_LEFT);
    }

    public function getAceptadoSunatAttribute(): bool
    {
        return in_array($this->estado_sunat, ['aceptado', 'aceptado_obs']);
    }

    public function getEstadoSunatBadgeAttribute(): string
    {
        return match ($this->estado_sunat ?? 'no_emitido') {
            'aceptado'     => 'success',
            'aceptado_obs' => 'warning',
            'rechazado'    => 'danger',
            'excepcion'    => 'danger',
            'pendiente'    => 'info',
            default        => 'light',
        };
    }

    public function getEstadoSunatLabelAttribute(): string
    {
        return match ($this->estado_sunat ?? 'no_emitido') {
            'no_emitido'   => 'No emitido',
            'pendiente'    => 'Enviado (sin CDR)',
            'aceptado'     => 'Aceptado',
            'aceptado_obs' => 'Aceptado c/obs.',
            'rechazado'    => 'Rechazado',
            'excepcion'    => 'Error',
            default        => '—',
        };
    }

    public static function siguienteCorrelativo(\DateTimeInterface $fecha): int
    {
        $max = static::whereDate('fecha_resumen', $fecha->format('Y-m-d'))->max('correlativo');
        return (int) ($max ?? 0) + 1;
    }
}
