<?php 
//Incluímos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

Class Marca
{
	//Implementamos nuestro constructor
	public function __construct()
	{

	}

	//Implementamos un método para insertar registros
	public function insertar($nombre,$descripcion)
	{
	    try {
	        $sql="INSERT INTO marca (nombre,descripcion,condicion)
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
	public function editar($idmarca,$nombre,$descripcion)
	{
		$sql="UPDATE marca SET nombre='$nombre',descripcion='$descripcion' WHERE idmarca='$idmarca'";
		return ejecutarConsulta($sql);
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

	// Marcas con stock crítico (artículos con stock <= 5)
	public function marcasStockCritico()
	{
		$sql = "SELECT COUNT(DISTINCT m.idmarca) as total 
		        FROM marca m
		        INNER JOIN articulo a ON m.idmarca = a.idmarca
		        WHERE a.stock <= 5 AND a.stock > 0 AND m.condicion = 1";
		return ejecutarConsultaSimpleFila($sql);
	}

	// Marcas nuevas (últimas 5 marcas creadas por ID más alto)
	public function marcasNuevas()
	{
		$sql = "SELECT COUNT(*) as total 
		        FROM (
		            SELECT idmarca 
		            FROM marca 
		            WHERE condicion = 1
		            ORDER BY idmarca DESC 
		            LIMIT 5
		        ) as nuevas";
		return ejecutarConsultaSimpleFila($sql);
	}

	// Método adicional: Obtener estadísticas de marcas
	public function estadisticasMarcas()
	{
		$sql = "SELECT 
		        COUNT(*) as total_marcas,
		        SUM(CASE WHEN condicion = 1 THEN 1 ELSE 0 END) as marcas_activas,
		        SUM(CASE WHEN condicion = 0 THEN 1 ELSE 0 END) as marcas_inactivas
		        FROM marca";
		return ejecutarConsultaSimpleFila($sql);
	}

	// Método adicional: Marcas con más artículos
	public function marcasConMasArticulos($limite = 5)
	{
		$sql = "SELECT m.nombre, COUNT(a.idarticulo) as total_articulos
		        FROM marca m
		        LEFT JOIN articulo a ON m.idmarca = a.idmarca AND a.condicion = 1
		        WHERE m.condicion = 1
		        GROUP BY m.idmarca, m.nombre
		        ORDER BY total_articulos DESC
		        LIMIT $limite";
		return ejecutarConsulta($sql);
	}
}

?>