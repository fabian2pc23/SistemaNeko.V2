<?php  
//Incluímos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

class Persona
{
	//Implementamos nuestro constructor
	public function __construct()
	{
	}

	//Implementamos un método para insertar registros
	public function insertar($tipo_persona, $nombre, $tipo_documento, $num_documento, $direccion, $telefono, $email)
	{
		$sql = "INSERT INTO persona 
				(tipo_persona, nombre, tipo_documento, num_documento, direccion, telefono, email, condicion)
				VALUES ('$tipo_persona', '$nombre', '$tipo_documento', '$num_documento', '$direccion', '$telefono', '$email', '1')";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para editar registros
	public function editar($idpersona, $tipo_persona, $nombre, $tipo_documento, $num_documento, $direccion, $telefono, $email)
	{
		$sql = "UPDATE persona 
				SET tipo_persona='$tipo_persona',
					nombre='$nombre',
					tipo_documento='$tipo_documento',
					num_documento='$num_documento',
					direccion='$direccion',
					telefono='$telefono',
					email='$email'
				WHERE idpersona='$idpersona'";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para eliminar registros (ya no se usará en proveedores, pero se deja por compatibilidad)
	public function eliminar($idpersona)
	{
		$sql = "DELETE FROM persona WHERE idpersona='$idpersona'";
		return ejecutarConsulta($sql);
	}

	//Implementar un método para mostrar los datos de un registro a modificar
	public function mostrar($idpersona)
	{
		$sql = "SELECT * FROM persona WHERE idpersona='$idpersona'";
		return ejecutarConsultaSimpleFila($sql);
	}

	//Implementar un método para listar los proveedores
	public function listarp()
	{
		$sql = "SELECT * FROM persona WHERE tipo_persona='Proveedor'";
		return ejecutarConsulta($sql);		
	}

	//Implementar un método para listar los clientes
	public function listarc()
	{
		$sql = "SELECT * FROM persona WHERE tipo_persona='Cliente'";
		return ejecutarConsulta($sql);		
	}

	//Desactivar persona (proveedor o cliente)
	public function desactivar($idpersona)
	{
		$sql = "UPDATE persona SET condicion='0' WHERE idpersona='$idpersona'";
		return ejecutarConsulta($sql);
	}

	//Activar persona (proveedor o cliente)
	public function activar($idpersona)
	{
		$sql = "UPDATE persona SET condicion='1' WHERE idpersona='$idpersona'";
		return ejecutarConsulta($sql);
	} 

	// Solo proveedores activos para combos (ingresos)
public function selectProveedoresActivos()
{
  $sql = "SELECT idpersona, nombre 
          FROM persona 
          WHERE tipo_persona='Proveedor' AND condicion=1
          ORDER BY nombre";
  return ejecutarConsulta($sql);
}

// Verificación rápida de estado antes de registrar compra
public function proveedorEstaActivo($idpersona)
{
  $idpersona = (int)$idpersona;
  $sql = "SELECT condicion 
          FROM persona 
          WHERE idpersona=$idpersona AND tipo_persona='Proveedor' 
          LIMIT 1";
  $fila = ejecutarConsultaSimpleFila($sql);
  return isset($fila['condicion']) ? (int)$fila['condicion'] === 1 : false;
} 
// modelos/Persona.php

public function existeProveedor($num_documento, $idpersona = 0)
{
  $idpersona = (int)$idpersona;
  $num = mysqli_real_escape_string($GLOBALS['conexion'], $num_documento);
  $sql = "SELECT idpersona 
          FROM persona 
          WHERE tipo_persona='Proveedor' 
            AND num_documento='$num'
            AND idpersona <> '$idpersona'
          LIMIT 1";
  $fila = ejecutarConsultaSimpleFila($sql);
  return !empty($fila) && isset($fila['idpersona']);
}

}

?>

