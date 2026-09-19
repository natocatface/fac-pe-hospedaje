<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NuevosModulosSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTarifasTemporada();
        $this->seedHousekeeping();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  10 TARIFAS POR TEMPORADA — fechas pasadas, presentes y futuras
    // ══════════════════════════════════════════════════════════════════════
    private function seedTarifasTemporada(): void
    {
        // Obtener IDs de tipos de habitación
        $tipos = DB::table('tipo_habitaciones')->pluck('id', 'nombre');

        $tarifas = [
            // ── Temporadas pasadas (ya vencidas) ──────────────────────────
            [
                'nombre'             => 'Fiestas Patrias 2024',
                'tipo_habitacion_id' => null,                          // todos los tipos
                'fecha_inicio'       => '2024-07-26',
                'fecha_fin'          => '2024-07-30',
                'precio_noche'       => 20.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Incremento del 20% durante las fiestas patrias.',
                'activa'             => true,
                'prioridad'          => 5,
            ],
            [
                'nombre'             => 'Navidad 2024',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2024-12-24',
                'fecha_fin'          => '2024-12-27',
                'precio_noche'       => 30.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Incremento del 30% en temporada navideña.',
                'activa'             => true,
                'prioridad'          => 7,
            ],
            [
                'nombre'             => 'Año Nuevo 2025',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2024-12-31',
                'fecha_fin'          => '2025-01-02',
                'precio_noche'       => 40.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Tarifa especial de Año Nuevo.',
                'activa'             => true,
                'prioridad'          => 8,
            ],
            [
                'nombre'             => 'Semana Santa 2025',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2025-04-14',
                'fecha_fin'          => '2025-04-20',
                'precio_noche'       => 25.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Temporada alta de Semana Santa.',
                'activa'             => true,
                'prioridad'          => 6,
            ],

            // ── Temporada vigente (activa ahora) ──────────────────────────
            [
                'nombre'             => 'Temporada Alta Mayo-Junio 2026',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2026-05-01',
                'fecha_fin'          => '2026-06-30',
                'precio_noche'       => 15.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Incremento del 15% en la temporada alta de otoño.',
                'activa'             => true,
                'prioridad'          => 4,
            ],
            [
                'nombre'             => 'Oferta Habitación Simple — Mayo 2026',
                'tipo_habitacion_id' => $tipos['Simple'] ?? $tipos->first() ?? null,
                'fecha_inicio'       => '2026-05-01',
                'fecha_fin'          => '2026-05-31',
                'precio_noche'       => 55.00,
                'tipo_precio'        => 'fijo',
                'descripcion'        => 'Precio especial fijo para habitaciones simples durante mayo.',
                'activa'             => true,
                'prioridad'          => 9,
            ],

            // ── Próximas temporadas (futuras) ─────────────────────────────
            [
                'nombre'             => 'Fiestas Patrias 2026',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2026-07-24',
                'fecha_fin'          => '2026-07-30',
                'precio_noche'       => 25.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Incremento del 25% por fiestas patrias.',
                'activa'             => true,
                'prioridad'          => 6,
            ],
            [
                'nombre'             => 'Temporada Invierno 2026',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2026-07-01',
                'fecha_fin'          => '2026-08-31',
                'precio_noche'       => 10.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Leve incremento en temporada de invierno escolar.',
                'activa'             => true,
                'prioridad'          => 3,
            ],
            [
                'nombre'             => 'Suite Lujo — Temporada Alta',
                'tipo_habitacion_id' => $tipos['Suite'] ?? $tipos->last() ?? null,
                'fecha_inicio'       => '2026-06-01',
                'fecha_fin'          => '2026-08-31',
                'precio_noche'       => 35.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'Incremento exclusivo para suites en alta temporada.',
                'activa'             => true,
                'prioridad'          => 8,
            ],
            [
                'nombre'             => 'Navidad y Año Nuevo 2026',
                'tipo_habitacion_id' => null,
                'fecha_inicio'       => '2026-12-20',
                'fecha_fin'          => '2027-01-05',
                'precio_noche'       => 45.00,
                'tipo_precio'        => 'porcentaje',
                'descripcion'        => 'La tarifa más alta del año por temporada festiva.',
                'activa'             => true,
                'prioridad'          => 10,
            ],
        ];

        foreach ($tarifas as $tarifa) {
            $exists = DB::table('tarifas_temporada')
                ->where('nombre', $tarifa['nombre'])
                ->exists();

            if (!$exists) {
                DB::table('tarifas_temporada')->insert(
                    array_merge($tarifa, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }

        $this->command->info('✓ 10 tarifas por temporada insertadas.');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HOUSEKEEPING — estados de limpieza variados en habitaciones
    // ══════════════════════════════════════════════════════════════════════
    private function seedHousekeeping(): void
    {
        $adminId = DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        // Obtener todas las habitaciones activas
        $habitaciones = DB::table('habitaciones')
            ->where('activa', true)
            ->orderBy('numero')
            ->get();

        if ($habitaciones->isEmpty()) {
            $this->command->warn('No hay habitaciones activas para actualizar housekeeping.');
            return;
        }

        // Distribución de estados: realista para un hotel ocupado al 60%
        //  limpia=40%, sucia=30%, en_limpieza=20%, inspeccion=10%
        $estadosDistribucion = [];
        $total = $habitaciones->count();
        $limpia      = (int) round($total * 0.40);
        $sucia       = (int) round($total * 0.30);
        $enLimpieza  = (int) round($total * 0.20);
        $inspeccion  = $total - $limpia - $sucia - $enLimpieza;

        for ($i = 0; $i < $limpia;     $i++) $estadosDistribucion[] = 'limpia';
        for ($i = 0; $i < $sucia;      $i++) $estadosDistribucion[] = 'sucia';
        for ($i = 0; $i < $enLimpieza; $i++) $estadosDistribucion[] = 'en_limpieza';
        for ($i = 0; $i < $inspeccion; $i++) $estadosDistribucion[] = 'inspeccion';

        // Notas de ejemplo por estado
        $notasPorEstado = [
            'limpia'      => ['Lista para ocupación.', 'Revisada y lista.', 'Limpia y equipada.', 'Sin observaciones.'],
            'sucia'       => ['Requiere limpieza completa.', 'Pendiente de limpieza post checkout.', 'Toallas y sábanas a cambiar.', 'Needs cleaning after checkout.'],
            'en_limpieza' => ['Camarera en proceso.', 'Limpieza en curso desde las 9am.', 'En limpieza, aprox. 20 min más.', 'Personal asignado: Equipo A.'],
            'inspeccion'  => ['Esperando inspección de supervisora.', 'Revisión de amenities pendiente.', 'Verificar minibar y TV.', 'Supervisora asignada.'],
        ];

        // Tiempos de actualización variados (simulando actividad durante el día)
        $horasActualizacion = [
            Carbon::today()->setTime(7, 30),
            Carbon::today()->setTime(8, 15),
            Carbon::today()->setTime(9, 0),
            Carbon::today()->setTime(9, 45),
            Carbon::today()->setTime(10, 30),
            Carbon::today()->setTime(11, 0),
            Carbon::today()->setTime(11, 30),
            Carbon::today()->setTime(12, 0),
            Carbon::today()->setTime(13, 15),
            Carbon::today()->setTime(14, 0),
        ];

        foreach ($habitaciones as $idx => $hab) {
            $estado = $estadosDistribucion[$idx % count($estadosDistribucion)];
            $notas  = $notasPorEstado[$estado][array_rand($notasPorEstado[$estado])];
            $hora   = $horasActualizacion[$idx % count($horasActualizacion)];

            DB::table('habitaciones')->where('id', $hab->id)->update([
                'estado_limpieza'      => $estado,
                'limpieza_notas'       => $notas,
                'limpieza_actualizado' => $hora,
                'limpieza_user_id'     => $adminId,
                'updated_at'           => now(),
            ]);
        }

        $this->command->info("✓ {$total} habitaciones actualizadas con estados de housekeeping.");

        // Resumen
        $this->command->line("  → Limpias:      {$limpia}");
        $this->command->line("  → Sucias:       {$sucia}");
        $this->command->line("  → En limpieza:  {$enLimpieza}");
        $this->command->line("  → Inspección:   {$inspeccion}");
    }
}
