<?php
// modelos/Usuario.php - VERSIÓN CORREGIDA Y COMPATIBLE
require "../config/Conexion.php";

class Usuario
{
    public function __construct(){}

    /* ===========================
       Helpers internos
       =========================== */
    private function obtenerNombreRol($id_rol){
        $id_rol = (int)$id_rol;
        $sql = "SELECT nombre FROM rol_usuarios WHERE id_rol='$id_rol' LIMIT 1";
        $row = ejecutarConsultaSimpleFila($sql);
        return $row && isset($row['nombre']) ? $row['nombre'] : null;
    }

    private function avatarPorRol($cargo){
        $k = mb_strtolower(trim((string)$cargo),'UTF-8');
        if ($k === 'administrador' || $k === 'admin') return 'administrador.png';
        if ($k === 'almacenero')   return 'almacenero.png';
        if ($k === 'vendedor')     return 'vendedor.png';
        return 'usuario.png';
    }

    public function permisos_por_rol($id_rol){
        return $this->permisosDeRol($id_rol);
    }
    
    private function permisosDeRol($id_rol){
        $id_rol = (int)$id_rol;
        $sql = "SELECT idpermiso FROM rol_permiso WHERE id_rol='$id_rol'";
        $rs  = ejecutarConsulta($sql);
        $out = array();
        if ($rs) while ($row = $rs->fetch_assoc()) { $out[] = (int)$row['idpermiso']; }
        return $out;
    }
    
    private function setPermisosUsuario($idusuario, $permisos){
        $idusuario = (int)$idusuario;
        if (!is_array($permisos) || count($permisos) === 0) return true;
        ejecutarConsulta("DELETE FROM usuario_permiso WHERE idusuario='$idusuario'");
        $ok = true;
        foreach ($permisos as $pid){
            $pid = (int)$pid;
            if (!ejecutarConsulta("INSERT INTO usuario_permiso(idusuario,idpermiso) VALUES('$idusuario','$pid')")) $ok=false;
        }
        return $ok;
    }

