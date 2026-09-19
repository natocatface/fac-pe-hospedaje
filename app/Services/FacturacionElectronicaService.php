<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Factura;
use App\Models\NotaCredito;
use App\Models\ComunicacionBaja;
use App\Models\ResumenBoletas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio de Facturación Electrónica SUNAT — Emisión Directa (PSE/Facturador Directo)
 *
 * Requiere: composer require greenter/greenter
 * (bacon/bacon-qr-code se instala automáticamente como dependencia de greenter)
 *
 * Flujo:
 *  1. prepararNumeroSunat()  → asigna serie y correlativo
 *  2. emitir()               → genera XML, firma, envía a SUNAT, guarda CDR
 *  3. generarQr()            → construye la cadena QR del comprobante
 */
class FacturacionElectronicaService
{
    // ── Tipos de documento SUNAT ────────────────────────────────────────────
    const TIPO_FACTURA  = '01';
    const TIPO_BOLETA   = '03';
    const TIPO_NC       = '07'; // Nota de Crédito
    const TIPO_ND       = '08'; // Nota de Débito

    // ── Tipos de documento de identidad ────────────────────────────────────
    const DOC_DNI       = '1';
    const DOC_CE        = '4';  // Carné de Extranjería
    const DOC_PASAPORTE = '7';
    const DOC_RUC       = '6';
    const DOC_SIN_DOC   = '-'; // Sin documento

    // ── Endpoints SUNAT ─────────────────────────────────────────────────────
    const ENDPOINT_BETA       = 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService';
    const ENDPOINT_PRODUCCION = 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService';

    // ── Storage paths ────────────────────────────────────────────────────────
    const PATH_XML  = 'sunat/xml';
    const PATH_CDR  = 'sunat/cdr';
    const PATH_CERT = 'sunat/certificado';

