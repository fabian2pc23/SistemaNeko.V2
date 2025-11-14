<?php
// modelos/Rol.php
require_once "../config/Conexion.php";

Class Rol {

  // Implementar constructor
  public function __construct() {}

  // ==================== INSERTAR ROL ====================
  public function insertar($nombre) {
    $sql = "INSERT INTO rol_usuarios (nombre, estado, creado_en)
            VALUES ('$nombre', 1, NOW())";
    $idarticulo = ejecutarConsulta_retornarID($sql);
    return $idarticulo;
  }

  // ==================== EDITAR ROL ====================
  public function editar($idrol, $nombre) {
    $sql = "UPDATE rol_usuarios 
            SET nombre='$nombre' 
            WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // ==================== DESACTIVAR ROL ====================
  public function desactivar($idrol) {
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
}
?>