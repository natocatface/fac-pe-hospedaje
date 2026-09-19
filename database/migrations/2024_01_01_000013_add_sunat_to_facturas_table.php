<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // ── Numeración SUNAT (F001-1, B001-1, etc.) ─────────────────
            $table->string('serie_sunat', 4)->nullable()->after('numero')
                  ->comment('Serie SUNAT: F001=factura, B001=boleta');
            $table->unsignedInteger('correlativo_sunat')->nullable()->after('serie_sunat')
                  ->comment('Correlativo numérico dentro de la serie');

            // ── Tipo de documento SUNAT ──────────────────────────────────
            $table->string('tipo_doc_sunat', 2)->nullable()->after('correlativo_sunat')
                  ->comment('01=Factura, 03=Boleta, 07=Nota Crédito, 08=Nota Débito');

            // ── Estado ante SUNAT ────────────────────────────────────────
            $table->enum('estado_sunat', [
                'no_emitido',   // No se ha enviado a SUNAT
                'pendiente',    // En proceso de envío
                'aceptado',     // Aceptado por SUNAT (código 0)
                'aceptado_obs', // Aceptado con observaciones
                'rechazado',    // Rechazado por SUNAT
                'excepcion',    // Error de comunicación/formato
                'baja',         // Dado de baja
            ])->default('no_emitido')->after('tipo_doc_sunat');

            // ── Respuesta SUNAT ──────────────────────────────────────────
            $table->string('codigo_sunat', 10)->nullable()->after('estado_sunat')
                  ->comment('Código de respuesta SUNAT (0=ok, 2xxx=observación, 4xxx/5xxx=error)');
            $table->text('mensaje_sunat')->nullable()->after('codigo_sunat');
            $table->text('notas_sunat')->nullable()->after('mensaje_sunat')
                  ->comment('Observaciones del CDR');

            // ── Datos técnicos del CPE ───────────────────────────────────
            $table->string('hash_cpe', 256)->nullable()->after('notas_sunat')
                  ->comment('Hash SHA-1 del XML firmado');
            $table->text('qr_data')->nullable()->after('hash_cpe')
                  ->comment('Datos para el código QR del comprobante');

            // ── Rutas de archivos ────────────────────────────────────────
            $table->string('xml_path', 400)->nullable()->after('qr_data')
                  ->comment('Ruta al XML firmado en storage');
            $table->string('cdr_path', 400)->nullable()->after('xml_path')
                  ->comment('Ruta al CDR (Constancia de Recepción) en storage');

            // ── Timestamps ───────────────────────────────────────────────
            $table->timestamp('fecha_envio_sunat')->nullable()->after('cdr_path');

            // ── Índice para consultas por serie/correlativo ──────────────
            $table->index(['serie_sunat', 'correlativo_sunat'], 'idx_sunat_numero');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex('idx_sunat_numero');
            $table->dropColumn([
                'serie_sunat', 'correlativo_sunat', 'tipo_doc_sunat',
                'estado_sunat', 'codigo_sunat', 'mensaje_sunat', 'notas_sunat',
                'hash_cpe', 'qr_data', 'xml_path', 'cdr_path', 'fecha_envio_sunat',
            ]);
        });
    }
};
