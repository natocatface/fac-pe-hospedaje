# Guía de Activación — Facturación Electrónica SUNAT

## 1. Instalar dependencias

Solo necesitas instalar **greenter/greenter**. El generador de QR (`bacon/bacon-qr-code`)
viene como dependencia transitiva, no hay que instalarlo por separado.

```bash
composer require greenter/greenter
```

> **greenter/greenter** es la librería PHP oficial para UBL 2.1 (facturas y boletas electrónicas SUNAT).
> Incluye automáticamente **bacon/bacon-qr-code v3** que el sistema usa para generar el QR del comprobante (en SVG por defecto, o PNG si tienes la extensión Imagick).

> ⚠️ **No instales `simplesoftwareio/simple-qrcode`**: entra en conflicto con la versión de
> `bacon/bacon-qr-code` que ya trae Greenter. El sistema usa bacon-qr-code directamente.

---

## 2. Migrar los nuevos campos SUNAT

```bash
php artisan migrate
```

Esto agrega a la tabla `facturas` los campos: `serie_sunat`, `correlativo_sunat`,
`tipo_doc_sunat`, `estado_sunat`, `codigo_sunat`, `mensaje_sunat`, `notas_sunat`,
`hash_cpe`, `qr_data`, `xml_path`, `cdr_path`, `fecha_envio_sunat`.

---

## 3. Agregar las claves SUNAT al Seeder (primera vez)

```bash
php artisan db:seed --class=ConfiguracionSeeder
```

Esto inserta (si no existen) las claves `sunat_*` en la tabla `configuraciones`.

---

## 4. Configurar credenciales en el sistema

Ingresa a **Admin → Configuración → SUNAT / Electrónica** y completa:

| Campo | Pruebas (Beta) | Producción |
|-------|---------------|------------|
| Usuario SOL | `MODDATOS` | Tu usuario SOL secundario |
| Clave SOL | `moddatos` | Tu clave SOL |
| Ambiente | Beta | Producción |
| RUC (Tab Empresa) | `20000000001` | Tu RUC real (11 dígitos) |

---

## 5. Certificado digital

### Para pruebas (ambiente Beta)
1. Descarga el certificado de pruebas desde el botón "Descargar SFS-DEMO.zip" en la pantalla de configuración.
2. Extrae el archivo `.p12` del ZIP (normalmente `SFS-DEMO.p12`).
3. Copia el archivo a: `storage/app/sunat/certificado/certificado.p12`
4. La contraseña del certificado de pruebas es: `moddatos`

O bien, súbelo directamente desde la pantalla de Configuración → SUNAT.

### Para producción
Requieres un certificado digital emitido por una entidad autorizada en Perú:
- DigiCert (https://www.digicert.com/pe/)
- GlobalSign (https://www.globalsign.com/es-pe/)
- RENIEC (para personas naturales)

El archivo debe estar en formato `.p12` (PKCS#12) con clave privada incluida.

---

## 6. Cómo emitir un comprobante electrónico

1. Crea o abre una **Factura** o **Boleta** desde el módulo de Facturación.
2. En el panel derecho verás la sección **"Facturación Electrónica"**.
3. Haz clic en **"Emitir a SUNAT"**.
4. El sistema:
   - Asigna la serie (F001 para facturas, B001 para boletas) y correlativo
   - Genera el XML UBL 2.1
   - Firma el XML con tu certificado digital
   - Envía al web service de SUNAT
   - Descarga el CDR (Constancia de Recepción)
   - Actualiza el estado: **Aceptado** ✓ o **Rechazado** ✗

---

## 7. Endpoints SUNAT

| Ambiente | URL |
|----------|-----|
| **Beta / Pruebas** | `https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService` |
| **Producción** | `https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService` |

---

## 8. Solución de problemas comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Class 'Greenter\See' not found` | Librería no instalada | `composer require greenter/greenter` |
| `Your requirements could not be resolved` al instalar `simple-qrcode` | Conflicto con bacon-qr-code v3 de Greenter | **No instales simple-qrcode**. El QR se genera con bacon-qr-code directamente |
| `Certificate file not found` | Ruta del .p12 incorrecta | Verificar en Config → SUNAT que el archivo existe |
| SUNAT código 0152 | RUC no está habilitado en beta | Usar RUC `20000000001` en pruebas |
| SUNAT código 2075 | Correlativo ya existe | El sistema genera uno nuevo automáticamente |
| SUNAT código 0101 | XML mal formado | Verificar que RUC, serie y datos del cliente sean correctos |
| `OpenSSL: unable to load` | Clave del certificado incorrecta | Verificar contraseña del .p12 en Configuración |
| QR sin imagen en PDF (solo cuadrado) | DomPDF no renderiza SVG | Activar extensión `imagick` en PHP — el sistema usará PNG automáticamente |

---

## 9. Estructura de archivos generados

Los XMLs y CDRs se guardan en:

```
storage/app/sunat/
  ├── certificado/
  │   └── certificado.p12              ← tu certificado
  ├── xml/
  │   ├── 20000000001-01-F001-1.xml    ← XML factura firmada
  │   ├── 20000000001-03-B001-1.xml    ← XML boleta firmada
  │   ├── 20000000001-07-FC01-1.xml    ← XML nota de crédito (factura)
  │   ├── 20000000001-07-BC01-1.xml    ← XML nota de crédito (boleta)
  │   ├── 20000000001-RA-20260517-1.xml ← XML comunicación de baja
  │   └── 20000000001-RC-20260517-1.xml ← XML resumen diario de boletas
  └── cdr/
      ├── R-20000000001-01-F001-1.zip
      ├── R-20000000001-07-FC01-1.zip
      └── R-ticket-12345678.zip         ← CDR de ticket (baja/resumen)
```

---

## 10. Módulos adicionales SUNAT

El sistema incluye además:

### 📝 Notas de Crédito (`/notas-credito`)
- Catálogo SUNAT 09 (10 motivos: anulación, devolución, descuento, etc.)
- Series FC01 (notas a facturas) / BC01 (notas a boletas)
- Permite acreditar total o parcial del comprobante afectado
- Sólo se pueden emitir sobre comprobantes ya **aceptados** por SUNAT

### 🚫 Comunicación de Baja (`/comunicaciones-baja`)
- Para anular electrónicamente **facturas** ya emitidas
- Formato `RA-YYYYMMDD-####`
- Genera ticket que se consulta posteriormente para obtener CDR
- Las **boletas** se anulan mediante el Resumen Diario, no por baja

### 📋 Resumen Diario de Boletas (`/resumen-boletas`)
- Reporta todas las boletas emitidas en una fecha (obligatorio en producción)
- Plazo: máximo al 7° día calendario siguiente
- Formato `RC-YYYYMMDD-####`
- Incluye boletas activas y dadas de baja
- Genera ticket que se consulta para obtener CDR

---

## 11. Migración

Para aplicar todas las nuevas tablas:

```bash
php artisan migrate
```

Esto crea:
- `add_sunat_to_facturas_table` (12 campos SUNAT en facturas)
- `create_notas_credito_table` (tabla notas_credito)
- `create_comunicaciones_baja_table` (tabla comunicaciones_baja + pivote)
- `create_resumen_boletas_table` (tabla resumen_boletas + pivote)

---

*Sistema de Hospedaje — Laravel 10 + Greenter 5.2 + SUNAT (Facturador Directo)*
*Módulos: Facturas · Boletas · Notas de Crédito · Comunicación de Baja · Resumen Diario*
