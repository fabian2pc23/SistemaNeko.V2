<?php

/**
 * sunat_config.php - Configuración de Facturación Electrónica
 * 
 * Este archivo contiene la configuración para la facturación electrónica con SUNAT.
 * Soporta múltiples proveedores: Greenter (directo SUNAT) y NubeFact (servicio externo)
 * 
 * SISTEMA DUAL: Puedes usar uno, otro, o ambos proveedores simultáneamente.
 */

// ============================================
// PROVEEDORES DE FACTURACIÓN ELECTRÓNICA
// ============================================
// Activa/desactiva cada proveedor según tus necesidades

// GREENTER - Conexión directa con SUNAT (gratuito, requiere certificado)
define('USAR_GREENTER', true);

// NUBEFACT - Servicio externo (requiere licencia/suscripción)
define('USAR_NUBEFACT', true);

// ============================================
// MODO DE OPERACIÓN
// ============================================
// 'beta'       = Modo pruebas (SUNAT BETA / NubeFact Demo)
// 'produccion' = Modo producción con certificados/tokens reales
define('SUNAT_MODO', 'beta');

// ============================================
// DATOS DE LA EMPRESA EMISORA
// ============================================
define('EMPRESA_RUC', '10406980788');
define('EMPRESA_RAZON_SOCIAL', 'CORTEZ FLORES ANDREA DEL CARMEN');
define('EMPRESA_NOMBRE_COMERCIAL', 'Ferretería Neko');
define('EMPRESA_DIRECCION', 'CAR. LAMBAYEQUE CARRETERA LAMBAYEQUE (FRENTE AL GRIFO PRIMAX)');
define('EMPRESA_UBIGEO', '140114');
define('EMPRESA_DEPARTAMENTO', 'LAMBAYEQUE');
define('EMPRESA_PROVINCIA', 'LAMBAYEQUE');
define('EMPRESA_DISTRITO', 'LAMBAYEQUE');
define('EMPRESA_URBANIZACION', '-');
define('EMPRESA_COD_LOCAL', '0000'); // 0000 = Establecimiento principal

// ============================================
// CONFIGURACIÓN GREENTER (Conexión directa SUNAT)
// ============================================
if (SUNAT_MODO === 'beta') {
    // Credenciales BETA (para pruebas)
    define('SOL_USUARIO', 'MODDATOS');
    define('SOL_CLAVE', 'moddatos');

    // Series BETA Greenter
    define('GREENTER_SERIE_FACTURA', 'F001');
    define('GREENTER_SERIE_BOLETA', 'B001');
} else {
    // Credenciales PRODUCCIÓN (reemplazar con las reales)
    define('SOL_USUARIO', 'TU_USUARIO_SOL');
    define('SOL_CLAVE', 'TU_CLAVE_SOL');

    // Series PRODUCCIÓN Greenter
    define('GREENTER_SERIE_FACTURA', 'F001');
    define('GREENTER_SERIE_BOLETA', 'B001');
}

// Certificado digital para Greenter
if (SUNAT_MODO === 'beta') {
    define('CERT_PATH', __DIR__ . '/../LLAMAPECERTIFICADODEMO10406980788_cert_out.pem');
} else {
    define('CERT_PATH', __DIR__ . '/../certificados/certificado_produccion.pem');
}

// ============================================
// CONFIGURACIÓN NUBEFACT
// ============================================
if (SUNAT_MODO === 'beta') {
    // NubeFact DEMO - Credenciales del usuario
    define('NUBEFACT_RUTA', 'https://api.nubefact.com/api/v1/d4ccebcc-283f-4190-b149-842131694dbf');
    define('NUBEFACT_TOKEN', '59a220f1edff4b708bac3f1a1e865493e8dec07d6fdc42a687a9939d0ab0cdd7');

    // Series DEMO NubeFact
    define('NUBEFACT_SERIE_FACTURA', 'FFF1');
    define('NUBEFACT_SERIE_BOLETA', 'BBB1');
    define('NUBEFACT_SERIE_TICKET', 'NNN1'); // Notas de venta / Tickets
} else {
    // NubeFact PRODUCCIÓN (requiere licencia)
    define('NUBEFACT_RUTA', 'https://api.nubefact.com/api/v1/TU_RUTA_NUBEFACT');
    define('NUBEFACT_TOKEN', 'TU_TOKEN_NUBEFACT');

    // Series PRODUCCIÓN NubeFact
    define('NUBEFACT_SERIE_FACTURA', 'F001');
    define('NUBEFACT_SERIE_BOLETA', 'B001');
    define('NUBEFACT_SERIE_TICKET', 'T001');
}

// ============================================
// RUTAS DE ALMACENAMIENTO
// ============================================
define('PATH_XML', __DIR__ . '/../files/facturas/xml/');
define('PATH_CDR', __DIR__ . '/../files/facturas/cdr/');
define('PATH_PDF', __DIR__ . '/../files/facturas/pdf/');

// ============================================
// OPCIONES ADICIONALES
// ============================================
define('GUARDAR_XML_LOCAL', true);       // Guardar copia del XML localmente
define('GUARDAR_CDR_LOCAL', true);       // Guardar copia del CDR localmente

// ============================================
// TIPOS DE COMPROBANTE POR PROVEEDOR
// ============================================
// Define qué proveedor usar para cada tipo de comprobante
// Opciones: 'greenter', 'nubefact', 'ambos', 'ninguno'
define('FACTURA_PROVEEDOR', 'ambos');   // Para Facturas
define('BOLETA_PROVEEDOR', 'ambos');    // Para Boletas
define('TICKET_PROVEEDOR', 'nubefact'); // Tickets solo con NubeFact (no van a SUNAT)
