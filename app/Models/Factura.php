<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'numero', 'reserva_id', 'huesped_id', 'user_id',
        'fecha_emision', 'subtotal', 'igv', 'descuento', 'total',
        'estado', 'tipo_comprobante', 'ruc_cliente', 'razon_social', 'observaciones',
        // Campos SUNAT
        'serie_sunat', 'correlativo_sunat', 'tipo_doc_sunat',
        'estado_sunat', 'codigo_sunat', 'mensaje_sunat', 'notas_sunat',
        'hash_cpe', 'qr_data', 'xml_path', 'cdr_path', 'fecha_envio_sunat',
    ];

    protected $casts = [
        'fecha_emision'      => 'date',
        'subtotal'           => 'decimal:2',
        'igv'                => 'decimal:2',
        'descuento'          => 'decimal:2',
        'total'              => 'decimal:2',
        'fecha_envio_sunat'  => 'datetime',
    ];

    /* ---- Relaciones ---- */

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function huesped(): BelongsTo
    {
        return $this->belongsTo(Huesped::class, 'huesped_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'factura_id');
    }

    public function notasCredito(): HasMany
    {
        return $this->hasMany(NotaCredito::class, 'factura_id');
    }

    public function comunicacionesBaja(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ComunicacionBaja::class, 'comunicacion_baja_factura')
                    ->withTimestamps();
    }

    public function resumenesBoletas(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ResumenBoletas::class, 'resumen_boletas_factura')
                    ->withTimestamps();
    }

    /* ---- Helpers ---- */

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'warning',
            'pagada'    => 'success',
            'anulada'   => 'danger',
            default     => 'secondary',
        };
    }

    public function getMontoPagadoAttribute(): float
    {
        return $this->pagos()->sum('monto');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->total - $this->monto_pagado;
    }

    public static function generarNumero(): string
    {
        $año    = date('Y');
        $ultimo = static::whereYear('created_at', $año)->max('id') ?? 0;
        return "FAC-{$año}-" . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }

    // ── Helpers SUNAT ──────────────────────────────────────────────────

    /** Número compuesto SUNAT: F001-1, B001-5, etc. */
    public function getNumeroSunatAttribute(): ?string
    {
        if (!$this->serie_sunat || !$this->correlativo_sunat) return null;
        return $this->serie_sunat . '-' . $this->correlativo_sunat;
    }

    /** Ya fue enviado y aceptado por SUNAT */
    public function getAceptadoSunatAttribute(): bool
    {
        return in_array($this->estado_sunat, ['aceptado', 'aceptado_obs']);
    }

    /** Badge color para el estado SUNAT */
    public function getEstadoSunatBadgeAttribute(): string
    {
        return match ($this->estado_sunat ?? 'no_emitido') {
            'aceptado'     => 'success',
            'aceptado_obs' => 'warning',
            'rechazado'    => 'danger',
            'excepcion'    => 'danger',
            'pendiente'    => 'info',
            'baja'         => 'secondary',
            default        => 'light',   // no_emitido
        };
    }

    /** Etiqueta para el estado SUNAT */
    public function getEstadoSunatLabelAttribute(): string
    {
        return match ($this->estado_sunat ?? 'no_emitido') {
            'no_emitido'   => 'No emitido',
            'pendiente'    => 'Enviando…',
            'aceptado'     => 'Aceptado',
            'aceptado_obs' => 'Aceptado c/obs.',
            'rechazado'    => 'Rechazado',
            'excepcion'    => 'Error',
            'baja'         => 'Dado de baja',
            default        => '—',
        };
    }

    /** ¿Este tipo de comprobante aplica a facturación electrónica? */
    public function getAplicaElectronicaAttribute(): bool
    {
        return in_array($this->tipo_comprobante, ['factura', 'boleta']);
    }
}
