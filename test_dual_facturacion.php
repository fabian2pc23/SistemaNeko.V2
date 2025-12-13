<?php

/**
 * test_dual_facturacion.php - Prueba del Sistema Dual de Facturación
 * 
 * Este script prueba tanto Greenter como NubeFact simultáneamente
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'vendor/autoload.php';
require_once 'config/sunat_config.php';
require_once 'config/GreenterApi.php';
require_once 'config/SunatApi.php';

echo "<html><head><title>Test Sistema Dual</title>";
echo "<style>
body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
.container { max-width: 800px; margin: 0 auto; }
.card { background: white; border-radius: 12px; padding: 20px; margin: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { background: #f0fdf4; border: 1px solid #86efac; }
.error { background: #fef2f2; border: 1px solid #fca5a5; }
.info { background: #eff6ff; border: 1px solid #93c5fd; }
h1 { color: #1e293b; }
h2 { color: #374151; margin-top: 0; }
h3 { color: #4b5563; }
.btn { display: inline-block; padding: 10px 18px; border-radius: 8px; text-decoration: none; margin: 5px; font-weight: 600; }
.btn-blue { background: #2563eb; color: white; }
.btn-green { background: #059669; color: white; }
.btn-red { background: #dc2626; color: white; }
.btn-gray { background: #6b7280; color: white; }
code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🧪 Test Sistema Dual de Facturación Electrónica</h1>";

// Configuración
echo "<div class='card info'>";
echo "<h2>⚙️ Configuración Actual</h2>";
echo "<p><strong>Modo:</strong> " . SUNAT_MODO . "</p>";
echo "<p><strong>Greenter Activo:</strong> " . (USAR_GREENTER ? '✅ SÍ' : '❌ NO') . "</p>";
echo "<p><strong>NubeFact Activo:</strong> " . (USAR_NUBEFACT ? '✅ SÍ' : '❌ NO') . "</p>";
echo "<p><strong>Factura usa:</strong> " . FACTURA_PROVEEDOR . "</p>";
echo "<p><strong>Boleta usa:</strong> " . BOLETA_PROVEEDOR . "</p>";
echo "</div>";

// Datos de prueba
$correlativo = time() % 100000;
$dataTest = [
    'tipo_comprobante' => 'Factura',
    'serie' => 'F001',
    'numero' => str_pad($correlativo, 8, '0', STR_PAD_LEFT),
    'fecha' => date('Y-m-d'),
    'cliente' => 'EMPRESA DE PRUEBA SAC',
    'num_documento' => '20000000001', // RUC válido para pruebas
    'direccion' => 'Av. Test 123, Lima',
    'gravada' => 100.00,
    'igv' => 18.00,
    'total' => 118.00,
    'total_letras' => 'CIENTO DIECIOCHO CON 00/100 SOLES',
    'items' => [
        [
            'codigo' => 'PROD001',
            'descripcion' => 'Producto de Prueba Unitario',
            'cantidad' => 2,
            'valor_unitario' => 50.00,
            'precio_unitario' => 59.00,
            'base_igv' => 100.00,
            'igv' => 18.00
        ]
    ]
];

echo "<div class='card'>";
echo "<h2>📄 Datos del Comprobante de Prueba</h2>";
echo "<p><strong>Serie-Número:</strong> F001-" . $dataTest['numero'] . "</p>";
echo "<p><strong>Cliente:</strong> " . $dataTest['cliente'] . "</p>";
echo "<p><strong>RUC:</strong> " . $dataTest['num_documento'] . "</p>";
echo "<p><strong>Total:</strong> S/ " . $dataTest['total'] . "</p>";
echo "</div>";

// =========================================
// PRUEBA GREENTER
// =========================================
echo "<div class='card'>";
echo "<h2>🟢 GREENTER (SUNAT Directo)</h2>";

if (USAR_GREENTER) {
    echo "<p>Conectando directamente con SUNAT...</p>";
    $inicio = microtime(true);

    try {
        $greenter = new GreenterApi();
        $resGreenter = $greenter->emitirComprobante($dataTest);
        $tiempo = round(microtime(true) - $inicio, 2);

        if ($resGreenter['exito']) {
            echo "<div class='card success'>";
            echo "<h3>✅ Éxito - {$tiempo}s</h3>";
            echo "<p><strong>Mensaje:</strong> " . $resGreenter['sunat_description'] . "</p>";
            echo "<p><strong>XML Local:</strong> <code>" . $resGreenter['xml_local'] . "</code></p>";
            echo "<p><strong>CDR Local:</strong> <code>" . $resGreenter['cdr_local'] . "</code></p>";

            echo "<div style='margin-top:15px'>";
            if (!empty($resGreenter['xml_local'])) {
                echo "<a href='" . $resGreenter['xml_local'] . "' target='_blank' class='btn btn-blue'>📄 Ver XML</a>";
            }
            if (!empty($resGreenter['cdr_local'])) {
                echo "<a href='" . $resGreenter['cdr_local'] . "' class='btn btn-green' download>📦 Descargar CDR</a>";
            }
            echo "</div>";
            echo "</div>";
        } else {
            echo "<div class='card error'>";
            echo "<h3>❌ Error</h3>";
            echo "<p>" . $resGreenter['mensaje'] . "</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='card error'>";
        echo "<h3>❌ Excepción</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>⚠️ Greenter está desactivado en la configuración.</p>";
}
echo "</div>";

// =========================================
// PRUEBA NUBEFACT
// =========================================
echo "<div class='card'>";
echo "<h2>🔵 NUBEFACT (Servicio Externo)</h2>";

if (USAR_NUBEFACT) {
    echo "<p>Conectando con NubeFact API...</p>";

    // Preparar datos NubeFact
    $json_nubefact = [
        "operacion" => "generar_comprobante",
        "tipo_de_comprobante" => 1, // Factura
        "serie" => NUBEFACT_SERIE_FACTURA,
        "sunat_transaction" => 1,
        "cliente_tipo_de_documento" => "6",
        "cliente_numero_de_documento" => $dataTest['num_documento'],
        "cliente_denominacion" => $dataTest['cliente'],
        "cliente_direccion" => $dataTest['direccion'],
        "fecha_de_emision" => date('d-m-Y'),
        "moneda" => 1,
        "porcentaje_de_igv" => 18.00,
        "total_gravada" => $dataTest['gravada'],
        "total_igv" => $dataTest['igv'],
        "total" => $dataTest['total'],
        "enviar_automaticamente_a_la_sunat" => "true",
        "enviar_automaticamente_al_cliente" => "false",
        "codigo_unico" => "TEST-" . time(),
        "items" => [
            [
                "unidad_de_medida" => "NIU",
                "codigo" => "PROD001",
                "descripcion" => "Producto de Prueba",
                "cantidad" => 2,
                "valor_unitario" => 50.00,
                "precio_unitario" => 59.00,
                "subtotal" => 100.00,
                "tipo_de_igv" => 1,
                "igv" => 18.00,
                "total" => 118.00,
                "anticipo_regularizacion" => "false"
            ]
        ]
    ];

    $inicio = microtime(true);

    try {
        $sunat = new SunatApi();
        $resApi = $sunat->emitirComprobante($json_nubefact);
        $tiempo = round(microtime(true) - $inicio, 2);
        $respuesta = json_decode($resApi['response'], true);

        if ($resApi['status'] == 200 || $resApi['status'] == 201) {
            if (isset($respuesta['errors'])) {
                echo "<div class='card error'>";
                echo "<h3>❌ Error NubeFact</h3>";
                echo "<p>" . $respuesta['errors'] . "</p>";
                echo "</div>";
            } else {
                echo "<div class='card success'>";
                echo "<h3>✅ Éxito - {$tiempo}s</h3>";
                echo "<p><strong>Serie-Número:</strong> " . ($respuesta['serie'] ?? '') . "-" . ($respuesta['numero'] ?? '') . "</p>";

                echo "<div style='margin-top:15px'>";
                if (!empty($respuesta['enlace_del_pdf'])) {
                    echo "<a href='" . $respuesta['enlace_del_pdf'] . "' target='_blank' class='btn btn-red'>📄 Ver PDF</a>";
                }
                if (!empty($respuesta['enlace_del_xml'])) {
                    echo "<a href='" . $respuesta['enlace_del_xml'] . "' target='_blank' class='btn btn-blue'>📝 Ver XML</a>";
                }
                if (!empty($respuesta['enlace_del_cdr'])) {
                    echo "<a href='" . $respuesta['enlace_del_cdr'] . "' class='btn btn-green' download>📦 CDR</a>";
                }
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<div class='card error'>";
            echo "<h3>❌ Error HTTP " . $resApi['status'] . "</h3>";
            echo "<p>" . ($respuesta['errors'] ?? $resApi['response']) . "</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div class='card error'>";
        echo "<h3>❌ Excepción</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    echo "<p>⚠️ NubeFact está desactivado en la configuración.</p>";
}
echo "</div>";

echo "<div class='card'>";
echo "<h2>🎉 Resumen</h2>";
echo "<p>El sistema dual permite usar <strong>Greenter</strong> para conexión directa con SUNAT (gratuito) y <strong>NubeFact</strong> como servicio OSE alternativo (con licencia).</p>";
echo "<p>Puedes configurar qué proveedor usar para cada tipo de comprobante en <code>config/sunat_config.php</code></p>";
echo "<br><a href='vistas/venta.php' class='btn btn-gray'>← Volver a Ventas</a>";
echo "</div>";

echo "</div></body></html>";