    // ────────────────────────────────────────────────────────────────────────
    //  MÉTODO PRINCIPAL — Emitir comprobante
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Emite el comprobante electrónico ante SUNAT.
     * Retorna array con resultado.
     */
    public function emitir(Factura $factura): array
    {
        // Verificar que Greenter esté instalado
        if (!class_exists('Greenter\See')) {
            return $this->error('El paquete greenter/greenter no está instalado. Ejecuta: composer require greenter/greenter');
        }

        try {
            // 1. Asignar serie/correlativo si aún no tiene
            if (!$factura->serie_sunat) {
                $this->prepararNumeroSunat($factura);
            }

            // 2. Construir el comprobante Greenter
            $comprobante = $this->buildComprobante($factura);
            if (!$comprobante) {
                return $this->error('No se pudo construir el comprobante. Verifique la configuración SUNAT.');
            }

            // 3. Inicializar el cliente SUNAT (SEE)
            $see = $this->buildSee();
            if (!$see) {
                return $this->error('No se pudo inicializar el cliente SUNAT. Verifique las credenciales SOL y el certificado.');
            }

            // 4. Generar y guardar XML firmado
            $xmlContent  = $see->getXmlSigned($comprobante);
            $xmlFilename = $this->getXmlFilename($factura);
            Storage::disk('local')->put(self::PATH_XML . '/' . $xmlFilename, $xmlContent);

            // 5. Enviar a SUNAT
            $factura->update([
                'estado_sunat'      => 'pendiente',
                'fecha_envio_sunat' => now(),
            ]);

            $result = $see->send($comprobante);

            // 6. Procesar respuesta CDR
            return $this->procesarRespuesta($factura, $result, $xmlFilename);

        } catch (\Throwable $e) {
            Log::error('SUNAT Error al emitir factura ' . $factura->numero . ': ' . $e->getMessage());
            $factura->update([
                'estado_sunat'   => 'excepcion',
                'mensaje_sunat'  => 'Error interno: ' . $e->getMessage(),
            ]);
            return $this->error('Excepción: ' . $e->getMessage());
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    //  PREPARAR NÚMERO SUNAT
    // ────────────────────────────────────────────────────────────────────────

    public function prepararNumeroSunat(Factura $factura): void
    {
        $tipoDoc = $this->getTipoDocSunat($factura);
        $serie   = $this->getSerie($factura);
        $correlativo = $this->siguienteCorrelativo($serie);

        $factura->update([
            'tipo_doc_sunat'    => $tipoDoc,
            'serie_sunat'       => $serie,
            'correlativo_sunat' => $correlativo,
            'qr_data'           => $this->buildQrData($factura, $serie, $correlativo),
        ]);

        $factura->refresh();
    }

    // ────────────────────────────────────────────────────────────────────────
    //  CONSTRUIR COMPROBANTE GREENTER
    // ────────────────────────────────────────────────────────────────────────

    private function buildComprobante(Factura $factura): mixed
    {
        $tipoDoc = $factura->tipo_doc_sunat;

        return match ($tipoDoc) {
            self::TIPO_FACTURA, self::TIPO_BOLETA => $this->buildInvoice($factura),
            default => null,
        };
    }

    private function buildInvoice(Factura $factura): \Greenter\Model\Sale\Invoice
    {
        $factura->load(['huesped', 'reserva.habitacion.tipoHabitacion', 'reserva.cargosAdicionales']);

        $company = $this->buildCompany();
        $client  = $this->buildClient($factura);
        $details = $this->buildDetails($factura);
        $leyenda = $this->buildLeyenda($factura->total);

        // Calcular importes
        $igvPct     = (float) Configuracion::get('facturacion_igv', '18');
        $mtoGravadas= round($factura->total / (1 + $igvPct / 100), 2);
        $mtoIgv     = round($factura->total - $mtoGravadas, 2);

        // Si no tiene IGV registrado en la factura, usar el campo directo
        if ($factura->igv > 0) {
            $mtoIgv      = (float) $factura->igv;
            $mtoGravadas = (float) $factura->subtotal;
        }

        $invoice = new \Greenter\Model\Sale\Invoice();
        $invoice
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')                    // Venta interna
            ->setTipoDoc($factura->tipo_doc_sunat)
            ->setSerie($factura->serie_sunat)
            ->setCorrelativo((string) $factura->correlativo_sunat)
            ->setFechaEmision(new \DateTime($factura->fecha_emision->format('Y-m-d')))
            ->setFormaPago(new \Greenter\Model\Sale\FormaPago())  // Contado
            ->setTipoMoneda('PEN')
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas($mtoGravadas)
            ->setMtoIGV($mtoIgv)
            ->setTotalImpuestos($mtoIgv)
            ->setValorVenta($mtoGravadas)
            ->setSubTotal((float) $factura->total)
            ->setMtoImpVenta((float) $factura->total)
            ->setDetails($details)
            ->setLegends([$leyenda]);

        return $invoice;
    }

    private function buildCompany(): \Greenter\Model\Company\Company
    {
        $address = new \Greenter\Model\Company\Address();
        $address
            ->setUbigueo(Configuracion::get('sunat_ubigeo', '150101'))
            ->setDepartamento(Configuracion::get('sunat_departamento', 'LIMA'))
            ->setProvincia(Configuracion::get('sunat_provincia', 'LIMA'))
            ->setDistrito(Configuracion::get('sunat_distrito', 'LIMA'))
            ->setUrbanizacion('-')
            ->setDireccion(Configuracion::get('empresa_direccion', 'AV. PRINCIPAL S/N'))
            ->setCodLocal('0000');

        $company = new \Greenter\Model\Company\Company();
        $company
            ->setRuc(Configuracion::get('empresa_ruc', '20000000001'))
            ->setRazonSocial(Configuracion::get('empresa_razon_social', 'EMPRESA SAC'))
            ->setNombreComercial(Configuracion::get('empresa_nombre', 'MI HOTEL'))
            ->setAddress($address);

        return $company;
    }

    private function buildClient(Factura $factura): \Greenter\Model\Client\Client
    {
        $huesped  = $factura->huesped;
        $tipoDoc  = $factura->tipo_doc_sunat;

        // Tipo de doc de identidad del cliente
        if ($tipoDoc === self::TIPO_FACTURA) {
            // Factura → requiere RUC
            $docType = self::DOC_RUC;
            $numDoc  = $factura->ruc_cliente ?? '00000000000';
            $nombre  = $factura->razon_social ?? $huesped->nombre_completo;
        } else {
            // Boleta → según tipo documento del huésped
            $docType = $this->mapTipoDoc($huesped->tipo_documento ?? 'DNI');
            $numDoc  = $huesped->num_documento ?? '00000000';
            $nombre  = $huesped->nombre_completo;
        }

        $client = new \Greenter\Model\Client\Client();
        $client
            ->setTipoDoc($docType)
            ->setNumDoc($numDoc)
            ->setRznSocial($nombre);

        return $client;
    }

    private function buildDetails(Factura $factura): array
    {
        $factura->load(['reserva.cargosAdicionales']);

        $igvPct  = (float) Configuracion::get('facturacion_igv', '18');
        $details = [];
        $seq     = 1;

        // ── Alojamiento ─────────────────────────────────────────────────
        $reserva     = $factura->reserva;
        $precioConIgv= (float) $reserva->precio_noche;
        $precioSinIgv= round($precioConIgv / (1 + $igvPct / 100), 6);
        $cantidad    = (float) $reserva->num_noches;
        $valorVenta  = round($precioSinIgv * $cantidad, 2);
        $mtoIgv      = round($valorVenta * ($igvPct / 100), 2);

        $detail = new \Greenter\Model\Sale\SaleDetail();
        $detail
            ->setCodProducto('S001')
            ->setUnidad('ZZ')                             // Servicios
            ->setCantidad($cantidad)
            ->setMtoValorUnitario($precioSinIgv)
            ->setDescripcion('SERVICIO DE ALOJAMIENTO — ' . $cantidad . ' noche(s) Hab. ' . ($reserva->habitacion->numero ?? ''))
            ->setMtoBaseIgv($valorVenta)
            ->setPorcentajeIgv($igvPct)
            ->setIgv($mtoIgv)
            ->setTipAfeIgv('10')                         // Gravado
            ->setTotalImpuestos($mtoIgv)
            ->setMtoValorVenta($valorVenta)
            ->setMtoPrecioUnitario($precioConIgv);

        $details[] = $detail;
        $seq++;

        // ── Cargos adicionales ───────────────────────────────────────────
        foreach ($factura->reserva->cargosAdicionales as $cargo) {
            $precioUnit = round($cargo->precio_unitario / (1 + $igvPct / 100), 6);
            $cant       = (float) $cargo->cantidad;
            $venta      = round($precioUnit * $cant, 2);
            $igv        = round($venta * ($igvPct / 100), 2);

            $d = new \Greenter\Model\Sale\SaleDetail();
            $d
                ->setCodProducto('S00' . $seq)
                ->setUnidad('ZZ')
                ->setCantidad($cant)
                ->setMtoValorUnitario($precioUnit)
                ->setDescripcion(strtoupper($cargo->concepto))
                ->setMtoBaseIgv($venta)
                ->setPorcentajeIgv($igvPct)
                ->setIgv($igv)
                ->setTipAfeIgv('10')
                ->setTotalImpuestos($igv)
                ->setMtoValorVenta($venta)
                ->setMtoPrecioUnitario((float) $cargo->precio_unitario);

            $details[] = $d;
            $seq++;
        }

        return $details;
    }

    private function buildLeyenda(float $total): \Greenter\Model\Sale\Legend
    {
        $leyenda = new \Greenter\Model\Sale\Legend();
        $leyenda
            ->setCode('1000')
            ->setValue(strtoupper($this->numeroALetras($total)) . ' SOLES');

        return $leyenda;
    }

    // ────────────────────────────────────────────────────────────────────────
    //  INICIALIZAR SEE (cliente SUNAT)
    // ────────────────────────────────────────────────────────────────────────

    private function buildSee(): ?\Greenter\See
    {
        $ruc      = Configuracion::get('empresa_ruc');
        $usuario  = Configuracion::get('sunat_sol_usuario');
        $clave    = Configuracion::get('sunat_sol_clave');
        $ambiente = Configuracion::get('sunat_ambiente', 'beta');
        $certPath = Configuracion::get('sunat_certificado_path');

        if (!$ruc || !$usuario || !$clave) {
            Log::error('SUNAT: Faltan credenciales SOL (RUC, usuario o clave).');
            return null;
        }

        $endpoint = $ambiente === 'produccion'
            ? self::ENDPOINT_PRODUCCION
            : self::ENDPOINT_BETA;

        $see = new \Greenter\See();

        // Cargar certificado
        $certContent = $this->loadCertificate($certPath);
        if ($certContent) {
            $see->setCertificate($certContent);
        } else {
            // Usar certificado de prueba SUNAT si no hay uno configurado
            Log::warning('SUNAT: Usando certificado de prueba (solo beta). Configure uno real para producción.');
            $testCert = $this->getTestCertificate();
            if ($testCert) $see->setCertificate($testCert);
        }

        $see->setService($endpoint);
        $see->setClaveSOL($ruc, $usuario, $clave);

        return $see;
    }

    private function loadCertificate(?string $path): ?string
    {
        if (!$path) return null;

        // Intentar desde storage local
        if (Storage::disk('local')->exists($path)) {
            $content = Storage::disk('local')->get($path);

            // Si es .p12/.pfx, convertir a PEM
            if (str_ends_with(strtolower($path), '.p12') || str_ends_with(strtolower($path), '.pfx')) {
                $password = Configuracion::get('sunat_certificado_clave', '');
                $certData = [];
                if (openssl_pkcs12_read($content, $certData, $password)) {
                    return ($certData['cert'] ?? '') . PHP_EOL . ($certData['pkey'] ?? '');
                }
                return null;
            }

            return $content; // PEM directo
        }

        return null;
    }

    /**
     * Certificado de prueba SUNAT para ambiente beta.
     * En producción DEBE reemplazarse por un certificado real de una CA autorizada.
     */
    private function getTestCertificate(): ?string
    {
        $p12Path = storage_path('app/sunat/certificado/sunat-test.p12');
        if (!file_exists($p12Path)) return null;

        $certData = [];
        if (openssl_pkcs12_read(file_get_contents($p12Path), $certData, 'moddatos')) {
            return ($certData['cert'] ?? '') . PHP_EOL . ($certData['pkey'] ?? '');
        }
        return null;
    }

    // ────────────────────────────────────────────────────────────────────────
    //  PROCESAR RESPUESTA CDR
    // ────────────────────────────────────────────────────────────────────────

    private function procesarRespuesta(Factura $factura, mixed $result, string $xmlFilename): array
    {
        if (!$result->isSuccess()) {
            $factura->update([
                'estado_sunat'  => 'excepcion',
                'codigo_sunat'  => 'E-SOAP',
                'mensaje_sunat' => $result->getError()->getMessage() ?? 'Error de comunicación SOAP',
            ]);
            return $this->error('Error SOAP: ' . ($result->getError()?->getMessage() ?? 'desconocido'));
        }

        $cdr  = $result->getCdrResponse();
        $code = (string) ($cdr?->getCode() ?? 'unknown');

        // Código 0 = aceptado, 2xxx = aceptado con observaciones, 4xxx/5xxx = rechazado
        if ($code === '0') {
            $estado = 'aceptado';
        } elseif (str_starts_with($code, '2')) {
            $estado = 'aceptado_obs';
        } elseif (str_starts_with($code, '4') || str_starts_with($code, '5')) {
            $estado = 'rechazado';
        } else {
            $estado = 'excepcion';
        }

        // Guardar CDR
        $cdrFilename = null;
        $cdrXml = $result->getCdrZip();
        if ($cdrXml) {
            $cdrFilename = 'R-' . $xmlFilename;
            Storage::disk('local')->put(self::PATH_CDR . '/' . $cdrFilename, $cdrXml);
        }

        // Actualizar factura
        $factura->update([
            'estado_sunat'      => $estado,
            'codigo_sunat'      => $code,
            'mensaje_sunat'     => $cdr?->getDescription() ?? '',
            'notas_sunat'       => implode(' | ', $cdr?->getNotes() ?? []),
            'hash_cpe'          => $cdr?->getId() ?? '',
            'xml_path'          => self::PATH_XML . '/' . $xmlFilename,
            'cdr_path'          => $cdrFilename ? self::PATH_CDR . '/' . $cdrFilename : null,
            'fecha_envio_sunat' => now(),
        ]);

        $success = in_array($estado, ['aceptado', 'aceptado_obs']);
        return [
            'success'    => $success,
            'estado'     => $estado,
            'codigo'     => $code,
            'mensaje'    => $cdr?->getDescription() ?? '',
            'notas'      => $cdr?->getNotes() ?? [],
            'xmlFile'    => $xmlFilename,
            'cdrFile'    => $cdrFilename,
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    //  QR CODE DATA
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Genera la cadena de datos para el QR según SUNAT:
     * RUC|TipoDoc|Serie|Correlativo|IGV|Total|FechaEmision|TipoDocCliente|NumDocCliente|
     */
    public function buildQrData(Factura $factura, string $serie, int $correlativo): string
    {
        $ruc         = Configuracion::get('empresa_ruc', '20000000001');
        $tipoDoc     = $this->getTipoDocSunat($factura);
        $igv         = number_format((float) $factura->igv, 2, '.', '');
        $total       = number_format((float) $factura->total, 2, '.', '');
        $fecha       = $factura->fecha_emision->format('Y-m-d');
        $tipoDocCli  = $this->mapTipoDoc($factura->huesped->tipo_documento ?? 'DNI');
        $numDocCli   = $factura->huesped->num_documento ?? '';

        return implode('|', [
            $ruc, $tipoDoc, $serie, $correlativo,
            $igv, $total, $fecha,
            $tipoDocCli, $numDocCli, '',
        ]);
    }

    /**
     * Genera imagen QR en Base64 para usar en PDF/HTML.
     *
     * Estrategia (orden de preferencia):
     *  1. bacon/bacon-qr-code v3+  → SVG (no requiere extensiones GD/Imagick).
     *     Ya viene instalado como dependencia de greenter/greenter.
     *  2. bacon/bacon-qr-code v3+ con Imagick (si está disponible) → PNG.
     *  3. Fallback: URL pública a api.qrserver.com (requiere internet).
     */
    public function generarQrBase64(string $qrData): ?string
    {
        try {
            // === Opción 1: bacon-qr-code v3 con backend SVG (siempre funciona) ===
            if (class_exists('BaconQrCode\Renderer\ImageRenderer')) {
                // Intentar primero con Imagick para PNG (más compatible con DomPDF)
                if (extension_loaded('imagick') && class_exists('BaconQrCode\Renderer\Image\ImagickImageBackEnd')) {
                    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                        new \BaconQrCode\Renderer\RendererStyle\RendererStyle(140, 2),
                        new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
                    );
                    $writer = new \BaconQrCode\Writer($renderer);
                    $png = $writer->writeString($qrData);
                    return 'data:image/png;base64,' . base64_encode($png);
                }

                // Sin Imagick: usar SVG (PHP puro, sin extensiones nativas)
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(140, 2),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $svg    = $writer->writeString($qrData);
                return 'data:image/svg+xml;base64,' . base64_encode($svg);
            }

            // === Opción 2: Fallback online ===
            $encoded = urlencode($qrData);
            return "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={$encoded}";

        } catch (\Throwable $e) {
            Log::warning('No se pudo generar QR: ' . $e->getMessage());
            // Como último recurso, intentar el servicio externo
            $encoded = urlencode($qrData);
            return "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={$encoded}";
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    //  HELPERS DE NUMERACIÓN
    // ────────────────────────────────────────────────────────────────────────

    private function getTipoDocSunat(Factura $factura): string
    {
        return $factura->tipo_comprobante === 'factura'
            ? self::TIPO_FACTURA
            : self::TIPO_BOLETA;
    }

    private function getSerie(Factura $factura): string
    {
        if ($factura->tipo_comprobante === 'factura') {
            return Configuracion::get('facturacion_serie_factura', 'F001');
        }
        return Configuracion::get('facturacion_serie_boleta', 'B001');
    }

    public function siguienteCorrelativo(string $serie): int
    {
        $ultimo = Factura::where('serie_sunat', $serie)
            ->whereNotNull('correlativo_sunat')
            ->max('correlativo_sunat');
        return (int) ($ultimo ?? 0) + 1;
    }

    private function getXmlFilename(Factura $factura): string
    {
        $ruc = Configuracion::get('empresa_ruc', '20000000001');
        return "{$ruc}-{$factura->tipo_doc_sunat}-{$factura->serie_sunat}-{$factura->correlativo_sunat}.xml";
    }

    private function mapTipoDoc(string $tipoDoc): string
    {
        return match (strtoupper($tipoDoc)) {
            'DNI'       => self::DOC_DNI,
            'CE'        => self::DOC_CE,
            'PASAPORTE' => self::DOC_PASAPORTE,
            'RUC'       => self::DOC_RUC,
            default     => self::DOC_DNI,
        };
    }

    // ────────────────────────────────────────────────────────────────────────
    //  DESCARGAR XML / CDR
    // ────────────────────────────────────────────────────────────────────────

    public function getXmlContent(Factura $factura): ?string
    {
        if (!$factura->xml_path) return null;
        return Storage::disk('local')->get($factura->xml_path);
    }

    public function getCdrContent(Factura $factura): ?string
    {
        if (!$factura->cdr_path) return null;
        return Storage::disk('local')->get($factura->cdr_path);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  UTILIDADES
    // ────────────────────────────────────────────────────────────────────────

    private function error(string $mensaje): array
    {
        return ['success' => false, 'mensaje' => $mensaje];
    }

    /**
     * Convierte un número a texto en español (leyenda SUNAT).
     * Ej: 150.50 → "CIENTO CINCUENTA CON 50/100"
     */
    private function numeroALetras(float $numero): string
    {
        $entero    = (int) floor($numero);
        $decimales = (int) round(($numero - $entero) * 100);
        $palabras  = $this->enteroALetras($entero);
        return strtoupper($palabras) . ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
    }

    private function enteroALetras(int $numero): string
    {
        if ($numero === 0) return 'CERO';
        $unidades  = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                      'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
                      'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
        $decenas   = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas  = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                      'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($numero <= 20) return $unidades[$numero];

        if ($numero < 100) {
            $d = (int) ($numero / 10);
            $u = $numero % 10;
            if ($d === 2 && $u > 0) return 'VEINTI' . strtolower($unidades[$u]);
            return $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
        }

        if ($numero === 100) return 'CIEN';

        if ($numero < 1000) {
            $c = (int) ($numero / 100);
            $r = $numero % 100;
            return $centenas[$c] . ($r > 0 ? ' ' . $this->enteroALetras($r) : '');
        }

        if ($numero < 2000) {
            $r = $numero % 1000;
            return 'MIL' . ($r > 0 ? ' ' . $this->enteroALetras($r) : '');
        }

        if ($numero < 1000000) {
            $miles = (int) ($numero / 1000);
            $r     = $numero % 1000;
            return $this->enteroALetras($miles) . ' MIL' . ($r > 0 ? ' ' . $this->enteroALetras($r) : '');
        }

        return (string) $numero;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ╔═══════════════════════════════════════════════════════════╗
    //  ║          NOTAS DE CRÉDITO (Tipo 07 — Catálogo 09)         ║
    //  ╚═══════════════════════════════════════════════════════════╝
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Emite una Nota de Crédito electrónica que afecta a una factura/boleta.
     */
    public function emitirNotaCredito(NotaCredito $nc): array
    {
        if (!class_exists('Greenter\See')) {
            return $this->error('Greenter no instalado.');
        }

        try {
            // Asignar serie/correlativo si no tiene
            if (!$nc->serie_sunat) {
                $this->prepararNumeroNotaCredito($nc);
            }

            $note = $this->buildNotaCredito($nc);
            if (!$note) {
                return $this->error('No se pudo construir la nota de crédito.');
            }

            $see = $this->buildSee();
            if (!$see) {
                return $this->error('No se pudo inicializar el cliente SUNAT.');
            }

            $xmlContent  = $see->getXmlSigned($note);
            $xmlFilename = Configuracion::get('empresa_ruc', '20000000001')
                         . "-{$nc->tipo_doc_sunat}-{$nc->serie_sunat}-{$nc->correlativo_sunat}.xml";
            Storage::disk('local')->put(self::PATH_XML . '/' . $xmlFilename, $xmlContent);

            $nc->update([
                'estado_sunat'      => 'pendiente',
                'fecha_envio_sunat' => now(),
            ]);

            $result = $see->send($note);
            return $this->procesarRespuestaNotaCredito($nc, $result, $xmlFilename);

        } catch (\Throwable $e) {
            Log::error('SUNAT NC error: ' . $e->getMessage());
            $nc->update([
                'estado_sunat'  => 'excepcion',
                'mensaje_sunat' => 'Excepción: ' . $e->getMessage(),
            ]);
            return $this->error('Excepción: ' . $e->getMessage());
        }
    }

    public function prepararNumeroNotaCredito(NotaCredito $nc): void
    {
        // Serie según el tipo de comprobante afectado
        $factura = $nc->factura;
        $serie   = $factura->tipo_comprobante === 'factura' ? 'FC01' : 'BC01';
        $serie   = Configuracion::get('facturacion_serie_nc_' . $factura->tipo_comprobante, $serie);

        $correlativo = (int) (NotaCredito::where('serie_sunat', $serie)
            ->whereNotNull('correlativo_sunat')
            ->max('correlativo_sunat') ?? 0) + 1;

        $nc->update([
            'tipo_doc_sunat'    => self::TIPO_NC,
            'serie_sunat'       => $serie,
            'correlativo_sunat' => $correlativo,
        ]);

        $nc->refresh();
    }

    private function buildNotaCredito(NotaCredito $nc): ?\Greenter\Model\Sale\Note
    {
        $factura = $nc->factura()->with(['huesped', 'reserva.habitacion.tipoHabitacion', 'reserva.cargosAdicionales'])->first();
        if (!$factura) return null;

        $company  = $this->buildCompany();
        $client   = $this->buildClient($factura);
        $details  = $this->buildDetails($factura);
        $leyenda  = $this->buildLeyenda($nc->total);

        $igvPct      = (float) Configuracion::get('facturacion_igv', '18');
        $mtoGravadas = $nc->subtotal > 0 ? (float) $nc->subtotal : round($nc->total / (1 + $igvPct / 100), 2);
        $mtoIgv      = $nc->igv > 0 ? (float) $nc->igv : round($nc->total - $mtoGravadas, 2);

        $note = new \Greenter\Model\Sale\Note();
        $note
            ->setUblVersion('2.1')
            ->setTipoDoc(self::TIPO_NC)
            ->setSerie($nc->serie_sunat)
            ->setCorrelativo((string) $nc->correlativo_sunat)
            ->setFechaEmision(new \DateTime($nc->fecha_emision->format('Y-m-d')))
            ->setTipDocAfectado($factura->tipo_doc_sunat)
            ->setNumDocfectado($factura->numero_sunat)
            ->setCodMotivo($nc->codigo_motivo)
            ->setDesMotivo($nc->motivo_descripcion)
            ->setTipoMoneda('PEN')
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas($mtoGravadas)
            ->setMtoIGV($mtoIgv)
            ->setTotalImpuestos($mtoIgv)
            ->setMtoImpVenta((float) $nc->total)
            ->setDetails($details)
            ->setLegends([$leyenda]);

        return $note;
    }

    private function procesarRespuestaNotaCredito(NotaCredito $nc, mixed $result, string $xmlFilename): array
    {
        if (!$result->isSuccess()) {
            $nc->update([
                'estado_sunat'  => 'excepcion',
                'codigo_sunat'  => 'E-SOAP',
                'mensaje_sunat' => $result->getError()?->getMessage() ?? 'Error SOAP',
            ]);
            return $this->error('Error SOAP: ' . ($result->getError()?->getMessage() ?? 'desconocido'));
        }

        $cdr  = $result->getCdrResponse();
        $code = (string) ($cdr?->getCode() ?? 'unknown');

        $estado = $code === '0' ? 'aceptado'
                : (str_starts_with($code, '2') ? 'aceptado_obs'
                : ((str_starts_with($code, '4') || str_starts_with($code, '5')) ? 'rechazado' : 'excepcion'));

        $cdrFilename = null;
        if ($cdrXml = $result->getCdrZip()) {
            $cdrFilename = 'R-' . $xmlFilename;
            Storage::disk('local')->put(self::PATH_CDR . '/' . $cdrFilename, $cdrXml);
        }

        $nc->update([
            'estado_sunat'      => $estado,
            'codigo_sunat'      => $code,
            'mensaje_sunat'     => $cdr?->getDescription() ?? '',
            'notas_sunat'       => implode(' | ', $cdr?->getNotes() ?? []),
            'hash_cpe'          => $cdr?->getId() ?? '',
            'xml_path'          => self::PATH_XML . '/' . $xmlFilename,
            'cdr_path'          => $cdrFilename ? self::PATH_CDR . '/' . $cdrFilename : null,
            'fecha_envio_sunat' => now(),
        ]);

        return [
            'success' => in_array($estado, ['aceptado', 'aceptado_obs']),
            'estado'  => $estado,
            'codigo'  => $code,
            'mensaje' => $cdr?->getDescription() ?? '',
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ╔═══════════════════════════════════════════════════════════╗
    //  ║          COMUNICACIÓN DE BAJA (Voided Documents)          ║
    //  ╚═══════════════════════════════════════════════════════════╝
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Envía una Comunicación de Baja a SUNAT para anular comprobantes ya emitidos.
     * Se usa principalmente para FACTURAS. Para boletas se usa el Resumen Diario.
     */
    public function emitirComunicacionBaja(ComunicacionBaja $baja): array
    {
        if (!class_exists('Greenter\See')) {
            return $this->error('Greenter no instalado.');
        }

        try {
            $voided = $this->buildVoided($baja);
            if (!$voided) return $this->error('No se pudo construir la comunicación.');

            $see = $this->buildSee();
            if (!$see) return $this->error('No se pudo inicializar SUNAT.');

            $xmlContent  = $see->getXmlSigned($voided);
            $xmlFilename = Configuracion::get('empresa_ruc') . "-RA-{$baja->fecha_comunicacion->format('Ymd')}-{$baja->correlativo}.xml";
            Storage::disk('local')->put(self::PATH_XML . '/' . $xmlFilename, $xmlContent);

            $baja->update(['estado_sunat' => 'pendiente', 'fecha_envio_sunat' => now()]);

            // Comunicación de baja envía y devuelve un ticket
            $result = $see->send($voided);

            if (!$result->isSuccess()) {
                $baja->update([
                    'estado_sunat'  => 'excepcion',
                    'mensaje_sunat' => $result->getError()?->getMessage() ?? 'Error SOAP',
                ]);
                return $this->error('SOAP: ' . ($result->getError()?->getMessage() ?? 'desconocido'));
            }

            $ticket = $result->getTicket();
            $baja->update([
                'ticket_sunat' => $ticket,
                'xml_path'     => self::PATH_XML . '/' . $xmlFilename,
            ]);

            // Consultar el estado del ticket
            return $this->consultarTicket($baja, $see);

        } catch (\Throwable $e) {
            Log::error('SUNAT Baja error: ' . $e->getMessage());
            $baja->update(['estado_sunat' => 'excepcion', 'mensaje_sunat' => $e->getMessage()]);
            return $this->error('Excepción: ' . $e->getMessage());
        }
    }

    private function buildVoided(ComunicacionBaja $baja): ?\Greenter\Model\Voided\Voided
    {
        $voided = new \Greenter\Model\Voided\Voided();
        $voided
            ->setCorrelativo(str_pad((string) $baja->correlativo, 4, '0', STR_PAD_LEFT))
            ->setFecGeneracion(new \DateTime($baja->fecha_generacion->format('Y-m-d')))
            ->setFecComunicacion(new \DateTime($baja->fecha_comunicacion->format('Y-m-d')))
            ->setCompany($this->buildCompany());

        $details = [];
        foreach ($baja->facturas as $factura) {
            $detail = new \Greenter\Model\Voided\VoidedDetail();
            $detail
                ->setTipoDoc($factura->tipo_doc_sunat)
                ->setSerie($factura->serie_sunat)
                ->setCorrelativo((string) $factura->correlativo_sunat)
                ->setDesMotivoBaja($baja->motivo);
            $details[] = $detail;
        }
        $voided->setDetails($details);

        return $voided;
    }

    /**
     * Consulta el estado del ticket (para Comunicación de Baja / Resumen Diario).
     */
    public function consultarTicket($modelo, \Greenter\See $see = null): array
    {
        $see ??= $this->buildSee();
        if (!$see) return $this->error('No se pudo inicializar SUNAT.');

        if (empty($modelo->ticket_sunat)) {
            return $this->error('Sin ticket SUNAT para consultar.');
        }

        try {
            $result = $see->getStatus($modelo->ticket_sunat);

            if (!$result->isSuccess()) {
                $modelo->update([
                    'estado_sunat'  => 'excepcion',
                    'mensaje_sunat' => $result->getError()?->getMessage() ?? 'Error consultando ticket',
                ]);
                return $this->error('SOAP: ' . ($result->getError()?->getMessage() ?? 'desconocido'));
            }

            $code   = (string) ($result->getCode() ?? 'unknown');
            $estado = $code === '0' ? 'aceptado'
                    : (str_starts_with($code, '2') ? 'aceptado_obs'
                    : ((str_starts_with($code, '4') || str_starts_with($code, '5')) ? 'rechazado' : 'excepcion'));

            // Guardar CDR del ticket
            $cdrFilename = null;
            if ($cdrZip = $result->getCdrZip()) {
                $cdrFilename = 'R-ticket-' . $modelo->ticket_sunat . '.zip';
                Storage::disk('local')->put(self::PATH_CDR . '/' . $cdrFilename, $cdrZip);
            }

            $modelo->update([
                'estado_sunat'  => $estado,
                'codigo_sunat'  => $code,
                'mensaje_sunat' => $result->getCdrResponse()?->getDescription() ?? '',
                'cdr_path'      => $cdrFilename ? self::PATH_CDR . '/' . $cdrFilename : null,
            ]);

            return [
                'success' => in_array($estado, ['aceptado', 'aceptado_obs']),
                'estado'  => $estado,
                'codigo'  => $code,
                'ticket'  => $modelo->ticket_sunat,
                'mensaje' => $result->getCdrResponse()?->getDescription() ?? '',
            ];

        } catch (\Throwable $e) {
            Log::error('SUNAT consultarTicket: ' . $e->getMessage());
            return $this->error('Excepción: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ╔═══════════════════════════════════════════════════════════╗
    //  ║          RESUMEN DIARIO DE BOLETAS (RC)                   ║
    //  ╚═══════════════════════════════════════════════════════════╝
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Envía el resumen diario de boletas a SUNAT (obligatorio en producción).
     * Reporta las boletas emitidas en un día.
     */
    public function emitirResumenDiario(ResumenBoletas $resumen): array
    {
        if (!class_exists('Greenter\See')) {
            return $this->error('Greenter no instalado.');
        }

        try {
            $summary = $this->buildSummary($resumen);
            if (!$summary) return $this->error('No se pudo construir el resumen.');

            $see = $this->buildSee();
            if (!$see) return $this->error('No se pudo inicializar SUNAT.');

            $xmlContent  = $see->getXmlSigned($summary);
            $xmlFilename = Configuracion::get('empresa_ruc') . "-RC-{$resumen->fecha_resumen->format('Ymd')}-{$resumen->correlativo}.xml";
            Storage::disk('local')->put(self::PATH_XML . '/' . $xmlFilename, $xmlContent);

            $resumen->update(['estado_sunat' => 'pendiente', 'fecha_envio_sunat' => now()]);

            $result = $see->send($summary);

            if (!$result->isSuccess()) {
                $resumen->update([
                    'estado_sunat'  => 'excepcion',
                    'mensaje_sunat' => $result->getError()?->getMessage() ?? 'Error SOAP',
                ]);
                return $this->error('SOAP: ' . ($result->getError()?->getMessage() ?? 'desconocido'));
            }

            $ticket = $result->getTicket();
            $resumen->update([
                'ticket_sunat' => $ticket,
                'xml_path'     => self::PATH_XML . '/' . $xmlFilename,
            ]);

            return $this->consultarTicket($resumen, $see);

        } catch (\Throwable $e) {
            Log::error('SUNAT Resumen error: ' . $e->getMessage());
            $resumen->update(['estado_sunat' => 'excepcion', 'mensaje_sunat' => $e->getMessage()]);
            return $this->error('Excepción: ' . $e->getMessage());
        }
    }

    private function buildSummary(ResumenBoletas $resumen): ?\Greenter\Model\Summary\Summary
    {
        $summary = new \Greenter\Model\Summary\Summary();
        $summary
            ->setCorrelativo(str_pad((string) $resumen->correlativo, 4, '0', STR_PAD_LEFT))
            ->setFecGeneracion(new \DateTime($resumen->fecha_generacion->format('Y-m-d')))
            ->setFecResumen(new \DateTime($resumen->fecha_resumen->format('Y-m-d')))
            ->setCompany($this->buildCompany());

        $resumen->load('facturas.huesped');
        $details = [];
        $seq = 1;
        foreach ($resumen->facturas as $factura) {
            $igvPct      = (float) Configuracion::get('facturacion_igv', '18');
            $mtoGravadas = (float) $factura->subtotal ?: round($factura->total / (1 + $igvPct / 100), 2);
            $mtoIgv      = (float) $factura->igv ?: round($factura->total - $mtoGravadas, 2);

            $detail = new \Greenter\Model\Summary\SummaryDetail();
            $detail
                ->setTipoDoc($factura->tipo_doc_sunat)
                ->setSerieNro($factura->numero_sunat)
                ->setEstado($factura->estado_sunat === 'baja' ? '3' : '1') // 1=adicionar, 2=modificar, 3=anular
                ->setClienteTipo($this->mapTipoDoc($factura->huesped->tipo_documento ?? 'DNI'))
                ->setClienteNro($factura->huesped->num_documento ?? '00000000')
                ->setTotal((float) $factura->total)
                ->setMtoOperGravadas($mtoGravadas)
                ->setMtoIGV($mtoIgv);
            $details[] = $detail;
            $seq++;
        }
        $summary->setDetails($details);

        return $summary;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  Helpers comunes para descargar XML/CDR de NC, Baja, Resumen
    // ════════════════════════════════════════════════════════════════════════

    public function getContenidoArchivo(?string $path): ?string
    {
        if (!$path || !Storage::disk('local')->exists($path)) return null;
        return Storage::disk('local')->get($path);
    }
}
