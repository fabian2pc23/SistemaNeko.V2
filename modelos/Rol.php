<?php
// modelos/Rol.php
require_once "../config/Conexion.php";

Class Rol {

  // Implementar constructor
  public function __construct() {}

  // ==================== INSERTAR ROL ====================
  public function insertar($nombre) {
    // Validar duplicados antes de insertar
    $errorDuplicado = $this->validarDuplicado($nombre);
    if ($errorDuplicado) {
      return $errorDuplicado; // Retorna el mensaje de error
    }

    $sql = "INSERT INTO rol_usuarios (nombre, estado, creado_en)
            VALUES ('$nombre', 1, NOW())";
    $idarticulo = ejecutarConsulta_retornarID($sql);
    return $idarticulo;
  }

  // ==================== EDITAR ROL ====================
  public function editar($idrol, $nombre) {
    // Validar duplicados antes de editar
    $errorDuplicado = $this->validarDuplicado($nombre, $idrol);
    if ($errorDuplicado) {
      return $errorDuplicado; // Retorna el mensaje de error
    }

    $sql = "UPDATE rol_usuarios 
            SET nombre='$nombre' 
            WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // ==================== DESACTIVAR ROL ====================
  public function desactivar($idrol) {
    // Verificar si es Administrador
    $sqlCheck = "SELECT nombre FROM rol_usuarios WHERE id_rol='$idrol'";
    $res = ejecutarConsultaSimpleFila($sqlCheck);
    if ($res && (strcasecmp($res['nombre'], 'Administrador') === 0 || strcasecmp($res['nombre'], 'Admin') === 0)) {
      return "⛔ No puedes desactivar el rol de Administrador.";
    }

    $sql = "UPDATE rol_usuarios SET estado='0' WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // ==================== ACTIVAR ROL ====================
  public function activar($idrol) {
    $sql = "UPDATE rol_usuarios SET estado='1' WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // ==================== MOSTRAR UN ROL ====================
  public function mostrar($idrol) {
    $sql = "SELECT * FROM rol_usuarios WHERE id_rol='$idrol'";
    return ejecutarConsultaSimpleFila($sql);
  }

  // ==================== LISTAR TODOS LOS ROLES ====================
  public function listar() {
    $sql = "SELECT * FROM rol_usuarios ORDER BY creado_en DESC";
    return ejecutarConsulta($sql);
  }

  // ==================== LISTAR SOLO ROLES ACTIVOS ====================
  public function listarActivos() {
    $sql = "SELECT * FROM rol_usuarios WHERE estado=1 ORDER BY nombre ASC";
    return ejecutarConsulta($sql);
  }

  // ==================== INSERTAR PERMISOS ====================
  public function insertarPermisos($idrol, $permisos) {
    if (empty($permisos) || !is_array($permisos)) {
      return true; // Si no hay permisos, no hacer nada
    }

    $valores = array();
    foreach ($permisos as $idpermiso) {
      $idpermiso = (int)$idpermiso; // Sanitizar
      $valores[] = "('$idrol', '$idpermiso')";
    }

    if (count($valores) > 0) {
      $sql = "INSERT INTO rol_permiso (id_rol, idpermiso) VALUES " . implode(',', $valores);
      return ejecutarConsulta($sql);
    }
    
    return true;
  }

  // ==================== BORRAR PERMISOS DE UN ROL ====================
  public function borrarPermisos($idrol) {
    $sql = "DELETE FROM rol_permiso WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // ==================== VERIFICAR SI UN ROL TIENE UN PERMISO ====================
  public function verificarPermiso($idrol, $idpermiso) {
    $sql = "SELECT * FROM rol_permiso 
            WHERE id_rol='$idrol' AND idpermiso='$idpermiso'";
    $resultado = ejecutarConsulta($sql);
    return $resultado->num_rows > 0;
  }

  // ==================== OBTENER PERMISOS DE UN ROL ====================
  public function obtenerPermisos($idrol) {
    $sql = "SELECT p.idpermiso, p.nombre 
            FROM permiso p
            INNER JOIN rol_permiso rp ON p.idpermiso = rp.idpermiso
            WHERE rp.id_rol = '$idrol'
            ORDER BY p.nombre ASC";
    return ejecutarConsulta($sql);
  }

  // ==================== CONTAR ROLES ====================
  public function contarRoles() {
    $sql = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as activos,
              SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as inactivos
            FROM rol_usuarios";
    return ejecutarConsultaSimpleFila($sql);
  }

  // ==================== VERIFICAR SI EXISTE NOMBRE ====================
  public function existeNombre($nombre, $idrol = 0) {
    if ($idrol > 0) {
      $sql = "SELECT id_rol FROM rol_usuarios 
              WHERE nombre='$nombre' AND id_rol != '$idrol'";
    } else {
      $sql = "SELECT id_rol FROM rol_usuarios WHERE nombre='$nombre'";
    }
    $resultado = ejecutarConsulta($sql);
    return $resultado->num_rows > 0;
  }

  // ==================== VALIDAR DUPLICADOS (Estricto) ====================
  public function validarDuplicado($nombre, $idrol = 0)
  {
    // 1. Normalización y Limpieza
    $nombre = trim($nombre);
    $nombreNorm = mb_strtolower($nombre, 'UTF-8');
    
    // Extraer solo letras (Alpha-only)
    $nombreAlpha = preg_replace('/[^a-z\x{00C0}-\x{00FF}]/u', '', $nombreNorm);
    $usarAlpha = ($nombreAlpha !== '');

    $sql = "SELECT id_rol, nombre FROM rol_usuarios WHERE id_rol != '$idrol'";
    $resultado = ejecutarConsulta($sql);

    while ($reg = $resultado->fetch_object()) {
      $dbNombre = $reg->nombre;
      $dbNombreNorm = mb_strtolower($dbNombre, 'UTF-8');
      $dbNombreAlpha = preg_replace('/[^a-z\x{00C0}-\x{00FF}]/u', '', $dbNombreNorm);

      // Regla 1: Comparación "Alpha-only"
      if ($usarAlpha && $nombreAlpha === $dbNombreAlpha) {
        return "⚠️ El rol '$nombre' es demasiado similar a '$dbNombre' (variación numérica o de símbolos).";
      }

      // Regla 2: Comparación Directa
      if (!$usarAlpha && $nombreNorm === $dbNombreNorm) {
        return "⚠️ El rol '$dbNombre' ya existe.";
      }

      // Regla 3: Levenshtein sobre la parte Alpha
      if ($usarAlpha && $dbNombreAlpha !== '') {
        $lev = levenshtein($nombreAlpha, $dbNombreAlpha);
        $largo = strlen($nombreAlpha);
        
        // Tolerancia estricta
        $tolerancia = ($largo <= 4) ? 0 : 1;

        if ($lev <= $tolerancia) {
          return "⚠️ El rol '$nombre' es muy similar a '$dbNombre'.";
        }
      }
    }

    return false;
  }
}
?>