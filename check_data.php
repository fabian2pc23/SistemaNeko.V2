<?php
require 'config/Conexion.php';
$sql = "SELECT MIN(fecha_hora) as min, MAX(fecha_hora) as max, COUNT(*) as count FROM ingreso";
$res = $conexion->query($sql);
if ($res) {
    print_r($res->fetch_assoc());
} else {
    echo "Error: " . $conexion->error;
}
