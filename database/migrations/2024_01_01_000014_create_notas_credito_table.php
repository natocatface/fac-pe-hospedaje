<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id();
            // ── Referencia al comprobante afectado ─────────────────────
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();

            // ── Numeración interna y SUNAT ──────────────────────────────
            $table->string('numero_interno', 30)->unique();
            $table->string('serie_sunat', 4)->nullable()
                  ->comment('FC01 para notas a facturas, BC01 para notas a boletas');
            $table->unsignedInteger('correlativo_sunat')->nullable();
            $table->string('tipo_doc_sunat', 2)->default('07')->comment('07 = Nota de Crédito');

            // ── Motivo de la nota (catálogo SUNAT 09) ───────────────────
            $table->string('codigo_motivo', 2)
                  ->comment('01=Anulación, 02=Anulación por error en RUC, 03=Corrección descripción, '
                          . '04=Descuento global, 05=Descuento por ítem, 06=Devolución total, '
                          . '07=Devolución por ítem, 08=Bonificación, 09=Disminución valor, 10=Otros');
            $table->text('motivo_descripcion');

            // ── Importes ────────────────────────────────────────────────
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('igv',      12, 2)->default(0);
            $table->decimal('total',    12, 2)->default(0);

            // ── Estado SUNAT ────────────────────────────────────────────
            $table->enum('estado_sunat', [
                'no_emitido', 'pendiente', 'aceptado', 'aceptado_obs',
                'rechazado', 'excepcion', 'baja',
            ])->default('no_emitido');
            $table->string('codigo_sunat', 10)->nullable();
            $table->text('mensaje_sunat')->nullable();
            $table->text('notas_sunat')->nullable();
            $table->string('hash_cpe', 256)->nullable();
            $table->text('qr_data')->nullable();
            $table->string('xml_path', 400)->nullable();
            $table->string('cdr_path', 400)->nullable();
            $table->timestamp('fecha_envio_sunat')->nullable();
            $table->date('fecha_emision');

            // ── Auditoría ───────────────────────────────────────────────
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['serie_sunat', 'correlativo_sunat'], 'idx_nc_sunat_numero');
            $table->index('estado_sunat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_credito');
    }
};
