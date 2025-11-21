<?php
require "config/Conexion.php";

echo "Checking tables...\n";

$tables = ['usuario', 'rol_usuarios', 'usuario_roles_new', 'rol_permiso', 'permiso', 'usuario_permiso'];

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $rs = ejecutarConsultaSimpleFila($sql);
    if ($rs) {
        echo "✅ Table '$table' exists.\n";
    } else {
        echo "❌ Table '$table' DOES NOT exist.\n";
    }
}

echo "\nTesting listar() query...\n";
$sql="SELECT 
        u.idusuario,
        u.nombre,
        (SELECT GROUP_CONCAT(r.nombre SEPARATOR ', ')
        FROM usuario_roles_new ur
        INNER JOIN rol_usuarios r ON ur.id_rol = r.id_rol
        WHERE ur.idusuario = u.idusuario) AS todos_roles
      FROM usuario u
      LIMIT 1";

$rs = ejecutarConsulta($sql);
if ($rs) {
    echo "✅ Query executed successfully.\n";
    $row = $rs->fetch_assoc();
    print_r($row);
} else {
    echo "❌ Query FAILED.\n";
    global $conexion;
    echo "Error: " . $conexion->error . "\n";
}
?>
