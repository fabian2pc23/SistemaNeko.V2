<?php 
//Incluímos inicialmente la conexión a la base de datos
require_once "../config/Conexion.php";

Class Categoria
{
	//Implementamos nuestro constructor
	public function __construct()
	{

	}

	//Implementamos un método para insertar registros
	public function insertar($nombre,$descripcion)
	{
	    try {
	        $sql="INSERT INTO categoria (nombre,descripcion,condicion)
	              VALUES ('$nombre','$descripcion','1')";
	        return ejecutarConsulta($sql);
	    } catch (Exception $e) {
	        if (strpos($e->getMessage(), 'Duplicate') !== false) {
	            return "duplicado";
	        } else {
	            return "error";
	        }
	    }
	}

	//Implementamos un método para editar registros
	public function editar($idcategoria,$nombre,$descripcion)
	{
		$sql="UPDATE categoria SET nombre='$nombre',descripcion='$descripcion' WHERE idcategoria='$idcategoria'";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para desactivar categorías
	public function desactivar($idcategoria)
	{
		$sql="UPDATE categoria SET condicion='0' WHERE idcategoria='$idcategoria'";
		return ejecutarConsulta($sql);
	}

	//Implementamos un método para activar categorías
	public function activar($idcategoria)
	{
		$sql="UPDATE categoria SET condicion='1' WHERE idcategoria='$idcategoria'";
		return ejecutarConsulta($sql);
	}

	//Implementar un método para mostrar los datos de un registro a modificar
	public function mostrar($idcategoria)
	{
		$sql="SELECT * FROM categoria WHERE idcategoria='$idcategoria'";
		return ejecutarConsultaSimpleFila($sql);
	}

	//Implementar un método para listar los registros
	public function listar()
	{
		$sql="SELECT * FROM categoria";
		return ejecutarConsulta($sql);		
	}

	//Implementar un método para listar los registros y mostrar en el select
	public function select()
	{
		$sql="SELECT * FROM categoria where condicion=1";
		return ejecutarConsulta($sql);		
	}

	// ==================== MÉTODOS PARA KPIs ====================
	
	// Categorías sin artículos
	public function categoriasSinArticulos()
	{
		$sql = "SELECT c.idcategoria, c.nombre, COUNT(a.idarticulo) as total_articulos
		        FROM categoria c
		        LEFT JOIN articulo a ON c.idcategoria = a.idcategoria
		        WHERE c.condicion = 1
		        GROUP BY c.idcategoria, c.nombre
		        HAVING total_articulos = 0
		        ORDER BY c.nombre ASC";
		return ejecutarConsulta($sql);
	}

	// Categorías con stock crítico (artículos con stock <= 10)
	public function categoriasStockCritico()
	{
		$sql = "SELECT COUNT(DISTINCT c.idcategoria) as total 
		        FROM categoria c
		        INNER JOIN articulo a ON c.idcategoria = a.idcategoria
		        WHERE a.stock <= 10 AND a.stock > 0 AND c.condicion = 1";
		return ejecutarConsultaSimpleFila($sql);
	}

	// Categorías nuevas (últimos 30 días)
	public function categoriasNuevas()
	{
		// Como la tabla categoria no tiene fecha_creacion,
		// contamos las últimas 5 categorías creadas (por ID más alto)
		$sql = "SELECT COUNT(*) as total 
		        FROM (
		            SELECT idcategoria 
		            FROM categoria 
		            WHERE condicion = 1
		            ORDER BY idcategoria DESC 
		            LIMIT 5
		        ) as nuevas";
		return ejecutarConsultaSimpleFila($sql);
	}
}

