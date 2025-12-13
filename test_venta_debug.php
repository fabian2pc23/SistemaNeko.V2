<?php
// test_venta_debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Loading Venta.php...\n";
require_once "modelos/Venta.php";
echo "Venta.php loaded.\n";

try {
    $venta = new Venta();
    echo "Venta instance created.\n";

    // Test dependencies
    if (!function_exists('ejecutarConsultaSimpleFila')) {
        die("Error: ejecutarConsultaSimpleFila not defined.\n");
    }
    echo "ejecutarConsultaSimpleFila is defined.\n";

    // Simulate parameters
    $idcliente = 1; // Existing user??
    $idusuario = 1; // Admin
    $tipo_comprobante = 'Boleta';
    $serie_comprobante = 'B001';
    $num_comprobante = '9999';
    $fecha_hora = date('Y-m-d H:i:s');
    $impuesto = 18;
    $total_venta = 100;
    $idarticulo = [5]; // An article ID that exists
    $cantidad = [1];
    $precio_venta = [10];
    $descuento = [0];

    echo "Calling insertar...\n";
    $rspta = $venta->insertar(
        $idcliente,
        $idusuario,
        $tipo_comprobante,
        $serie_comprobante,
        $num_comprobante,
        $fecha_hora,
        $impuesto,
        $total_venta,
        $idarticulo,
        $cantidad,
        $precio_venta,
        $descuento
    );
    
    echo "Result: " . $rspta . "\n";
    
} catch (Throwable $e) {
    echo "Caught Exception/Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
