<?php
// validate_documento.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once "../config/Conexion.php";

// Función para validar documento
function validarDocumento($tipo_documento, $num_documento, $idusuario = 0) {
    global $conexion;
    
    if (empty($tipo_documento) || empty($num_documento)) {
        return [
            'success' => false,
            'valid' => false,
            'exists' => false,
            'message' => 'Tipo de documento y número son requeridos'
        ];
    }
    
    // Escapar valores para prevenir SQL injection
    $tipo_escaped = $conexion->real_escape_string($tipo_documento);
    $num_escaped = $conexion->real_escape_string($num_documento);
    $idusuario = (int)$idusuario;
    
    // Consulta SQL para verificar si el documento ya existe
    if ($idusuario > 0) {
        // Al editar, excluir el usuario actual
        $sql = "SELECT COUNT(*) as total, 
                       u.nombre, 
                       u.email 
                FROM usuario u 
                WHERE u.tipo_documento = '$tipo_escaped' 
                  AND u.num_documento = '$num_escaped' 
                  AND u.idusuario != '$idusuario' 
                LIMIT 1";
    } else {
        // Al crear nuevo usuario
        $sql = "SELECT COUNT(*) as total, 
                       u.nombre, 
                       u.email 
                FROM usuario u 
                WHERE u.tipo_documento = '$tipo_escaped' 
                  AND u.num_documento = '$num_escaped' 
                LIMIT 1";
    }
    
    $result = ejecutarConsultaSimpleFila($sql);
    
    if (!$result) {
        return [
            'success' => false,
            'valid' => false,
            'exists' => false,
            'message' => 'Error al consultar la base de datos'
        ];
    }
    
    $total = (int)($result['total'] ?? 0);
    
    if ($total > 0) {
        // El documento ya existe en la base de datos
        $nombre = $result['nombre'] ?? 'Usuario existente';
        $email = $result['email'] ?? '';
        
        return [
            'success' => true,
            'valid' => false,
            'exists' => true,
            'message' => "Este documento ya está registrado para: $nombre",
            'usuario_existente' => [
                'nombre' => $nombre,
                'email' => $email
            ]
        ];
    }
    
    // El documento está disponible
    return [
        'success' => true,
        'valid' => true,
        'exists' => false,
        'message' => 'Documento disponible'
    ];
}

// Procesar la petición
$tipo_documento = isset($_GET['tipo_documento']) ? trim($_GET['tipo_documento']) : '';
$num_documento = isset($_GET['num_documento']) ? trim($_GET['num_documento']) : '';
$idusuario = isset($_GET['idusuario']) ? (int)$_GET['idusuario'] : 0;

$resultado = validarDocumento($tipo_documento, $num_documento, $idusuario);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;
?>