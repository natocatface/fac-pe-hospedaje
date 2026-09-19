<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumen_boletas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('correlativo')->comment('Correlativo diario del resumen');
            $table->date('fecha_generacion')->comment('Fecha de emisión de las boletas reportadas');
            $table->date('fecha_resumen')->comment('Fecha del envío del resumen');

            $table->enum('estado_sunat', [
                'no_emitido', 'pendiente', 'aceptado', 'aceptado_obs',
                'rechazado', 'excepcion',
            ])->default('no_emitido');
            $table->string('ticket_sunat', 50)->nullable();
            $table->string('codigo_sunat', 10)->nullable();
            $table->text('mensaje_sunat')->nullable();
            $table->string('xml_path', 400)->nullable();
            $table->string('cdr_path', 400)->nullable();
            $table->timestamp('fecha_envio_sunat')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('estado_sunat');
            $table->index('fecha_resumen');
        });

        // Pivote: boletas incluidas en cada resumen
        Schema::create('resumen_boletas_factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resumen_boletas_id')->constrained('resumen_boletas', 'id', 'rb_resumen_fk')
                  ->cascadeOnDelete();
            $table->foreignId('factura_id')->constrained('facturas', 'id', 'rb_factura_fk')
                  ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['resumen_boletas_id', 'factura_id'], 'rb_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resumen_boletas_factura');
        Schema::dropIfExists('resumen_boletas');
    }
};