    /* ============================================================
       MÚLTIPLES ROLES - USAR TABLA usuario_roles_new
       ============================================================ */
    public function asignarRolesMultiples($idusuario, $roles_array){
        $idusuario = (int)$idusuario;
        
        if (!is_array($roles_array) || count($roles_array) === 0) {
            return false;
        }
        
        // Validar que haya al menos un rol principal
        $tienePrincipal = false;
        foreach($roles_array as $r){
            if (isset($r['es_principal']) && (int)$r['es_principal'] === 1) {
                $tienePrincipal = true;
                break;
            }
        }
        
        if (!$tienePrincipal) {
            return false;
        }
        
        // Limpiar roles actuales
        ejecutarConsulta("DELETE FROM usuario_roles_new WHERE idusuario='$idusuario'");
        
        // Insertar nuevos roles
        foreach($roles_array as $rol){
            $id_rol = (int)($rol['id_rol'] ?? 0);
            $es_principal = (int)($rol['es_principal'] ?? 0);
            
            if ($id_rol > 0) {
                ejecutarConsulta("INSERT INTO usuario_roles_new (idusuario, id_rol, es_principal) 
                                  VALUES ('$idusuario', '$id_rol', '$es_principal')");
            }
        }
        
        return true;
    }
    
    public function obtenerRolesUsuario($idusuario){
        $idusuario = (int)$idusuario;
        
        // Intentar usar procedimiento almacenado
        $sql = "SELECT 
                    ur.id_usuario_rol,
                    ur.idusuario,
                    ur.id_rol,
                    r.nombre AS nombre_rol,
                    ur.es_principal,
                    ur.asignado_en
                FROM usuario_roles_new ur
                INNER JOIN rol_usuarios r ON ur.id_rol = r.id_rol
                WHERE ur.idusuario = '$idusuario'
                ORDER BY ur.es_principal DESC, r.nombre ASC";
        
        return ejecutarConsulta($sql);
    }
    
    public function obtenerPermisosAcumulativos($idusuario){
        $idusuario = (int)$idusuario;
        
        $sql = "SELECT DISTINCT 
                    p.idpermiso,
                    p.nombre
                FROM usuario_roles_new ur
                INNER JOIN rol_permiso rp ON ur.id_rol = rp.id_rol
                INNER JOIN permiso p ON rp.idpermiso = p.idpermiso
                WHERE ur.idusuario = '$idusuario'
                ORDER BY p.nombre ASC";
        
        $rs = ejecutarConsulta($sql);
        $permisos = array();
        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $permisos[] = (int)$row['idpermiso'];
            }
        }
        return $permisos;
    }

    /* ============================================================
       INSERTAR
       ============================================================ */
    public function insertar(
        $nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos,
        $roles_array = null, $modo_permisos = ''
    ){
        // Si viene roles_array, usamos múltiples roles
        if (is_array($roles_array) && count($roles_array) > 0) {
            // Obtener cargo del rol principal
            foreach($roles_array as $r){
                if (isset($r['es_principal']) && (int)$r['es_principal'] === 1) {
                    $cargo = $this->obtenerNombreRol($r['id_rol']) ?: $cargo;
                    break;
                }
            }
        }

        $tieneRoles = is_array($roles_array) && count($roles_array) > 0;
        $tienePermisos = is_array($permisos) && count($permisos) > 0;

        $condicion = ($tieneRoles || $tienePermisos) ? '1' : '3';

        if ($imagen === null || $imagen === '') {
            $imagen = $this->avatarPorRol($cargo);
        }

        $cols = "nombre,tipo_documento,num_documento,direccion,telefono,email,cargo,clave,imagen,condicion";
        $vals = "'$nombre','$tipo_documento','$num_documento','$direccion','$telefono','$email','$cargo','$clave','$imagen','$condicion'";

        // Si hay un solo rol en el array, también guardarlo en id_rol (compatibilidad)
        if ($tieneRoles && count($roles_array) === 1) {
            $id_rol = (int)$roles_array[0]['id_rol'];
            $cols .= ",id_rol";
            $vals .= ",'$id_rol'";
        }

        $sql = "INSERT INTO usuario ($cols) VALUES ($vals)";
        $idusuarionew = ejecutarConsulta_retornarID($sql);

        // Asignar múltiples roles
        if ($tieneRoles) {
            $this->asignarRolesMultiples($idusuarionew, $roles_array);
            $permisos = $this->obtenerPermisosAcumulativos($idusuarionew);
        }

        $this->setPermisosUsuario($idusuarionew, $permisos);
        return true;
    }

    /* ============================================================
       EDITAR
       ============================================================ */
    public function editar(
        $idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos,
        $roles_array = null, $modo_permisos = '', $mantener_clave = false
    ){
        $idusuario = (int)$idusuario;

        $act = ejecutarConsultaSimpleFila("SELECT imagen, condicion FROM usuario WHERE idusuario='$idusuario'");
        $imgActual  = $act['imagen']     ?? '';
        $condActual = (string)($act['condicion'] ?? '1');

        // Si viene roles_array, obtener cargo del rol principal
        if (is_array($roles_array) && count($roles_array) > 0) {
            foreach($roles_array as $r){
                if (isset($r['es_principal']) && (int)$r['es_principal'] === 1) {
                    $cargo = $this->obtenerNombreRol($r['id_rol']) ?: $cargo;
                    break;
                }
            }
        }

        $tieneRoles = is_array($roles_array) && count($roles_array) > 0;
        $tienePermisos = is_array($permisos) && count($permisos) > 0;

        $sets = array();
        $sets[] = "nombre='$nombre'";
        $sets[] = "tipo_documento='$tipo_documento'";
        $sets[] = "num_documento='$num_documento'";
        $sets[] = "direccion='$direccion'";
        $sets[] = "telefono='$telefono'";
        $sets[] = "email='$email'";
        $sets[] = "cargo='$cargo'";

        if (!$mantener_clave && $clave !== null && $clave !== '') {
            $sets[] = "clave='$clave'";
        }

        if ($imagen !== null && $imagen !== '') {
            $sets[] = "imagen='$imagen'";
        } else {
            $defaults = ['administrador.png','almacenero.png','vendedor.png','usuario.png'];
            if (in_array($imgActual, $defaults, true)) {
                $nuevo = $this->avatarPorRol($cargo);
                if ($nuevo !== $imgActual) $sets[] = "imagen='$nuevo'";
            }
        }

        // Si hay un solo rol, actualizar id_rol (compatibilidad)
        if ($tieneRoles && count($roles_array) === 1) {
            $id_rol = (int)$roles_array[0]['id_rol'];
            $sets[] = "id_rol='$id_rol'";
        } elseif ($tieneRoles && count($roles_array) > 1) {
            // Si hay múltiples roles, usar el principal como id_rol
            foreach($roles_array as $r){
                if (isset($r['es_principal']) && (int)$r['es_principal'] === 1) {
                    $id_rol = (int)$r['id_rol'];
                    $sets[] = "id_rol='$id_rol'";
                    break;
                }
            }
        }

        if ($condActual === '3' && ($tieneRoles || $tienePermisos)) {
            $sets[] = "condicion='1'";
        }

        $sql = "UPDATE usuario SET ".implode(",", $sets)." WHERE idusuario='$idusuario'";
        ejecutarConsulta($sql);

        // Asignar múltiples roles
        if ($tieneRoles) {
            $this->asignarRolesMultiples($idusuario, $roles_array);
            $permisos = $this->obtenerPermisosAcumulativos($idusuario);
        }

        if ($tienePermisos || ($modo_permisos === 'rol' && $tieneRoles)) {
            ejecutarConsulta("DELETE FROM usuario_permiso WHERE idusuario='$idusuario'");
            return $this->setPermisosUsuario($idusuario, $permisos);
        }
        return true;
    }

    public function desactivar($idusuario){
        return ejecutarConsulta("UPDATE usuario SET condicion='0' WHERE idusuario='$idusuario'");
    }
    
    public function activar($idusuario){
        return ejecutarConsulta("UPDATE usuario SET condicion='1' WHERE idusuario='$idusuario'");
    }

    public function mostrar($idusuario){
        $sql="SELECT u.* 
              FROM usuario u
              WHERE u.idusuario='$idusuario'";
        return ejecutarConsultaSimpleFila($sql);
    }

    public function listar(){
        $sql="SELECT 
                u.idusuario,
                u.nombre,
                COALESCE(td.nombre, u.tipo_documento) AS tipo_documento,
                u.num_documento,
                u.telefono,
                u.email,
                u.cargo,
                u.imagen,
                u.condicion,
                (SELECT GROUP_CONCAT(
                    CONCAT(r.nombre, IF(ur.es_principal=1, ' (Principal)', ''))
                    ORDER BY ur.es_principal DESC, r.nombre ASC
                    SEPARATOR ', '
                )
                FROM usuario_roles_new ur
                INNER JOIN rol_usuarios r ON ur.id_rol = r.id_rol
                WHERE ur.idusuario = u.idusuario) AS todos_roles
              FROM usuario u
              LEFT JOIN tipo_documento td ON u.id_tipodoc = td.id_tipodoc
              ORDER BY u.idusuario DESC";
        return ejecutarConsulta($sql);
    }

    public function listarmarcados($idusuario){
        return ejecutarConsulta("SELECT * FROM usuario_permiso WHERE idusuario='$idusuario'");
    }

    public function verificar($email, $clave){
        $sql="SELECT idusuario, nombre, tipo_documento, num_documento, telefono, email, cargo, imagen
              FROM usuario
              WHERE email='$email' AND clave='$clave' AND condicion='1'";
        return ejecutarConsulta($sql);
    }

    public function verificarEmailExiste($email, $idusuario = 0){
        global $conexion;
        $email_escaped = $conexion->real_escape_string($email);
        $sql = ($idusuario > 0)
            ? "SELECT COUNT(*) as total FROM usuario WHERE email='$email_escaped' AND idusuario != '$idusuario'"
            : "SELECT COUNT(*) as total FROM usuario WHERE email='$email_escaped'";
        $result = ejecutarConsultaSimpleFila($sql);
        if (!$result || !isset($result['total'])) return false;
        return ((int)$result['total'] > 0);
    }

    public function verificarDocumentoExiste($tipo_documento, $num_documento, $idusuario = 0){
        global $conexion;
        $tipo_escaped = $conexion->real_escape_string($tipo_documento);
        $num_escaped  = $conexion->real_escape_string($num_documento);
        $sql = ($idusuario > 0)
            ? "SELECT COUNT(*) as total FROM usuario WHERE tipo_documento='$tipo_escaped' AND num_documento='$num_escaped' AND idusuario != '$idusuario'"
            : "SELECT COUNT(*) as total FROM usuario WHERE tipo_documento='$tipo_escaped' AND num_documento='$num_escaped'";
        $result = ejecutarConsultaSimpleFila($sql);
        if (!$result || !isset($result['total'])) return false;
        return ((int)$result['total'] > 0);
    }
}
?>