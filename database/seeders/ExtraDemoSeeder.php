<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Agrega 10 huéspedes + 10 reservas que cubren la semana actual (11-17 mayo 2026)
 * para que el gráfico de ocupación y los paneles del dashboard muestren datos reales.
 */
class ExtraDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        // ── Obtener habitaciones con precio base ──────────────────────────
        $habs = DB::table('habitaciones')
            ->join('tipo_habitaciones', 'habitaciones.tipo_habitacion_id', '=', 'tipo_habitaciones.id')
            ->select('habitaciones.id', 'habitaciones.numero', 'tipo_habitaciones.precio_base')
            ->where('habitaciones.activa', true)
            ->get()
            ->keyBy('numero');

        // ═══════════════════════════════════════════════════════════════════
        //  10 HUÉSPEDES NUEVOS
        // ═══════════════════════════════════════════════════════════════════
        $nuevosHuespedes = [
            ['nombre' => 'Valentina', 'apellido' => 'Paredes Núñez',    'num_documento' => '74123456', 'genero' => 'F', 'telefono' => '977001122', 'email' => 'vparedes@email.com',   'nacionalidad' => 'Peruana',    'fecha_nacimiento' => '1992-03-10'],
            ['nombre' => 'Sebastián', 'apellido' => 'Quispe Cárdenas',  'num_documento' => '65234567', 'genero' => 'M', 'telefono' => '966002233', 'email' => 'squispe@email.com',    'nacionalidad' => 'Boliviana',  'fecha_nacimiento' => '1987-06-25'],
            ['nombre' => 'Camila',    'apellido' => 'Herrera Salinas',  'num_documento' => '73345678', 'genero' => 'F', 'telefono' => '955003344', 'email' => 'cherrera@email.com',   'nacionalidad' => 'Peruana',    'fecha_nacimiento' => '1995-09-14'],
            ['nombre' => 'Andrés',    'apellido' => 'Montoya Espinoza', 'num_documento' => '62456789', 'genero' => 'M', 'telefono' => '944004455', 'email' => 'amontoya@email.com',   'nacionalidad' => 'Colombiana', 'fecha_nacimiento' => '1980-01-30'],
            ['nombre' => 'Isabella',  'apellido' => 'Torres Cáceres',   'num_documento' => '75567890', 'genero' => 'F', 'telefono' => '933005566', 'email' => 'itorres@email.com',    'nacionalidad' => 'Venezolana', 'fecha_nacimiento' => '1998-04-05'],
            ['nombre' => 'Matías',    'apellido' => 'Vargas Coronado',  'num_documento' => '63678901', 'genero' => 'M', 'telefono' => '922006677', 'email' => 'mvargas@email.com',    'nacionalidad' => 'Peruana',    'fecha_nacimiento' => '1983-07-19'],
            ['nombre' => 'Luciana',   'apellido' => 'Paz Bustamante',   'num_documento' => '76789012', 'genero' => 'F', 'telefono' => '911007788', 'email' => 'lpaz@email.com',       'nacionalidad' => 'Chilena',    'fecha_nacimiento' => '1990-12-08'],
            ['nombre' => 'Gabriel',   'apellido' => 'Ramos Iturrizaga', 'num_documento' => '64890123', 'genero' => 'M', 'telefono' => '900008899', 'email' => 'gramos@email.com',     'nacionalidad' => 'Peruana',    'fecha_nacimiento' => '1977-08-22'],
            ['nombre' => 'Fernanda',  'apellido' => 'León Villanueva',  'num_documento' => '77901234', 'genero' => 'F', 'telefono' => '999109900', 'email' => 'fleon@email.com',      'nacionalidad' => 'Ecuatoriana','fecha_nacimiento' => '1993-02-17'],
            ['nombre' => 'Nicolás',   'apellido' => 'Cruz Palomino',    'num_documento' => '66012345', 'genero' => 'M', 'telefono' => '988200011', 'email' => 'ncruz@email.com',      'nacionalidad' => 'Argentina',  'fecha_nacimiento' => '1985-11-03'],
        ];

        $huespedesIds = [];
        foreach ($nuevosHuespedes as $h) {
            $existing = DB::table('huespedes')->where('num_documento', $h['num_documento'])->value('id');
            if ($existing) {
                $huespedesIds[] = $existing;
            } else {
                $huespedesIds[] = DB::table('huespedes')->insertGetId(array_merge($h, [
                    'tipo_documento' => 'DNI',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]));
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        //  10 RESERVAS — cubren semana 11-17 mayo 2026
        //
        //  Estado del gráfico resultante (fecha_entrada<=día AND fecha_salida>día):
        //   11/05 → 5 hab  |  12/05 → 5  |  13/05 → 5
        //   14/05 → 6 hab  |  15/05 → 7  |  16/05 → 7  |  17/05 → 8
        // ═══════════════════════════════════════════════════════════════════
        $metodos     = ['efectivo', 'tarjeta_credito', 'tarjeta_debito', 'transferencia', 'yape', 'plin'];
        $comprobantes= ['boleta', 'boleta', 'boleta', 'factura'];
        $origenes    = ['presencial', 'web', 'telefono', 'agencia'];

        /*
         * [huesped_idx, hab_numero, entrada, salida, estado, personas,
         *  fecha_checkin_real, fecha_checkout_real]
         * fecha_checkin_real = null → se usa entrada
         * fecha_checkout_real = null → null (solo aplica si estado=checkout)
         */
        $reservasNuevas = [
            // ─ ACTIVAS (checkin) — cubrirán todos o varios días del gráfico ──
            // R01: hab 101, entró 08/05, sale 20/05 → activa los 7 días
            [0, '101', '2026-05-08', '2026-05-20', 'checkin',  2, '2026-05-08', null],
            // R02: hab 103, entró 10/05, sale 18/05 → activa los 7 días
            [1, '103', '2026-05-10', '2026-05-18', 'checkin',  1, '2026-05-10', null],
            // R03: hab 201, entró 11/05, sale 19/05 → activa 11→17/05
            [2, '201', '2026-05-11', '2026-05-19', 'checkin',  2, '2026-05-11', null],
            // R04: hab 203, entró 12/05, sale 20/05 → activa 12→17/05
            [3, '203', '2026-05-12', '2026-05-20', 'checkin',  2, '2026-05-12', null],
            // R05: hab 204, entró 14/05, sale 22/05 → activa 14→17/05
            [4, '204', '2026-05-14', '2026-05-22', 'checkin',  3, '2026-05-14', null],
            // R06: hab 301, entró 15/05, sale 23/05 → activa 15→17/05
            [5, '301', '2026-05-15', '2026-05-23', 'checkin',  2, '2026-05-15', null],
            // R07: hab 302, entró 16/05, sale 20/05 → activa 16→17/05
            [6, '302', '2026-05-16', '2026-05-20', 'checkin',  2, '2026-05-16', null],
            // R08: hab 303, entró HOY 17/05, sale 21/05 → activa hoy
            [7, '303', '2026-05-17', '2026-05-21', 'checkin',  1, '2026-05-17', null],

            // ─ CHECKOUTS RECIENTES — aportan al gráfico y a ingresos del mes ─
            // R09: hab 105, entró 06/05, salió 12/05 → checkout, pago 12/05
            [8, '105', '2026-05-06', '2026-05-12', 'checkout', 2, '2026-05-06', '2026-05-12'],
            // R10: hab 202, entró 09/05, salió 16/05 → checkout, pago 16/05
            [9, '202', '2026-05-09', '2026-05-16', 'checkout', 2, '2026-05-09', '2026-05-16'],
        ];

        $contadorRes = DB::table('reservas')->max(DB::raw("CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)")) + 1;
        if ($contadorRes < 100) $contadorRes = 100; // evitar colisiones
        $contadorFac = DB::table('facturas')->count() + 1;

        $reservaIds = [];
        foreach ($reservasNuevas as $idx => $r) {
            [$hIdx, $habNumero, $entrada, $salida, $estado, $personas, $fCheckin, $fCheckout] = $r;

            if (!isset($habs[$habNumero])) continue;
            $hab       = $habs[$habNumero];
            $numNoches = Carbon::parse($entrada)->diffInDays(Carbon::parse($salida));
            $precioNoche = $hab->precio_base;
            $subtotal  = $precioNoche * $numNoches;
            $total     = $subtotal;

            $codigo = 'RES-2026-' . str_pad($contadorRes, 4, '0', STR_PAD_LEFT);
            // Evitar duplicados
            while (DB::table('reservas')->where('codigo', $codigo)->exists()) {
                $contadorRes++;
                $codigo = 'RES-2026-' . str_pad($contadorRes, 4, '0', STR_PAD_LEFT);
            }

            $reservaIds[$idx] = DB::table('reservas')->insertGetId([
                'codigo'         => $codigo,
                'huesped_id'     => $huespedesIds[$hIdx],
                'habitacion_id'  => $hab->id,
                'user_id'        => $adminId,
                'fecha_entrada'  => $entrada,
                'fecha_salida'   => $salida,
                'fecha_checkin'  => $fCheckin,
                'fecha_checkout' => $fCheckout,
                'num_personas'   => $personas,
                'estado'         => $estado,
                'precio_noche'   => $precioNoche,
                'num_noches'     => $numNoches,
                'subtotal'       => $subtotal,
                'descuento'      => 0,
                'total'          => $total,
                'origen'         => $origenes[array_rand($origenes)],
                'created_at'     => Carbon::parse($entrada)->addHours(rand(8, 12)),
                'updated_at'     => now(),
            ]);

            $contadorRes++;

            // ── Factura ────────────────────────────────────────────────────
            $igv        = round($subtotal * 0.18, 2);
            $totalFac   = round($subtotal + $igv, 2);
            $estadoFac  = $estado === 'checkout' ? 'pagada' : 'pendiente';
            $fechaEmision = $estado === 'checkout' ? $salida : now()->toDateString();
            $numFac     = 'FAC-2026-' . str_pad($contadorFac, 4, '0', STR_PAD_LEFT);

            $facturaId = DB::table('facturas')->insertGetId([
                'numero'           => $numFac,
                'reserva_id'       => $reservaIds[$idx],
                'huesped_id'       => $huespedesIds[$hIdx],
                'user_id'          => $adminId,
                'fecha_emision'    => $fechaEmision,
                'subtotal'         => $subtotal,
                'igv'              => $igv,
                'descuento'        => 0,
                'total'            => $totalFac,
                'estado'           => $estadoFac,
                'tipo_comprobante' => $comprobantes[array_rand($comprobantes)],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $contadorFac++;

            // ── Pago (solo checkouts) ──────────────────────────────────────
            if ($estadoFac === 'pagada') {
                DB::table('pagos')->insert([
                    'factura_id'  => $facturaId,
                    'user_id'     => $adminId,
                    'monto'       => $totalFac,
                    'metodo_pago' => $metodos[array_rand($metodos)],
                    'referencia'  => 'OP-' . rand(200000, 299999),
                    'fecha_pago'  => $fechaEmision,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        //  ACTUALIZAR ESTADOS DE HABITACIONES
        //  1. Resetear todas las activas a 'disponible'
        //  2. Marcar 'ocupada' las que tienen checkin vigente hoy
        // ═══════════════════════════════════════════════════════════════════
        // Primero: limpiar estado de reservas viejas que ya hicieron checkout
        DB::table('habitaciones')
            ->where('activa', true)
            ->where('estado', 'ocupada')
            ->update(['estado' => 'disponible', 'updated_at' => now()]);

        // Luego: marcar habitaciones con checkin activo HOY
        $habsOcupadasHoy = ['101', '103', '201', '203', '204', '301', '302', '303'];
        DB::table('habitaciones')
            ->whereIn('numero', $habsOcupadasHoy)
            ->update(['estado' => 'ocupada', 'updated_at' => now()]);

        // ═══════════════════════════════════════════════════════════════════
        //  CARGOS ADICIONALES en reservas activas
        // ═══════════════════════════════════════════════════════════════════
        $cargosExtra = [
            [0, 'Desayuno buffet x2',    'restaurante', 35.00, 2, '2026-05-09'],
            [1, 'Minibar (bebidas)',      'minibar',     45.00, 1, '2026-05-11'],
            [2, 'Lavandería x2 personas','lavanderia',  25.00, 2, '2026-05-13'],
            [3, 'Desayuno x2',           'restaurante', 30.00, 2, '2026-05-14'],
            [4, 'Tour Machu Picchu',     'tours',      180.00, 3, '2026-05-15'],
            [5, 'Cena romántica suite',  'restaurante', 95.00, 2, '2026-05-16'],
            [6, 'Spa pareja',            'spa',        150.00, 2, '2026-05-16'],
            [7, 'Room service',          'restaurante', 45.00, 1, '2026-05-17'],
            [8, 'Transporte aeropuerto', 'transporte',  60.00, 2, '2026-05-12'],
            [9, 'Desayuno x2',          'restaurante',  30.00, 2, '2026-05-10'],
        ];

        foreach ($cargosExtra as $c) {
            [$rIdx, $concepto, $categoria, $precio, $cantidad, $fecha] = $c;
            if (!isset($reservaIds[$rIdx])) continue;

            DB::table('cargos_adicionales')->insert([
                'reserva_id'      => $reservaIds[$rIdx],
                'factura_id'      => null,
                'concepto'        => $concepto,
                'categoria'       => $categoria,
                'precio_unitario' => $precio,
                'cantidad'        => $cantidad,
                'subtotal'        => $precio * $cantidad,
                'fecha'           => $fecha,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // ═══════════════════════════════════════════════════════════════════
        //  RESUMEN FINAL
        // ═══════════════════════════════════════════════════════════════════
        $ocupadas   = DB::table('habitaciones')->where('estado', 'ocupada')->count();
        $disponibles= DB::table('habitaciones')->where('estado', 'disponible')->count();
        $totalHuespedes = DB::table('huespedes')->count();
        $reservasMes= DB::table('reservas')
            ->whereDate('fecha_entrada', '>=', Carbon::now()->startOfMonth())
            ->count();

        $this->command->info("✓ 10 huéspedes adicionales insertados (total: {$totalHuespedes})");
        $this->command->info("✓ 10 reservas semana actual insertadas (este mes: {$reservasMes})");
        $this->command->info("✓ 10 facturas + pagos generados");
        $this->command->info("✓ 10 cargos adicionales en reservas activas");
        $this->command->info("✓ Habitaciones: {$ocupadas} ocupadas / {$disponibles} disponibles");
        $this->command->line('');
        $this->command->line('  Ocupación gráfico (últimos 7 días):');
        $this->command->line('  11/05→5 | 12/05→5 | 13/05→5 | 14/05→6 | 15/05→7 | 16/05→7 | 17/05→8');
    }
}
