<?php
// test_views.php - Script de prueba para verificar vistas
session_start();

echo "<h2>Estado de la Sesión</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Permisos</h2>";
echo "Almacén: " . (isset($_SESSION['almacen']) ? $_SESSION['almacen'] : 'NO SET') . "<br>";
echo "Acceso: " . (isset($_SESSION['acceso']) ? $_SESSION['acceso'] : 'NO SET') . "<br>";

echo "<h2>Verificación de Archivos</h2>";

$files = ['categoria.php', 'usuario.php', 'articulo.php'];
foreach ($files as $file) {
    $path = __DIR__ . "/vistas/$file";
    echo "<strong>$file:</strong> ";
    if (file_exists($path)) {
        echo "✅ Existe (". filesize($path) . " bytes)<br>";
    } else {
        echo "❌ NO EXISTE<br>";
    }
}

echo "<h2>Enlaces de Prueba</h2>";
echo '<a href="vistas/categoria.php">Ir a Categoría</a><br>';
echo '<a href="vistas/usuario.php">Ir a Usuario</a><br>';
echo '<a href="vistas/articulo.php">Ir a Artículo</a><br>';
?>
