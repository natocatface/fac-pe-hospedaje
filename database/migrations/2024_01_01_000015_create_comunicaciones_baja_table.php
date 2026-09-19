<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de la comunicación
        Schema::create('comunicaciones_baja', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('correlativo')->comment('Correlativo diario, ej. 1 para la primera del día');
            $table->date('fecha_generacion')->comment('Fecha de emisión de los comprobantes que se anulan');
            $table->date('fecha_comunicacion')->comment('Fecha en que se comunica la baja a SUNAT');
            $table->text('motivo')->comment('Motivo común para todos los comprobantes anulados');

            // Estado SUNAT
            $table->enum('estado_sunat', [
                'no_emitido', 'pendiente', 'aceptado', 'aceptado_obs',
                'rechazado', 'excepcion',
            ])->default('no_emitido');
            $table->string('ticket_sunat', 50)->nullable()->comment('Ticket devuelto por SUNAT al enviar');
            $table->string('codigo_sunat', 10)->nullable();
            $table->text('mensaje_sunat')->nullable();
            $table->string('xml_path', 400)->nullable();
            $table->string('cdr_path', 400)->nullable();
            $table->timestamp('fecha_envio_sunat')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('estado_sunat');
            $table->index('fecha_comunicacion');
        });

        // Tabla pivot: facturas incluidas en la baja
        Schema::create('comunicacion_baja_factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunicacion_baja_id')->constrained('comunicaciones_baja', 'id', 'cb_baja_fk')
                  ->cascadeOnDelete();
            $table->foreignId('factura_id')->constrained('facturas', 'id', 'cb_factura_fk')
                  ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['comunicacion_baja_id', 'factura_id'], 'cb_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicacion_baja_factura');
        Schema::dropIfExists('comunicaciones_baja');
    }
};
