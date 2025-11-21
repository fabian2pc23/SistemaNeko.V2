<?php 
//Incluímos inicialmente la conexión a la base de datos
require_once "../config/Conexion.php";

Class Marca
{
	//Implementamos nuestro constructor
	public function __construct()
	{

	}

	//Implementamos un método para insertar registros
	public function insertar($nombre,$descripcion)
	{
		$validar = $this->validarDuplicado($nombre);
		if ($validar) {
			return "duplicado";
		}

		$sql="INSERT INTO marca (nombre,descripcion,condicion)
		      VALUES ('$nombre','$descripcion','1')";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para editar registros
	public function editar($idmarca,$nombre,$descripcion)
	{
		$validar = $this->validarDuplicado($nombre, $idmarca);
		if ($validar) {
			return "duplicado";
		}

		$sql="UPDATE marca SET nombre='$nombre',descripcion='$descripcion' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
	}

	//Validar duplicados (Exacto y Singular/Plural)
	public function validarDuplicado($nombre, $idmarca = 0)
	{
		// Normalizar nombre (eliminar espacios extra y convertir a minúsculas para comparación)
		$nombre = trim($nombre);
		
		// Generar variaciones (Singular/Plural básico)
		// Si termina en 's', quitamos la 's' para buscar el singular
		// Si no termina en 's', agregamos 's' y 'es' para buscar plurales
		
		$variaciones = array();
		$variaciones[] = $nombre; // El nombre exacto

		if (substr($nombre, -1) == 's' || substr($nombre, -1) == 'S') {
			$variaciones[] = substr($nombre, 0, -1); // Posible singular
			if (substr($nombre, -2) == 'es' || substr($nombre, -2) == 'ES') {
				$variaciones[] = substr($nombre, 0, -2); // Posible singular de 'es'
			}
		} else {
			$variaciones[] = $nombre . 's'; // Posible plural simple
			$variaciones[] = $nombre . 'es'; // Posible plural con 'es'
		}

		// Construir la consulta SQL
		// Buscamos cualquiera de las variaciones
		$sql = "SELECT * FROM marca WHERE (";
		$first = true;
		foreach ($variaciones as $val) {
			if (!$first) $sql .= " OR ";
			$sql .= "nombre LIKE '$val'";
			$first = false;
		}
		$sql .= ") AND idmarca != '$idmarca'";

		$resultado = ejecutarConsulta($sql);
		
		// Si hay resultados, es un duplicado
		if ($resultado->num_rows > 0) {
			return true;
		}
		return false;
	}

	//Implementamos un método para desactivar marcas
	public function desactivar($idmarca)
	{
		$sql="UPDATE marca SET condicion='0' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para activar marcas
	public function activar($idmarca)
	{
		$sql="UPDATE marca SET condicion='1' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
	}

	//Implementar un método para mostrar los datos de un registro a modificar
	public function mostrar($idmarca)
	{
		$sql="SELECT * FROM marca WHERE idmarca='$idmarca'";
		return ejecutarConsultaSimpleFila($sql);
	}

	//Implementar un método para listar los registros
	public function listar()
	{
		$sql="SELECT * FROM marca ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	//Implementar un método para listar los registros y mostrar en el select
	public function select()
	{
		$sql="SELECT * FROM marca WHERE condicion=1 ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	//Implementar un método para listar los registros activos (alias de select para consistencia)
	public function listarActivos()
	{
		$sql="SELECT * FROM marca WHERE condicion=1 ORDER BY nombre ASC";
		return ejecutarConsulta($sql);		
	}

	// ==================== MÉTODOS PARA KPIs ====================
	
	// Marcas sin artículos
	public function marcasSinArticulos()
	{
		$sql = "SELECT m.idmarca, m.nombre, COUNT(a.idarticulo) as total_articulos
		        FROM marca m
		        LEFT JOIN articulo a ON m.idmarca = a.idmarca
		        WHERE m.condicion = 1
		        GROUP BY m.idmarca, m.nombre
		        HAVING total_articulos = 0
		        ORDER BY m.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Marcas con stock crítico (al menos un artículo con stock <= 5)
	public function marcasStockCritico()
	{
		$sql = "SELECT DISTINCT m.idmarca, m.nombre
		        FROM marca m
		        INNER JOIN articulo a ON m.idmarca = a.idmarca
		        WHERE m.condicion = 1 AND a.stock <= 5
		        ORDER BY m.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Marcas nuevas (últimas 5 registradas)
	public function marcasNuevas()
	{
		$sql = "SELECT idmarca, nombre
		        FROM marca
		        WHERE condicion = 1
		        ORDER BY idmarca DESC
		        LIMIT 5";
		return ejecutarConsulta($sql);
	}
}

