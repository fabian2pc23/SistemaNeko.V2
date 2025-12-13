<?php
/**
 * Script de prueba para verificar el sistema de checkout
 * Acceder a: http://localhost/neko_sac_store/public/test_checkout.php
 */

// Activar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración
require_once __DIR__ . '/../config/config.php';

echo "<h1>🔍 Test de Sistema de Checkout</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// 1. Verificar BASE_URL
echo "<h2>1. Configuración</h2>";
echo "<p class='info'>BASE_URL: " . BASE_URL . "</p>";
echo "<p class='info'>DB_HOST: " . DB_HOST . "</p>";
echo "<p class='info'>DB_NAME: " . DB_NAME . "</p>";

// 2. Verificar conexión a BD
echo "<h2>2. Conexión a Base de Datos</h2>";
try {
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Conexión exitosa</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

// 3. Verificar tablas
echo "<h2>3. Tablas Necesarias</h2>";
$tablas = [
    'pedido_online',
    'detalle_pedido_online',
    'transaccion_pago',
    'pago_yape_simulado',
    'pago_tarjeta_simulado',
    'articulo'
];

foreach ($tablas as $tabla) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabla '$tabla' existe</p>";
        } else {
            echo "<p class='error'>❌ Tabla '$tabla' NO existe</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error verificando '$tabla': " . $e->getMessage() . "</p>";
    }
}

// 4. Verificar artículos en BD
echo "<h2>4. Productos en Base de Datos</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM articulo WHERE condicion = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p class='info'>Total de productos activos: " . $result['total'] . "</p>";
    
    if ($result['total'] == 0) {
        echo "<p class='error'>⚠️ No hay productos activos en la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error consultando productos: " . $e->getMessage() . "</p>";
}

// 5. Verificar sesión
echo "<h2>5. Sesión PHP</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p class='success'>✅ Sesión iniciada</p>";
echo "<p class='info'>Session ID: " . session_id() . "</p>";

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<p class='info'>Carrito tiene " . count($_SESSION['cart']) . " item(s)</p>";
} else {
    echo "<p class='info'>Carrito vacío</p>";
}

// 6. Test de inserción
echo "<h2>6. Test de Inserción de Pedido</h2>";
try {
    // Generar código de prueba
    $codigoPrueba = 'TEST-' . date('YmdHis');
    
    $stmt = $db->prepare("
        INSERT INTO pedido_online (
            codigo_pedido, nombre_cliente, email_cliente, telefono_cliente,
            direccion_entrega, subtotal, igv, total, metodo_pago,
            fecha_pedido
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $codigoPrueba,
        'Test Usuario',
        'test@test.com',
        '999999999',
        'Dirección de prueba',
        100.00,
        18.00,
        118.00,
        'yape'
    ]);
    
    $idPedido = $db->lastInsertId();
    echo "<p class='success'>✅ Pedido de prueba creado con ID: $idPedido</p>";
    echo "<p class='success'>✅ Código: $codigoPrueba</p>";
    
    // Eliminar el pedido de prueba
    $stmt = $db->prepare("DELETE FROM pedido_online WHERE idpedido = ?");
    $stmt->execute([$idPedido]);
    echo "<p class='info'>🗑️ Pedido de prueba eliminado</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error en test de inserción: " . $e->getMessage() . "</p>";
}

// 7. Verificar archivos
echo "<h2>7. Archivos del Sistema</h2>";
$archivos = [
    '../src/Controllers/CheckoutController.php',
    '../src/Views/checkout/index.php',
    '../src/Views/checkout/confirmacion.php',
    'assets/js/checkout.js',
    'assets/css/app.css'
];

foreach ($archivos as $archivo) {
    $rutaCompleta = __DIR__ . '/' . $archivo;
    if (file_exists($rutaCompleta)) {
        echo "<p class='success'>✅ $archivo existe</p>";
    } else {
        echo "<p class='error'>❌ $archivo NO existe</p>";
    }
}

// 8. Test de JSON
echo "<h2>8. Test de Respuesta JSON</h2>";
echo "<p class='info'>Simulando respuesta JSON...</p>";
echo "<pre>";
$testResponse = [
    'success' => true,
    'idpedido' => 123,
    'codigo_pedido' => 'PED-20251117-0001',
    'metodo_pago' => 'yape',
    'total' => 118.00
];
echo json_encode($testResponse, JSON_PRETTY_PRINT);
echo "</pre>";

echo "<h2>✅ Test Completo</h2>";
echo "<p class='success'>Si todos los tests pasaron, el sistema está listo.</p>";
echo "<p class='info'><a href='" . BASE_URL . "'>Volver a la tienda</a></p>";
?>
