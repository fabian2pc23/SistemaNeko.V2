<?php
// debug_usuario.php - Script para depurar usuario.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Depuración de usuario.php</h2>";

// Iniciar sesión
session_start();

echo "<h3>Sesión:</h3><pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Verificando archivo:</h3>";
$file = __DIR__ . '/vistas/usuario.php';
echo "Ruta: $file<br>";
echo "Existe: " . (file_exists($file) ? 'SÍ' : 'NO') . "<br>";
echo "Tamaño: " . filesize($file) . " bytes<br>";

echo "<h3>Intentando incluir...</h3>";
try {
    include $file;
} catch (Exception $e) {
    echo "<div style='color:red'>ERROR: " . $e->getMessage() . "</div>";
}
?>
