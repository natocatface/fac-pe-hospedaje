<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaCredito extends Model
{
    protected $table = 'notas_credito';

    protected $fillable = [
        'factura_id', 'numero_interno',
        'serie_sunat', 'correlativo_sunat', 'tipo_doc_sunat',
        'codigo_motivo', 'motivo_descripcion',
        'subtotal', 'igv', 'total',
        'estado_sunat', 'codigo_sunat', 'mensaje_sunat', 'notas_sunat',
        'hash_cpe', 'qr_data', 'xml_path', 'cdr_path',
        'fecha_envio_sunat', 'fecha_emision', 'user_id',
    ];

    protected $casts = [
        'fecha_emision'      => 'date',
        'subtotal'           => 'decimal:2',
        'igv'                => 'decimal:2',
        'total'              => 'decimal:2',
        'fecha_envio_sunat'  => 'datetime',
    ];

    // ── Catálogo de motivos SUNAT (Catálogo 09) ─────────────────────────
    public const MOTIVOS = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '04' => 'Descuento global',
        '05' => 'Descuento por ítem',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '08' => 'Bonificación',
        '09' => 'Disminución en el valor',
        '10' => 'Otros conceptos',
    ];

    /* ── Relaciones ───────────────────────────────────────────────── */

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* ── Helpers SUNAT ───────────────────────────────────────────── */

    public function getNumeroSunatAttribute(): ?string
    {
        if (!$this->serie_sunat || !$this->correlativo_sunat) return null;
        return $this->serie_sunat . '-' . $this->correlativo_sunat;
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
            'baja'         => 'secondary',
            default        => 'light',
        };
    }

    public function getEstadoSunatLabelAttribute(): string
    {
        return match ($this->estado_sunat ?? 'no_emitido') {
            'no_emitido'   => 'No emitida',
            'pendiente'    => 'Enviando…',
            'aceptado'     => 'Aceptada',
            'aceptado_obs' => 'Aceptada c/obs.',
            'rechazado'    => 'Rechazada',
            'excepcion'    => 'Error',
            'baja'         => 'Dada de baja',
            default        => '—',
        };
    }

    public function getMotivoDescripcionCortoAttribute(): string
    {
        return self::MOTIVOS[$this->codigo_motivo] ?? $this->motivo_descripcion;
    }

    public static function generarNumeroInterno(): string
    {
        $año    = date('Y');
        $ultimo = static::whereYear('created_at', $año)->max('id') ?? 0;
        return "NC-{$año}-" . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }
}
