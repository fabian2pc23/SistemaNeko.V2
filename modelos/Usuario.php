<?php
// modelos/Usuario.php
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

    // Avatar por rol (si no suben imagen)
    private function avatarPorRol($cargo){
        $k = mb_strtolower(trim((string)$cargo),'UTF-8');
        if ($k === 'administrador') return 'administrador.png';
        if ($k === 'almacenero')   return 'almacenero.png';
        if ($k === 'vendedor')     return 'vendedor.png';
        return 'usuario.png';
    }

    // === Permisos por rol ===
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
       INSERTAR
       ============================================================ */
    public function insertar(
        $nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos,
        $id_rol = null, $modo_permisos = ''
    ){
        // Completar cargo desde el rol si viene id_rol
        if ((empty($cargo) || $cargo === '0') && !empty($id_rol)) {
            $cargo = $this->obtenerNombreRol($id_rol) ?: '';
        }

        $tieneRol      = !is_null($id_rol) && $id_rol !== '' && (int)$id_rol > 0;
        $tienePermisos = is_array($permisos) && count($permisos) > 0;

        // Si no mandan permisos y modo='rol' → traer del rol
        if (!$tienePermisos && $modo_permisos === 'rol' && $tieneRol) {
            $permisos = $this->permisosDeRol((int)$id_rol);
            $tienePermisos = count($permisos) > 0;
        }

        // Estado: si hay rol o permisos → Activo; si no → Pendiente(3)
        $condicion = ($tieneRol || $tienePermisos) ? '1' : '3';

        // Avatar por rol si no llega imagen
        if ($imagen === null || $imagen === '') {
            $imagen = $this->avatarPorRol($cargo);
        }

        $cols = "nombre,tipo_documento,num_documento,direccion,telefono,email,cargo,clave,imagen,condicion";
        $vals = "'$nombre','$tipo_documento','$num_documento','$direccion','$telefono','$email','$cargo','$clave','$imagen','$condicion'";
        if ($tieneRol) {
            $id_rol = (int)$id_rol;
            $cols .= ",id_rol";
            $vals .= ",'$id_rol'";
        }

        $sql = "INSERT INTO usuario ($cols) VALUES ($vals)";
        $idusuarionew = ejecutarConsulta_retornarID($sql);

        // Aplicar permisos (explícitos o derivados)
        $this->setPermisosUsuario($idusuarionew, $permisos);
        return true;
    }

    /* ============================================================
       EDITAR
       ============================================================ */
    public function editar(
        $idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos,
        $id_rol = null, $modo_permisos = '', $mantener_clave = false
    ){
        $idusuario = (int)$idusuario;

        // Datos actuales para decidir imagen/estado
        $act = ejecutarConsultaSimpleFila("SELECT imagen, condicion, id_rol, cargo FROM usuario WHERE idusuario='$idusuario'");
        $imgActual  = $act['imagen']     ?? '';
        $condActual = (string)($act['condicion'] ?? '1');

        // Si cargo vacío y llega id_rol → nombre del rol
        if ((empty($cargo) || $cargo === '0') && !empty($id_rol)) {
            $cargo = $this->obtenerNombreRol($id_rol) ?: '';
        }

        $tieneRol      = !is_null($id_rol) && $id_rol !== '' && (int)$id_rol > 0;
        $tienePermisos = is_array($permisos) && count($permisos) > 0;

        // Si no mandan permisos y modo='rol' → traer del rol
        if (!$tienePermisos && $modo_permisos === 'rol' && $tieneRol) {
            $permisos      = $this->permisosDeRol((int)$id_rol);
            $tienePermisos = count($permisos) > 0;
        }

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

        // Imagen:
        if ($imagen !== null && $imagen !== '') {
            // subieron archivo nuevo (nombre ya resuelto en ajax)
            $sets[] = "imagen='$imagen'";
        } else {
            // si NO suben y el actual es de los defaults, ajustar al rol
            $defaults = ['administrador.png','almacenero.png','vendedor.png','usuario.png'];
            if (in_array($imgActual, $defaults, true)) {
                $nuevo = $this->avatarPorRol($cargo);
                if ($nuevo !== $imgActual) $sets[] = "imagen='$nuevo'";
            }
        }

        if ($tieneRol) { $sets[] = "id_rol='".((int)$id_rol)."'"; }

        // Si estaba Pendiente (3) y ahora hay rol o permisos → Activar (1)
        if ($condActual === '3' && ($tieneRol || $tienePermisos)) {
            $sets[] = "condicion='1'";
        }

        $sql = "UPDATE usuario SET ".implode(",", $sets)." WHERE idusuario='$idusuario'";
        ejecutarConsulta($sql);

        // Permisos: aplicar si llegan o si modo='rol'
        if ($tienePermisos || ($modo_permisos === 'rol' && $tieneRol)) {
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
        $sql="SELECT u.*, r.nombre AS nombre_rol
              FROM usuario u
              LEFT JOIN rol_usuarios r ON u.id_rol = r.id_rol
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
                u.id_rol,
                u.imagen,
                u.condicion,
                r.nombre AS nombre_rol
              FROM usuario u
              LEFT JOIN rol_usuarios  r  ON u.id_rol = r.id_rol
              LEFT JOIN tipo_documento td ON u.id_tipodoc = td.id_tipodoc
              ORDER BY u.idusuario DESC";
        return ejecutarConsulta($sql);
    }

    public function listarmarcados($idusuario){
        return ejecutarConsulta("SELECT * FROM usuario_permiso WHERE idusuario='$idusuario'");
    }

    // LOGIN por email
    public function verificar($email, $clave){
        $sql="SELECT idusuario, nombre, tipo_documento, num_documento, telefono, email, cargo, imagen, id_rol
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
