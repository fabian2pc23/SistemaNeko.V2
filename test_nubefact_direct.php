<?php
// test_nubefact_direct.php - Prueba directa de NubeFact
error_reporting(E_ALL);
ini_set('display_errors', 1);

$ruta = 'https://api.nubefact.com/api/v1/d4ccebcc-283f-4190-b149-842131694dbf';
$token = '59a220f1edff4b708bac3f1a1e865493e8dec07d6fdc42a687a9939d0ab0cdd7';

$data = [
    "operacion" => "generar_comprobante",
    "tipo_de_comprobante" => 1,
    "serie" => "FFF1",
    "sunat_transaction" => 1,
    "cliente_tipo_de_documento" => "6",
    "cliente_numero_de_documento" => "20000000001", // RUC válido para pruebas
    "cliente_denominacion" => "EMPRESA TEST SAC",
    "cliente_direccion" => "Av. Test 123",
    "fecha_de_emision" => date('d-m-Y'),
    "moneda" => 1,
    "porcentaje_de_igv" => 18.00,
    "total_gravada" => 100.00,
    "total_igv" => 18.00,
    "total" => 118.00,
    "enviar_automaticamente_a_la_sunat" => "true",
    "enviar_automaticamente_al_cliente" => "false",
    "codigo_unico" => "TEST-" . time(),
    "items" => [[
        "unidad_de_medida" => "NIU",
        "codigo" => "PROD001",
        "descripcion" => "Producto Test",
        "cantidad" => 1,
        "valor_unitario" => 100.00,
        "precio_unitario" => 118.00,
        "subtotal" => 100.00,
        "tipo_de_igv" => 1,
        "igv" => 18.00,
        "total" => 118.00,
        "anticipo_regularizacion" => "false"
    ]]
];

echo "<h2>Test Directo NubeFact</h2>";
echo "<p><strong>Ruta:</strong> $ruta</p>";
echo "<p><strong>Token:</strong> " . substr($token, 0, 20) . "...</p>";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $ruta,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Token token="' . $token . '"'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($curl);
$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "<h3>Respuesta</h3>";
echo "<p><strong>HTTP Code:</strong> $httpcode</p>";
if ($error) {
    echo "<p style='color:red'><strong>cURL Error:</strong> $error</p>";
}
echo "<h4>Response Body:</h4>";
echo "<pre style='background:#f5f5f5;padding:15px;border-radius:8px;overflow:auto'>";
echo htmlspecialchars($response);
echo "</pre>";

if ($httpcode == 200 || $httpcode == 201) {
    $decoded = json_decode($response, true);
    if (isset($decoded['enlace_del_pdf'])) {
        echo "<p style='color:green'><strong>✅ ¡Éxito!</strong></p>";
        echo "<a href='" . $decoded['enlace_del_pdf'] . "' target='_blank'>Ver PDF</a>";
    }
}
