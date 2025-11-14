<?php 
// modelos/Permiso.php - Versión mejorada (opcional)
require "../config/Conexion.php";

Class Permiso
{
	// Constructor
	public function __construct()
	{

	}

	// ==================== LISTAR TODOS LOS PERMISOS ====================
	// Método original - mantiene compatibilidad con tu código actual
	public function listar()
	{
		$sql="SELECT * FROM permiso ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	// ==================== MÉTODOS ADICIONALES (OPCIONALES) ====================
	// Puedes usarlos en el futuro si los necesitas

	// Listar solo permisos activos (si tu tabla tiene campo 'estado')
	public function listarActivos()
	{
		$sql="SELECT * FROM permiso WHERE estado=1 ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	// Mostrar un permiso específico
	public function mostrar($idpermiso)
	{
		$sql="SELECT * FROM permiso WHERE idpermiso='$idpermiso'";
		return ejecutarConsultaSimpleFila($sql);
	}

	// Obtener permisos de un rol específico
	public function obtenerPermisosRol($idrol)
	{
		$sql="SELECT p.idpermiso, p.nombre 
		      FROM permiso p
		      INNER JOIN rol_permiso rp ON p.idpermiso = rp.idpermiso
		      WHERE rp.id_rol = '$idrol'
		      ORDER BY p.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Obtener permisos de un usuario específico
	public function obtenerPermisosUsuario($idusuario)
	{
		$sql="SELECT p.idpermiso, p.nombre 
		      FROM permiso p
		      INNER JOIN usuario_permiso up ON p.idpermiso = up.idpermiso
		      WHERE up.idusuario = '$idusuario'
		      ORDER BY p.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Verificar si existe un permiso con ese nombre
	public function existePermiso($nombre)
	{
		$sql="SELECT idpermiso FROM permiso WHERE nombre='$nombre'";
		$resultado = ejecutarConsulta($sql);
		return $resultado->num_rows > 0;
	}

	// Contar permisos totales
	public function contar()
	{
		$sql="SELECT COUNT(*) as total FROM permiso";
		return ejecutarConsultaSimpleFila($sql);
	}
}

?>